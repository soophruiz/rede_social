<?php
session_start();
include "conexao.php";

if(!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['id'];

// Dados do usuário
$stmtUser = $con->prepare("SELECT id, nome, foto_perfil, bio FROM usuarios WHERE id = ?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if(!$usuario) {
    echo "Usuário não encontrado. Faça login novamente.";
    session_destroy();
    exit;
}

// Atualizar perfil
if(isset($_POST['acao']) && $_POST['acao'] === 'atualizar') {
    $novo_nome = trim($_POST['nome']);
    $nova_bio = trim($_POST['bio']);
    $foto = $usuario['foto_perfil'];

    if(!empty($_FILES['foto_perfil']['name'])) {
        $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $foto = "perfil/".uniqid().".".$ext;
        if(!is_dir("perfil")) mkdir("perfil", 0777, true);
        move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $foto);
    }

    $stmt = $con->prepare("UPDATE usuarios SET nome = ?, foto_perfil = ?, bio = ? WHERE id = ?");
    $stmt->bind_param("sssi", $novo_nome, $foto, $nova_bio, $usuario_id);
    $stmt->execute();
    header("Location: perfil.php");
    exit;
}

// Contagem seguidores e seguindo
$stmtSeg = $con->prepare("SELECT (SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?) AS seguidores,
                                 (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?) AS seguindo");
$stmtSeg->bind_param("ii", $usuario_id, $usuario_id);
$stmtSeg->execute();
$contagem = $stmtSeg->get_result()->fetch_assoc();
$stmtSeg->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Perfil</title>
<style>
body { font-family: 'Poppins', sans-serif; background:#f4f4f8; margin:0; padding:0; color:#333; }

.sidebar {
    position: fixed; left:0; top:0; height:100vh; width:240px; background:#fff;
    border-right:1px solid rgba(0,0,0,0.05); padding:25px 18px;
    box-shadow:0 6px 20px rgba(0,0,0,0.06); z-index:2000;
}
.sidebar .brand { display:flex; align-items:center; gap:10px; margin-bottom:30px; }
.sidebar .brand img{ height:44px; }
.sidebar .brand b{ color:#ff4da6; font-size:22px; }
.sidebar nav a{ display:flex; align-items:center; gap:12px; text-decoration:none; color:#555; padding:12px 14px; border-radius:14px; margin-bottom:12px; font-weight:600; transition:0.2s; }
.sidebar nav a:hover{ background: linear-gradient(90deg, rgba(255,77,166,0.12), rgba(255,183,217,0.06)); color:#ff4da6; }
.sidebar nav a.ativo{ background: linear-gradient(90deg, rgba(255,77,166,0.2), rgba(255,183,217,0.1)); color:#ff4da6; }

.main-wrap { margin-left:260px; padding:30px 24px; max-width:750px; }

h2 { color:#ff4da6; font-size:26px; margin-bottom:20px; text-align:center; }

#perfil { background:#fff0f5; padding:25px; border-radius:18px; box-shadow:0 8px 25px rgba(0,0,0,0.08); text-align:center; margin-bottom:20px; }
#perfil img { width:120px; height:120px; border-radius:50%; object-fit:cover; margin-bottom:15px; border:4px solid #ff4da6; }
#perfil h3 { color:#ff4da6; margin-bottom:10px; }

.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content:center; align-items:center; }
.modal-content { background:#fff; padding:25px; border-radius:18px; width:90%; max-width:400px; position:relative; }
.modal-content input, .modal-content textarea { width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ffb3d9; box-sizing:border-box; }
.modal-content button { background:#ff4da6; color:white; font-weight:bold; border:none; padding:10px; border-radius:10px; cursor:pointer; transition:0.3s; }
.modal-content button:hover{ background:#ff1a75; }
.close { position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; }

.follow-info { display:flex; justify-content:center; gap:20px; margin:15px 0; font-weight:bold; }
.follow-info a { text-decoration:none; background:#ff4da6; color:white; padding:8px 15px; border-radius:12px; transition:0.3s; }
.follow-info a:hover { background:#ff1a75; }

.post { background:#fff0f5; border-radius:18px; padding:20px; margin-bottom:15px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.post-header { display:flex; align-items:center; margin-bottom:10px; }
.post-header img { width:50px; height:50px; border-radius:50%; margin-right:10px; object-fit:cover; border:2px solid #ff4da6; }
.post-header .info { font-size:14px; } .post-header .info b { display:block; font-size:16px; color:#ff4da6; }

textarea, input[type=text] { width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ffb3d9; box-sizing:border-box; }
form.inline { display:inline; } img.icon { width:20px; vertical-align:middle; }
.comentario { margin-left:20px; margin-top:5px; background:#fce4f2; padding:8px; border-radius:8px; }

@media (max-width:900px){ .sidebar{display:none;} .main-wrap{margin-left:0;padding:20px;} }
</style>
</head>
<body>

<div class="sidebar">
    <div class="brand">
        <img src="logo.png" alt="Logo">
        <b>PIXIE</b>
    </div>
    <nav>
        <a href="feed.php">Feed</a>
        <a href="perfil.php" class="ativo">Perfil</a>
        <a href="chat.php">Mensagens</a>
        <a href="todas_notificacoes.php">Notificações</a>
        <a href="logout.php">Sair</a>
    </nav>
</div>

<div class="main-wrap">

<h2>Meu Perfil</h2>

<div id="perfil">
    <img src="<?php echo !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'perfil/default.png'; ?>" alt="Foto de Perfil">
    <h3><?php echo htmlspecialchars($usuario['nome']); ?></h3>
    <p><?php echo nl2br(htmlspecialchars($usuario['bio'])); ?></p>

    <div class="follow-info">
        <span><?php echo $contagem['seguidores']; ?> Seguidores</span>
        <span><?php echo $contagem['seguindo']; ?> Seguindo</span>
    </div>

    <div class="follow-info">
        <a href="seguidores.php">Seguidores</a>
        <a href="seguindo.php">Seguindo</a>
    </div>

    <button onclick="document.getElementById('modal-edit').style.display='flex'" >Editar Perfil</button>

</div>

<!-- Modal Editar Perfil -->
<div id="modal-edit" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modal-edit').style.display='none'">&times;</span>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="atualizar">
            <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" placeholder="Nome" required>
            <textarea name="bio" placeholder="Escreva algo sobre você..." rows="3"><?php echo htmlspecialchars($usuario['bio']); ?></textarea>
            <input type="file" name="foto_perfil">
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</div>

<h3>Minhas Postagens</h3>

<?php
$sql = "SELECT p.id, p.conteudo, p.imagem, p.criado_em FROM postagens p WHERE p.usuario_id = $usuario_id ORDER BY p.id DESC";
$res = $con->query($sql);

if($res->num_rows==0){ echo "<p style='text-align:center;'>Você ainda não postou nada.</p>"; }
else{
    while($post=$res->fetch_assoc()){
        echo "<div class='post'>";
        echo "<div class='post-header'>";
        echo "<img src='".(!empty($usuario['foto_perfil'])?$usuario['foto_perfil']:'perfil/default.png')."'>";
        echo "<div class='info'><b>".htmlspecialchars($usuario['nome'])."</b><small>".date("d/m/Y H:i",strtotime($post['criado_em']))."</small></div>";
        echo "</div>";
        echo "<p>".nl2br(htmlspecialchars($post['conteudo']))."</p>";
        if(!empty($post['imagem'])) echo "<img src='".htmlspecialchars($post['imagem'])."' style='max-width:100%;border-radius:8px;margin-top:10px;'><br>";

        echo "<a href='#' onclick=\"document.getElementById('form-editar".$post['id']."').style.display='block'\">
                     <button type='submit' style='border:pink;background:pink;cursor:pointer;color:white;border-radius:30%;height:30px;widht:50;'>Editar legenda</button>
                  </a>";

        echo "<form id='form-editar".$post['id']."' method='POST' style='display:none;margin-top:10px;'>
                <input type='hidden' name='acao' value='editar'>
                <input type='hidden' name='id_post' value='".$post['id']."'>
                <textarea name='nova_legenda' required>".htmlspecialchars($post['conteudo'])."</textarea>
                <button type='submit'>Salvar</button>
              </form>";

        echo "</div>";
    }
}
?>

</div>
</body>
</html>
