<?php
session_start();
include "conexao.php";
if(!isset($_SESSION['id'])) { header("Location: index.php"); exit; }

$usuario_id = $_SESSION['id'];
if(isset($_POST['seguir_id'])) {
    $seguir_id = intval($_POST['seguir_id']);
    $check = $con->prepare("SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $check->bind_param("ii", $usuario_id, $seguir_id);
    $check->execute();
    $res = $check->get_result();
    if($res->num_rows == 0) {
        $stmt = $con->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $usuario_id, $seguir_id);
        $stmt->execute();
    }
}
header("Location: feed.php");
exit;
?>
