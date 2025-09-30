<?php
// Começar a sessão
session_start();

// Se não está logado, vai para o login
if (!isset($_SESSION['usuario'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Conectar no banco
include 'config.php';
include 'funcoes_estoque.php';

// Se o usuário clicou em "Registrar"
if ($_POST['add_movimento'] ?? false) {
    $pastel_escolhido = $_POST['pastel_id'];
    $tipo_movimento = $_POST['tipo'];
    $quantidade_movimento = $_POST['quantidade'];
    $observacoes_movimento = $_POST['observacoes'];
    $usuario_id = $_SESSION['usuario']['id'];
    
    // Inserir movimentação no banco
    $sql = "INSERT INTO movimentacoes (pastel_id, usuario_id, data_hora, tipo, quantidade, observacoes) 
            VALUES ($pastel_escolhido, $usuario_id, NOW(), '$tipo_movimento', $quantidade_movimento, '$observacoes_movimento')";
    $conn->query($sql);
    
    // Voltar para a mesma página
    header("Location: movimentacoes.php");
    exit();
}

// Buscar todas as pizzas ativas
$sql_pasteis = "SELECT * FROM pasteis WHERE ativo = 1 ORDER BY nome";
$resultado_pasteis = $conn->query($sql_pasteis);

// Buscar últimas 20 movimentações
$sql_movimentacoes = "SELECT m.*, p.nome as pastel_nome, u.nome as usuario_nome 
                      FROM movimentacoes m 
                      JOIN pasteis p ON m.pastel_id = p.id 
                      JOIN usuarios u ON m.usuario_id = u.id 
                      ORDER BY m.data_hora DESC LIMIT 20";
$resultado_movimentacoes = $conn->query($sql_movimentacoes);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações - Sistema Pastelaria</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Movimentações</h1>
        
        <a href="index.php" class="btn">Voltar</a>

        <div>
            <h3>Nova Movimentação</h3>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>pastel:</label>
                        <select name="pastel_id" required>
                            <option value="">Selecione um pastel</option>
                            <?php while($pastel = $resultado_pasteis->fetch_assoc()): 
                                $pastel_id = $pastel['id'];
                                
                                // Calcular estoque atual desta pizza
                                $sql_entradas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'entrada'";
                                $entradas = $conn->query($sql_entradas)->fetch_assoc();
                                $total_entradas = $entradas['total'] ? $entradas['total'] : 0;
                                
                                $sql_saidas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'saida'";
                                $saidas = $conn->query($sql_saidas)->fetch_assoc();
                                $total_saidas = $saidas['total'] ? $saidas['total'] : 0;
                                
                                $estoque_atual = $total_entradas - $total_saidas;
                            ?>
                                <option value="<?= $pastel['id'] ?>">
                                    <?= htmlspecialchars($pastel['nome']) ?> - <?= $estoque_atual ?>/<?= $pastel['estoque_minimo'] ?> - R$ <?= number_format($pastel['preco'], 2, ',', '.') ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo:</label>
                        <select name="tipo" required>
                            <option value="">Selecione</option>
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Quantidade:</label>
                        <input type="number" name="quantidade" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Observações:</label>
                        <textarea name="observacoes"></textarea>
                    </div>
                </div>
                <button type="submit" name="add_movimento" class="btn">Registrar</button>
            </form>
        </div>

        <h3>Histórico Recente</h3>
        <table>
            <tr><th>Data/Hora</th><th>Pastel</th><th>Usuário</th><th>Tipo</th><th>Qtd</th><th>Obs</th></tr>
            <?php while($movimentacao = $resultado_movimentacoes->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m H:i', strtotime($movimentacao['data_hora'])) ?></td>
                    <td><?= htmlspecialchars($movimentacao['pastel_nome']) ?></td>
                    <td><?= htmlspecialchars($movimentacao['usuario_nome']) ?></td>
                    <td class="<?= $movimentacao['tipo'] ?>"><?= $movimentacao['tipo'] == 'entrada' ? 'Entrada' : 'Saída' ?></td>
                    <td><?= $movimentacao['quantidade'] ?></td>
                    <td><?= htmlspecialchars($movimentacao['observacoes']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="historico.php" class="btn">Histórico Completo</a>
        </div>
    </div>
</body>
</html>
