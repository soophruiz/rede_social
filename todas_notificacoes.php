<?php
session_start();
include "conexao.php";

if(!isset($_SESSION['id'])){
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['id'];

if(isset($_GET['marcar_lidas']) && $_GET['marcar_lidas'] == 1){
    $stmt = $con->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
}

$sql = "SELECT n.*, u.nome 
        FROM notificacoes n 
        JOIN usuarios u ON n.remetente_id = u.id 
        WHERE n.usuario_id = ?
        ORDER BY n.data_hora DESC";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

$notificacoes = [];
while($n = $res->fetch_assoc()){
    $msg = '';
    if($n['tipo'] == 'comentario') $msg = "{$n['nome']} comentou em sua postagem.";
    if($n['tipo'] == 'mensagem') $msg = "{$n['nome']} enviou uma mensagem.";
    if($n['tipo'] == 'curtida') $msg = "{$n['nome']} curtiu sua postagem.";
    $notificacoes[] = [
        'id' => $n['id'],
        'msg' => $msg,
        'data' => date("d/m/Y H:i", strtotime($n['data_hora'])),
        'lida' => $n['lida']
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Notificações</title>
<style>
body {
    font-family: 'Poppins', Arial, sans-serif;
    background: #f4f4f8;
    margin: 0;
    padding: 0;
    color: #333;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 240px;
    background: #fff;
    border-right: 1px solid rgba(0,0,0,0.05);
    padding: 25px 18px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    z-index: 2000;
}
.sidebar .brand {
    display:flex; align-items:center; gap:10px; margin-bottom:30px;
}
.sidebar .brand img{ height:44px; }
.sidebar .brand b{ color:#ff4da6; font-size:22px; letter-spacing:1px; }
.sidebar nav a {
    display:flex; align-items:center; gap:12px; text-decoration:none;
    color:#555; padding:12px 14px; border-radius:14px; margin-bottom:12px;
    font-weight:600;
    transition:0.2s;
}
.sidebar nav a:hover { 
    background: linear-gradient(90deg, rgba(255,77,166,0.12), rgba(255,183,217,0.06)); 
    color:#ff4da6; 
}
.sidebar nav a.ativo { 
    background: linear-gradient(90deg, rgba(255,77,166,0.2), rgba(255,183,217,0.1)); 
    color:#ff4da6; 
}

.main-wrap {
    margin-left: 260px;
    padding: 30px 24px;
    max-width: 750px;
}

h2 {
    color:#ff4da6;
    font-size:26px;
    margin-bottom:20px;
    text-align:center;
}
.botao {
    display:inline-block;
    background:#ff4da6;
    color:white;
    padding:12px 20px;
    border-radius:14px;
    text-decoration:none;
    font-weight:600;
    transition:0.3s;
    margin-bottom:25px;
}
.botao:hover {
    background:#ff1a75;
}
.card {
    background:#fff;
    padding:25px 28px;
    border-radius:18px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    transition:0.3s;
}

.notificacao {
    padding:15px 18px;
    margin:12px 0;
    border-left:6px solid #ff4da6;
    border-radius:12px;
    background:#fff5f9;
    transition:0.2s;
}
.notificacao:hover {
    background:#ffe6f2;
}
.notificacao.lida {
    opacity:0.7;
    border-left:6px solid #ffb3d9;
}
.notificacao strong {
    display:block;
    font-weight:600;
    font-size:15px;
    margin-bottom:5px;
}
.notificacao small {
    color:#888;
    font-size:13px;
}
.sem-notificacao {
    text-align:center;
    color:#666;
    font-size:15px;
    padding:30px 0;
}

@media (max-width:900px){
    .sidebar { display:none; }
    .main-wrap { margin-left:0; padding:20px; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="brand">
        <img src="logo.png" alt="Logo">
        <b>PIXIE</b>
    </div>
    <nav>
        <a href="feed.php">Feed</a>
        <a href="perfil.php">Perfil</a>
        <a href="chat.php">Mensagens</a>
        <a href="todas_notificacoes.php" class="ativo">Notificações</a>
        <a href="logout.php">Sair</a>
    </nav>
</div>

<div class="main-wrap">
    <h2>Notificações</h2>
    <a class="botao" href="todas_notificacoes.php?marcar_lidas=1">Marcar todas como lidas</a>

    <div class="card">
        <?php if(empty($notificacoes)): ?>
            <p class="sem-notificacao">Nenhuma notificação por enquanto 💌</p>
        <?php else: ?>
            <?php foreach($notificacoes as $n): ?>
                <div class="notificacao <?php echo $n['lida'] ? 'lida' : ''; ?>">
                    <strong><?php echo htmlspecialchars($n['msg']); ?></strong>
                    <small><?php echo $n['data']; ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
