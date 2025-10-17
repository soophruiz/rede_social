<?php
session_start();
include('conexao.php');

if(!isset($_SESSION['id'])){
    header("Location: index.php");
    exit;
}

$idUsuario = $_SESSION['id'];
$idPerfil = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($idPerfil <= 0){
    echo "Perfil inválido.";
    exit;
}

// Buscar dados do usuário pesquisado
$stmt = $con->prepare("SELECT id, nome, foto_perfil, bio FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $idPerfil);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$usuario){
    echo "Usuário não encontrado.";
    exit;
}

// Contagem seguidores / seguindo
$stmt2 = $con->prepare("
    SELECT 
        (SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?) AS seguidores,
        (SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?) AS seguindo
");
$stmt2->bind_param("ii", $idPerfil, $idPerfil);
$stmt2->execute();
$dadosSeg = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// Verifica se o usuário logado já segue o perfil
$stmtSeg = $con->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
$stmtSeg->bind_param("ii", $idUsuario, $idPerfil);
$stmtSeg->execute();
$jaSegue = $stmtSeg->get_result()->num_rows > 0;
$stmtSeg->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Perfil de <?php echo htmlspecialchars($usuario['nome']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: 'Poppins', sans-serif; background:#f4f4f8; margin:0; padding:0; color:#333; }
.sidebar { position: fixed; left:0; top:0; height:100vh; width:240px; background:#fff; border-right:1px solid rgba(0,0,0,0.05); padding:25px 18px; box-shadow:0 6px 20px rgba(0,0,0,0.06); z-index:2000; }
.sidebar .brand { display:flex; align-items:center; gap:10px; margin-bottom:30px; }
.sidebar .brand img{ height:44px; }
.sidebar .brand b{ color:#ff4da6; font-size:22px; }
.sidebar nav a{ display:flex; align-items:center; gap:12px; text-decoration:none; color:#555; padding:12px 14px; border-radius:14px; margin-bottom:12px; font-weight:600; transition:0.2s; }
.sidebar nav a:hover{ background: linear-gradient(90deg, rgba(255,77,166,0.12), rgba(255,183,217,0.06)); color:#ff4da6; }
.sidebar nav a.ativo{ background: linear-gradient(90deg, rgba(255,77,166,0.2), rgba(255,183,217,0.1)); color:#ff4da6; }

.main-wrap { margin-left:260px; padding:30px 24px; max-width:750px; }
h2 { color:#ff4da6; font-size:26px; margin-bottom:20px; text-align:center; }

.perfil-container { background:#fff0f5; padding:25px; border-radius:18px; box-shadow:0 8px 25px rgba(0,0,0,0.08); text-align:center; margin-bottom:20px; }
.perfil-container .foto-perfil { width:120px; height:120px; border-radius:50%; object-fit:cover; margin-bottom:15px; border:4px solid #ff4da6; }
.perfil-container h3, .perfil-container .nome { color:#ff4da6; margin-bottom:10px; font-size:26px; }
.perfil-container .bio { font-size:14px; color:#555; margin-bottom:20px; }

.info { display:flex; justify-content:center; gap:20px; font-weight:bold; margin-bottom:20px; }
.info div { text-align:center; }

.follow-btn { background:#ff4da6; color:white; font-weight:bold; border:none; padding:10px 20px; border-radius:12px; cursor:pointer; transition:0.3s; }
.follow-btn:hover { background:#ff1a75; }

.linha-divisoria { border:none; border-top:1px solid rgba(0,0,0,0.1); margin:30px 0; }

.postagens-container h3 { color:#ff4da6; margin-bottom:15px; text-align:center; }
.post { background:#fff0f5; border-radius:18px; padding:20px; margin-bottom:15px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.post-header { display:flex; align-items:center; margin-bottom:10px; }
.post-header img.post-user-img { width:50px; height:50px; border-radius:50%; margin-right:10px; object-fit:cover; border:2px solid #ff4da6; }
.post-header .info { font-size:14px; } 
.post-header .info b { display:block; font-size:16px; color:#ff4da6; }
.post-img { width:100%; border-radius:8px; margin-top:10px; }
.inline { display:inline; } 
img.icon { width:20px; vertical-align:middle; }
.comentario { margin-left:20px; margin-top:5px; background:#fce4f2; padding:8px; border-radius:8px; font-size:13px; }
.sem-postagens { text-align:center; color:#666; font-style:italic; }

@media (max-width:900px){ .sidebar{display:none;} .main-wrap{margin-left:0;padding:20px;} }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="sidebar">
    <div class="brand">
        <img src="logo.png" alt="Logo">
        <b>PIXIE</b>
    </div>
    <nav>
        <a href="feed.php">Feed</a>
        <a href="perfil.php">Meu Perfil</a>
        <a href="todas_notificacoes.php">Notificações</a>
        <a href="chat.php">Mensagens</a>
        <a href="logout.php">Sair</a>
    </nav>
</div>

<div class="main-wrap">
<h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>

<div class="perfil-container">
    <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" class="foto-perfil">
    <p class="bio"><?php echo nl2br(htmlspecialchars($usuario['bio'] ?? '')); ?></p>

    <div class="info">
        <div><b><?php echo $dadosSeg['seguidores']; ?></b><br>Seguidores</div>
        <div><b><?php echo $dadosSeg['seguindo']; ?></b><br>Seguindo</div>
    </div>

    <button class="follow-btn" id="seguirBtn" data-id="<?php echo $idPerfil; ?>">
        <?php echo $jaSegue ? "Deixar de seguir" : "Seguir"; ?>
    </button>
</div>

<hr class="linha-divisoria">

<div class="postagens-container">
    <h3>Postagens de <?php echo htmlspecialchars($usuario['nome']); ?></h3>
    <?php
    $sqlPosts = $con->prepare("
        SELECT p.id, p.conteudo, p.imagem, p.criado_em,
        (SELECT COUNT(*) FROM curtidas WHERE postagem_id = p.id) AS total_curtidas
        FROM postagens p WHERE usuario_id = ? ORDER BY id DESC
    ");
    $sqlPosts->bind_param("i", $idPerfil);
    $sqlPosts->execute();
    $resPosts = $sqlPosts->get_result();

    if($resPosts->num_rows == 0){
        echo "<p class='sem-postagens'>Nenhuma postagem ainda.</p>";
    } else {
        while($post = $resPosts->fetch_assoc()){
            echo "<div class='post'>";
            echo "<div class='post-header'>";
            echo "<img src='".htmlspecialchars($usuario['foto_perfil'])."' class='post-user-img'>";
            echo "<div class='info'><b>".htmlspecialchars($usuario['nome'])."</b> <small>".date("d/m/Y H:i", strtotime($post['criado_em']))."</small></div>";
            echo "</div>";

            echo "<p>".nl2br(htmlspecialchars($post['conteudo']))."</p>";
            if(!empty($post['imagem'])){
                echo "<img src='".htmlspecialchars($post['imagem'])."' class='post-img'>";
            }

            // Curtir
            echo "<form class='inline' method='get' action='feed.php'>
                    <input type='hidden' name='curtir' value='".$post['id']."'>
                    <button type='submit' style='border:none;background:none;cursor:pointer;'>
                        <img src='curtidas.png' class='icon' alt='Curtir'>
                    </button>
                  </form> <span>(".$post['total_curtidas'].")</span>";

            //  comentário visível
            $sqlC = "SELECT c.conteudo,c.criado_em,u.nome FROM comentarios c
                     JOIN usuarios u ON c.usuario_id=u.id
                     WHERE c.postagem_id={$post['id']} ORDER BY c.id ASC";
            $resC = $con->query($sqlC);
            while($com=$resC->fetch_assoc()){
                $dataComent = date("d/m/Y H:i", strtotime($com['criado_em']));
                echo "<div class='comentario'><b>".htmlspecialchars($com['nome'])."</b> ($dataComent)<br>".htmlspecialchars($com['conteudo'])."</div>";
            }

            // Form para novo comentário
            echo "<form id='form-coment".$post['id']."' method='POST' action='feed.php' style='margin-top:10px;'>
                    <input type='hidden' name='acao' value='comentar'>
                    <input type='hidden' name='id_post' value='".$post['id']."'>
                    <input type='text' name='conteudo' placeholder='Escreva um comentário...' required>
                    <button type='submit'>Enviar</button>
                  </form>";

            echo "</div>";
        }
    }
    ?>
</div>
</div>

<script>
$('#seguirBtn').click(function(){
    const idSeguido = $(this).data('id');
    const botao = $(this);
    $.post('seguir.php', { id_seguido: idSeguido }, function(resp){
        if(resp.trim() === 'seguindo'){ botao.text('Deixar de seguir'); }
        else if(resp.trim() === 'nao_seguindo'){ botao.text('Seguir'); }
    });
});
</script>

</body>
</html>
