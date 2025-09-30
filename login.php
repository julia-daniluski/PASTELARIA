<?php
session_start();

// Se já está logado, redireciona para o menu
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

$mensagem_erro = "";

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include 'config.php';

    $nome_digitado  = $_POST['nome'];
    $senha_digitada = $_POST['senha'];

    $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE nome = ?");
    $stmt->bind_param("s", $nome_digitado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $dados_usuario = $resultado->fetch_assoc();

        if (password_verify($senha_digitada, $dados_usuario['senha'])) {
            // Armazena apenas dados essenciais na sessão
            $_SESSION['usuario_id'] = $dados_usuario['id'];
            $_SESSION['usuario_nome'] = $dados_usuario['nome'];
            header("Location: index.php");
            exit();
        } else {
            $mensagem_erro = "Usuário ou senha inválidos.";
        }
    } else {
        $mensagem_erro = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Pastelaria</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/favicon.png" type="image/x-icon"> 
</head>
<body class="login-page">
    <div class="login-container">
        <img src="https://i.pinimg.com/originals/28/12/d7/2812d78d2f192fc317da48602c93666c.png" alt="pastel sorrindo" class="logo">
        <h1>Sistema Pastelazzo</h1>
        <form method="post">
            <div class="form-group">
                <label>Usuário:</label>
                <input type="text" name="nome" required autofocus>
            </div>
            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="senha" required>
            </div>
            <button type="submit">Entrar</button>
            <?php if ($mensagem_erro): ?>
                <div class="erro"><?= htmlspecialchars($mensagem_erro) ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
