<?php
session_start();
include "conexao.php";

if(!isset($_SESSION['id'])) exit;

$usuario_id = $_SESSION['id'];

$sql = "SELECT n.*, u.nome 
        FROM notificacoes n 
        JOIN usuarios u ON n.remetente_id = u.id 
        WHERE n.usuario_id = ? AND n.lida = 0 
        ORDER BY n.data_hora DESC";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

$notificacoes = [];
while($n = $res->fetch_assoc()) {
    $msg = "";
    if($n['tipo'] == 'comentario') $msg = "{$n['nome']} comentou em sua postagem.";
    if($n['tipo'] == 'mensagem') $msg = "{$n['nome']} enviou uma mensagem.";
    if($n['tipo'] == 'curtida') $msg = "{$n['nome']} curtiu sua postagem.";
    $notificacoes[] = [
        'id' => $n['id'],
        'msg' => $msg,
        'data' => $n['data_hora']
    ];
}

$con->query("UPDATE notificacoes SET lida = 1 WHERE usuario_id = $usuario_id");

echo json_encode($notificacoes);
?>
