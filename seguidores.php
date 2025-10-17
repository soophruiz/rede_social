<?php
session_start();
include "conexao.php";

if(!isset($_SESSION['id'])){
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['id'];

// Dados do usuário
$stmtUser = $con->prepare("SELECT nome, foto_perfil FROM usuarios WHERE id = ?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

// Lista de seguidores
$sql = "SELECT u.id, u.nome, u.foto_perfil
        FROM seguidores s
        JOIN usuarios u ON s.seguidor_id = u.id
        WHERE s.seguido_id = ?
        ORDER BY s.data_seguimento DESC";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Seguidores</title>
<style>
body{font-family:'Poppins',sans-serif;background:#f4f4f8;margin:0;padding:0;color:#333;}
.sidebar{position:fixed;left:0;top:0;height:100vh;width:240px;background:#fff;border-right:1px solid rgba(0,0,0,0.05);padding:25px 18px;box-shadow:0 6px 20px rgba(0,0,0,0.06);}
.sidebar .brand{display:flex;align-items:center;gap:10px;margin-bottom:30px;}
.sidebar .brand img{height:44px;}
.sidebar .brand b{color:#ff4da6;font-size:22px;}
.sidebar nav a{display:flex;align-items:center;gap:12px;text-decoration:none;color:#555;padding:12px 14px;border-radius:14px;margin-bottom:12px;font-weight:600;transition:0.2s;}
.sidebar nav a:hover{background:linear-gradient(90deg,rgba(255,77,166,0.12),rgba(255,183,217,0.06));color:#ff4da6;}
.sidebar nav a.ativo{background:linear-gradient(90deg,rgba(255,77,166,0.2),rgba(255,183,217,0.1));color:#ff4da6;}
.main{margin-left:260px;padding:30px;max-width:800px;}
h2{text-align:center;color:#ff4da6;margin-bottom:25px;}
.card{background:white;border-radius:16px;padding:20px;margin-bottom:15px;display:flex;align-items:center;gap:15px;box-shadow:0 6px 20px rgba(0,0,0,0.06);}
.card img{width:60px;height:60px;border-radius:50%;object-fit:cover;}
.card b{color:#ff4da6;font-size:16px;}
@media(max-width:900px){.sidebar{display:none;}.main{margin-left:0;padding:20px;}}
</style>
</head>
<body>

<div class="sidebar">
  <div class="brand">
    <img src="logo.png"><b>PIXIE</b>
  </div>
  <nav>
    <a href="feed.php">Feed</a>
    <a href="perfil.php">Perfil</a>
    <a href="seguidores.php" class="ativo">Seguidores</a>
    <a href="seguindo.php">Seguindo</a>
    <a href="chat.php">Mensagens</a>
    <a href="todas_notificacoes.php">Notificações</a>
    <a href="configuracoes.php">Configurações</a>
    <a href="logout.php">Sair</a>
  </nav>
</div>

<div class="main">
<h2>Seus Seguidores</h2>
<?php
if($result->num_rows == 0){
    echo "<p style='text-align:center;'>Você ainda não tem seguidores.</p>";
} else {
    while($row = $result->fetch_assoc()){
    echo "<div class='card'>";
    echo "<img src='".(!empty($row['foto_perfil'])?$row['foto_perfil']:'perfil/default.png')."'>";
    echo "<b>".htmlspecialchars($row['nome'])."</b>";
    
    // Botões
    echo "<div style='margin-left:auto; display:flex; gap:10px;'>";
    echo "<form method='post' action='deixar_de_seguir.php' style='margin:0;'>
            <input type='hidden' name='seguir_id' value='".$row['id']."'>
            <button type='submit' style='padding:6px 12px; border:none; border-radius:8px; background:#ff4da6; color:white; cursor:pointer;'>Remover seguidor</button>
          </form>";
    echo "<a href='perfil_pesquisado.php?id=".$row['id']."' style='padding:6px 12px; border-radius:8px; background:#eee; color:#333; text-decoration:none; display:flex; align-items:center;'>Ver perfil</a>";
    echo "</div>";

    echo "</div>";
}
}
?>
</div>
</body>
</html>
