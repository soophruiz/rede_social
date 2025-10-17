<?php
session_start();
include "conexao.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email='$email'";
    $res = mysqli_query($con,$sql);
    $user = mysqli_fetch_assoc($res);

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['id'] = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        header("Location: feed.php");
        exit;
    } else {
        $erro = "Email ou senha incorretos!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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

        form input[type="email"],
        form input[type="password"] {
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

        .erro {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if(isset($erro)) echo "<p class='erro'>$erro</p>"; ?>
        <form method="POST">
            Email: <input type="email" name="email" required><br>
            Senha: <input type="password" name="senha" required><br>
            <button type="submit">Entrar</button>
        </form>
        <a href="cadastro.php">Cadastrar-se</a>
    </div>
</body>
</html>
