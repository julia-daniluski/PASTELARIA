<?php
// Começar a sessão
session_start();

session_start();
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: login.php'); 
    exit(); 
}


// Conectar no banco
include 'config.php';

// Pegar os filtros que o usuário escolheu
$filtro_pastel = $_GET['pastel'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';

// Montar a consulta SQL baseada nos filtros
$sql = "SELECT m.id, p.nome AS pastel_nome, u.nome AS usuario_nome, m.tipo, m.quantidade, m.data_hora, m.observacoes 
        FROM movimentacoes m
        JOIN pasteis p ON m.pastel_id = p.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE 1=1";

// Se escolheu uma pastel específica
if ($filtro_pastel) {
    $sql .= " AND m.pastel_id = $filtro_pastel";
}

// Se escolheu um tipo específico
if ($filtro_tipo) {
    $sql .= " AND m.tipo = '$filtro_tipo'";
}

$sql .= " ORDER BY m.data_hora DESC";

// Executar a consulta
$resultado_movimentacoes = $conn->query($sql);

// Buscar todas as pasteis para o filtro
$sql_pasteis = "SELECT * FROM pasteis ORDER BY nome";
$resultado_pasteis = $conn->query($sql_pasteis);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - Sistema pastelazzo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <img src="https://i.pinimg.com/originals/28/12/d7/2812d78d2f192fc317da48602c93666c.png" alt="pastel sorrindo" class="logo">
        <h1>Histórico Pastelazzo</h1>
        
        <a href="index.php" class="btn">Voltar</a>

        <div>
            <h3>Filtros</h3>
            <form method="get">
                <div class="form-row">
                    <div class="form-group">
                        <label>pastel:</label>
                        <select name="pastel">
                            <option value="">Todas</option>
                            <?php while($pastel = $resultado_pasteis->fetch_assoc()): ?>
                                <option value="<?= $pastel['id'] ?>" <?= $filtro_pastel == $pastel['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pastel['nome']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo:</label>
                        <select name="tipo">
                            <option value="">Todos</option>
                            <option value="entrada" <?= $filtro_tipo == 'entrada' ? 'selected' : '' ?>>Entrada</option>
                            <option value="saida" <?= $filtro_tipo == 'saida' ? 'selected' : '' ?>>Saída</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">Filtrar</button>
                <a href="historico.php" class="btn">Limpar</a>
            </form>
        </div>

        <table>
            <tr><th>ID</th><th>Data/Hora</th><th>pastel</th><th>Usuário</th><th>Tipo</th><th>Qtd</th><th>Obs</th></tr>
            <?php 
            // Verificar se tem movimentações
            if ($resultado_movimentacoes->num_rows > 0): 
                // Mostrar cada movimentação
                while($movimentacao = $resultado_movimentacoes->fetch_assoc()): 
            ?>
                <tr>
                    <td><?= $movimentacao['id'] ?></td>
                    <td><?= date('d/m H:i', strtotime($movimentacao['data_hora'])) ?></td>
                    <td><?= htmlspecialchars($movimentacao['pastel_nome']) ?></td>
                    <td><?= htmlspecialchars($movimentacao['usuario_nome']) ?></td>
                    <td class="<?= $movimentacao['tipo'] ?>"><?= $movimentacao['tipo'] == 'entrada' ? 'Entrada' : 'Saída' ?></td>
                    <td><?= $movimentacao['quantidade'] ?></td>
                    <td><?= htmlspecialchars($movimentacao['observacoes']) ?></td>
                </tr>
            <?php 
                endwhile; 
            else: 
            ?>
                <tr><td colspan="7" style="text-align: center; padding: 20px;">Nenhuma movimentação encontrada.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
