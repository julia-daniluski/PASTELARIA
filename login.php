<?php
// Iniciar a sessão
session_start();

// Se já está logado, redireciona para o menu
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// Variável para mensagem de erro
$mensagem_erro = "";

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Conectar no banco
    include 'config.php';

    // Pegar os dados digitados
    $nome_digitado  = $_POST['nome'];
    $senha_digitada = $_POST['senha'];

    // Usar prepared statements para evitar SQL Injection
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE nome = ?");
    $stmt->bind_param("s", $nome_digitado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Se encontrou o usuário
    if ($resultado->num_rows > 0) {
        $dados_usuario = $resultado->fetch_assoc();

        // Verifica a senha (se estiver usando hash no banco)
        if (password_verify($senha_digitada, $dados_usuario['senha'])) {
            $_SESSION['usuario'] = $dados_usuario;
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
</head>
<body class="login-page">
    <div class="login-container">
        <h1>Sistema Pastelaria</h1>
        <form method="post">
            <div class="form-group">
                <label>Usuário:</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Senha:</label>
                <input type="password" name="senha" required>
            </div>
            <button type="submit">Entrar</button>
            <?php if ($mensagem_erro): ?>
                <div class="erro"><?= $mensagem_erro ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
