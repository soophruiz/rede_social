<?php
include "conexao.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $foto = "perfil/default.png";
    if (!empty($_FILES['foto_perfil']['name'])) {
        if ($_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $fotoDir = __DIR__ . "/perfil/";
            if (!is_dir($fotoDir)) {
                mkdir($fotoDir, 0777, true);
            }
            $nomeArquivo = uniqid() . "." . $ext;
            $foto = "perfil/" . $nomeArquivo;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $fotoDir . $nomeArquivo);
        } else {
            echo "Erro ao enviar a foto: " . $_FILES['foto_perfil']['error'];
        }
    }

    $stmt = $con->prepare("INSERT INTO usuarios (nome, email, senha, foto_perfil) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nome, $email, $senha, $foto);

    if ($stmt->execute()) {
        header("Location: index.php"); 
        exit;
    } else {
        echo "Erro: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffe6f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #fff0f5;
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            width: 350px;
        }

        h2 {
            text-align: center;
            color: #ff4da6;
            margin-bottom: 30px;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form input[type="file"] {
            width: 100%;
            padding: 12px 10px;
            margin-bottom: 20px;
            border: 1px solid #ffb3d9;
            border-radius: 8px;
            box-sizing: border-box;
        }

        form button {
            width: 100%;
            padding: 12px;
            background-color: #ff4da6;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        form button:hover {
            background-color: #ff1a75;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #ff4da6;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cadastro de Usuário</h2>
        <form method="POST" enctype="multipart/form-data">
            Nome: <input type="text" name="nome" required><br>
            Email: <input type="email" name="email" required><br>
            Senha: <input type="password" name="senha" required><br>
            Foto: <input type="file" name="foto_perfil"><br>
            <button type="submit">Cadastrar</button>
        </form>
        <a href="index.php">Login</a>
    </div>
</body>
</html>
