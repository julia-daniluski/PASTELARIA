<?php
// Inicia a sessão para verificar se o usuário está autenticado
session_start();

// Se não houver usuário logado, redireciona para a página de login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Inclui o arquivo de configuração para conexão com o banco de dados
include 'config.php';

// Recebe o ID da pastel via GET
$id = $_GET['id'] ?? 0;

if ($id > 0) {
    // Prepara uma consulta segura para deletar a pastel
    $stmt = $conn->prepare("DELETE FROM pasteis WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Redireciona para a lista de pasteis
header("Location: pasteis.php");
exit();
?>
