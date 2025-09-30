<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Se não está logado, vai para o login
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Conectar no banco
include 'config.php';
include 'funcoes_estoque.php';

// Verificar se tem estoque baixo
$alerta_estoque = gerarAlertaEstoque($conn);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Pastelaria</title>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="img/favicon.png" type="image/x-icon"> 
</head>
<body>
    <div class="container">
        <img src="https://i.pinimg.com/originals/28/12/d7/2812d78d2f192fc317da48602c93666c.png" alt="pastel sorrindo" class="logo">
        <h1>Sistema Pastelazzo</h1>        
        <div class="welcome">
            Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!
        </div>

        <?php if ($alerta_estoque): ?>
            <div class="alert">
                <h3>Estoque Baixo!</h3>
                <ul>
                    <?php foreach ($alerta_estoque['pasteis'] ?? [] as $pastel): ?>
                        <li><?= htmlspecialchars($pastel['nome']) ?> - <?= $pastel['estoque_atual'] ?>/<?= $pastel['estoque_minimo'] ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <a href="movimentacoes.php" class="btn btn-link">Registrar Movimentações</a>
                </p>
            </div>
        <?php endif; ?>

        <div class="menu-grid">
            <a href="pasteis.php" class="menu-item btn btn-link">
                <h3>Pasteis</h3>
                <p>Gerenciar cardápio</p>
            </a>
            <a href="movimentacoes.php" class="menu-item btn btn-link">
                <h3>Movimentações</h3>
                <p>Entrada e saída</p>
            </a>
            <a href="historico.php" class="menu-item btn btn-link">
                <h3>Histórico</h3>
                <p>Ver movimentações</p>
            </a>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="logout.php" class="btn btn-link">Sair</a>
        </div>
    </div>
</body>
</html>
