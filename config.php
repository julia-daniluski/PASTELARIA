<?php
// Configuração da conexão com o banco de dados MySQL para a Pizzaria

$servername = "localhost"; // Nome do servidor do banco (localmente localhost)
$username = "root";        // Usuário do banco de dados
$password = "";            // Senha do banco (vazia no XAMPP padrão)
$dbname = "pastelaria_db";   // Nome do banco de dados da pizzaria

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definição do charset para evitar problemas com acentuação
$conn->set_charset("utf8");
?>
