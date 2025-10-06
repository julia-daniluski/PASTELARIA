<?php
// Começar a sessão
session_start();

if (!isset($_SESSION['usuario_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Conectar no banco
include 'config.php';
include 'funcoes_estoque.php';

// Fallback para app_log caso não esteja definido em config.php
if (!function_exists('app_log')) {
    function app_log($message) {
        $date = date('Y-m-d H:i:s');
        $line = "[$date] " . (is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
        error_log('[pastelria] ' . $line);
    }
}

// Se o usuário clicou em "Registrar"
if (isset($_POST['add_movimento'])) {
    app_log(['route' => 'mov_add', 'post' => $_POST, 'user' => $_SESSION['usuario_id'] ?? null]);
    
    $pastel_escolhida = trim($_POST['pastel_id'] ?? '');
    $tipo_movimento = trim($_POST['tipo'] ?? '');
    $quantidade_movimento = trim($_POST['quantidade'] ?? '');
    $observacoes_movimento = trim($_POST['observacoes'] ?? '');
    $usuario_id = (int)($_SESSION['usuario_id'] ?? 0); // 🔧 CORRIGIDO AQUI

    $erros = array();
    if (!ctype_digit((string)$pastel_escolhida)) { $erros[] = 'pastel inválida.'; }
    if ($tipo_movimento !== 'entrada' && $tipo_movimento !== 'saida') { $erros[] = 'Tipo inválido.'; }
    if (!ctype_digit((string)$quantidade_movimento) || (int)$quantidade_movimento <= 0) { $erros[] = 'Quantidade inválida.'; }
    if ($usuario_id <= 0) { $erros[] = 'Usuário inválido.'; }

    if (empty($erros)) {
        // Para saída, validar estoque suficiente
        if ($tipo_movimento === 'saida') {
            $pastel_id = (int)$pastel_escolhida;
            $qtd = (int)$quantidade_movimento;
            $entradas = $conn->query("SELECT COALESCE(SUM(quantidade),0) AS total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'entrada'")->fetch_assoc()['total'];
            $saidas = $conn->query("SELECT COALESCE(SUM(quantidade),0) AS total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'saida'")->fetch_assoc()['total'];
            $estoque_atual = (int)$entradas - (int)$saidas;
            if ($qtd > $estoque_atual) {
                $erros[] = 'Quantidade de saída supera o estoque atual (' . $estoque_atual . ').';
                app_log(['route' => 'mov_add', 'status' => 'estoque_insuficiente', 'estoque' => $estoque_atual, 'qtd' => $qtd]);
            }
        }
    }

    if (empty($erros)) {
        $pastel_id = (int)$pastel_escolhida;
        $qtd = (int)$quantidade_movimento;
        $stmt = $conn->prepare("INSERT INTO movimentacoes (pastel_id, usuario_id, data_hora, tipo, quantidade, observacoes) VALUES (?, ?, NOW(), ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iisis', $pastel_id, $usuario_id, $tipo_movimento, $qtd, $observacoes_movimento);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Movimentação registrada com sucesso.';
                app_log(['route' => 'mov_add', 'status' => 'ok', 'insert_id' => $conn->insert_id]);
            } else {
                $_SESSION['flash_error'] = 'Erro ao registrar movimentação: ' . $stmt->error;
                app_log(['route' => 'mov_add', 'status' => 'fail', 'error' => $stmt->error]);
            }
            $stmt->close();
        } else {
            $_SESSION['flash_error'] = 'Erro ao preparar registro: ' . $conn->error;
            app_log(['route' => 'mov_add', 'status' => 'prepare_fail', 'error' => $conn->error]);
        }
    } else {
        $_SESSION['flash_error'] = implode(' ', $erros);
        app_log(['route' => 'mov_add', 'status' => 'validation_fail', 'errors' => $erros]);
    }

    header("Location: movimentacoes.php");
    exit();
}

// Buscar todas as pasteis ativas
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
    <title>Movimentações - Sistema pastelria</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Movimentações</h1>
        <?php if (!empty($_GET['debug'])): ?>
            <div class="alert">
                <strong>DEBUG</strong>
                <pre style="white-space:pre-wrap;overflow:auto;max-height:200px;">POST: <?php echo htmlspecialchars(print_r($_POST, true)); ?>
SESSION: <?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert success"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert error"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>
        
        <a href="index.php" class="btn">Voltar</a>

        <div>
            <h3>Nova Movimentação</h3>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>pastel:</label>
                        <select name="pastel_id" required>
                            <option value="">Selecione uma pastel</option>
                            <?php while($pastel = $resultado_pasteis->fetch_assoc()): 
                                $pastel_id = $pastel['id'];
                                
                                // Calcular estoque atual desta pastel
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
            <tr><th>Data/Hora</th><th>pastel</th><th>Usuário</th><th>Tipo</th><th>Qtd</th><th>Obs</th></tr>
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


