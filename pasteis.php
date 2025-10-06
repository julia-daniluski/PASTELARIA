<?php
// Iniciar sessão apenas uma vez
session_start();

// Verificar login
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Conectar no banco
include 'config.php';
include 'funcoes_estoque.php';

// Fallback para app_log caso não esteja definido
if (!function_exists('app_log')) {
    function app_log($message) {
        $date = date('Y-m-d H:i:s');
        $line = "[$date] " . (is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
        error_log('[pastelaria] ' . $line);
    }
}

// Buscar texto digitado na pesquisa
$texto_busca = $_GET['busca'] ?? '';

// Se o usuário clicou em "Cadastrar"
if (isset($_POST['add'])) {
    app_log(['route' => 'pasteis_add', 'post' => $_POST]);
    $nome_pastel = trim($_POST['nome'] ?? '');
    $ingredientes_pastel = trim($_POST['ingredientes'] ?? '');
    $preco_pastel = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
    $categoria_pastel = trim($_POST['categoria'] ?? '');
    $estoque_minimo_pastel = trim($_POST['estoque_minimo'] ?? '0');

    $erros = [];
    if ($nome_pastel === '') { $erros[] = 'Informe o nome.'; }
    if ($ingredientes_pastel === '') { $erros[] = 'Informe os ingredientes.'; }
    if ($categoria_pastel === '') { $erros[] = 'Informe a categoria.'; }
    if (!is_numeric($preco_pastel)) { $erros[] = 'Preço inválido.'; }
    if (!ctype_digit((string)$estoque_minimo_pastel)) { $erros[] = 'Estoque mínimo inválido.'; }

    if (empty($erros)) {
        $preco = (float)$preco_pastel;
        $estoque_min = (int)$estoque_minimo_pastel;

        // ✅ Corrigido: removido o campo "tamanho"
        $stmt = $conn->prepare("INSERT INTO pasteis (nome, ingredientes, preco, categoria, estoque_minimo, ativo) VALUES (?, ?, ?, ?, ?, 1)");
        if ($stmt) {
            $stmt->bind_param('ssdsi', $nome_pastel, $ingredientes_pastel, $preco, $categoria_pastel, $estoque_min);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Pastel cadastrado com sucesso.';
                app_log(['route' => 'pasteis_add', 'status' => 'ok', 'insert_id' => $conn->insert_id]);
            } else {
                $_SESSION['flash_error'] = 'Erro ao cadastrar pastel: ' . $stmt->error;
                app_log(['route' => 'pasteis_add', 'status' => 'fail', 'error' => $stmt->error]);
            }
            $stmt->close();
        } else {
            $_SESSION['flash_error'] = 'Erro ao preparar inserção: ' . $conn->error;
            app_log(['route' => 'pasteis_add', 'status' => 'prepare_fail', 'error' => $conn->error]);
        }
    } else {
        $_SESSION['flash_error'] = implode(' ', $erros);
        app_log(['route' => 'pasteis_add', 'status' => 'validation_fail', 'errors' => $erros]);
    }

    header("Location: pasteis.php");
    exit();
}

// Buscar pasteis no banco
if ($texto_busca) {
    $sql = "SELECT * FROM pasteis WHERE nome LIKE '%$texto_busca%' OR ingredientes LIKE '%$texto_busca%' ORDER BY nome";
} else {
    $sql = "SELECT * FROM pasteis ORDER BY nome";
}
$resultado_pasteis = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pasteis - Sistema Pastelaria</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Gerenciar Pasteis</h1>

    <?php if (!empty($_GET['debug'])): ?>
        <div class="alert">
            <strong>DEBUG</strong>
            <pre style="white-space:pre-wrap;overflow:auto;max-height:200px;">
POST: <?php echo htmlspecialchars(print_r($_POST, true)); ?>
SESSION: <?php echo htmlspecialchars(print_r($_SESSION, true)); ?>
            </pre>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert success"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert error"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div>
        <form method="get" style="display: flex; gap: 10px;">
            <input name="busca" placeholder="Buscar..." value="<?= htmlspecialchars($texto_busca) ?>" style="flex: 1; padding: 8px;">
            <button type="submit" class="btn">Buscar</button>
            <a href="index.php" class="btn">Voltar</a>
        </form>
    </div>

    <table>
        <tr><th>Nome</th><th>Ingredientes</th><th>Preço</th><th>Categoria</th><th>Estoque</th><th>Ações</th></tr>
        <?php while($pastel = $resultado_pasteis->fetch_assoc()): 
            $pastel_id = $pastel['id'];
            $sql_entradas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'entrada'";
            $entradas = $conn->query($sql_entradas)->fetch_assoc();
            $total_entradas = $entradas['total'] ?? 0;

            $sql_saidas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'saida'";
            $saidas = $conn->query($sql_saidas)->fetch_assoc();
            $total_saidas = $saidas['total'] ?? 0;

            $estoque_atual = $total_entradas - $total_saidas;
            $estoque_minimo = $pastel['estoque_minimo'];
            $estoque_baixo = $estoque_atual <= $estoque_minimo;
        ?>
        <tr class="<?= $estoque_baixo ? 'estoque-baixo' : 'estoque-ok' ?>">
            <td><span class="status-indicator <?= $estoque_baixo ? 'status-baixo' : 'status-ok' ?>"></span><?= htmlspecialchars($pastel['nome']) ?></td>
            <td><?= htmlspecialchars($pastel['ingredientes']) ?></td>
            <td>R$ <?= number_format($pastel['preco'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($pastel['categoria']) ?></td>
            <td><strong><?= $estoque_atual ?></strong>/<?= $estoque_minimo ?><?= $estoque_baixo ? '<br><small>⚠️ Baixo!</small>' : '' ?></td>
            <td>
                <a href="editar_pastel.php?id=<?= $pastel['id'] ?>" class="btn" style="padding: 3px 8px; font-size: 11px;">Editar</a>
                <a href="deletar_pastel.php?id=<?= $pastel['id'] ?>" class="btn" style="padding: 3px 8px; font-size: 11px;" onclick="return confirm('Excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <br>

    <div>
        <h3>Adicionar Pastel</h3>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Preço:</label>
                    <input type="number" name="preco" step="0.01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Categoria:</label>
                    <input type="text" name="categoria" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Estoque Mínimo:</label>
                    <input type="number" name="estoque_minimo" min="0" value="5" required>
                </div>
                <div class="form-group">
                    <label>Ingredientes:</label>
                    <textarea name="ingredientes" required></textarea>
                </div>
            </div>
            <button type="submit" name="add" class="btn">Cadastrar</button>
        </form>
    </div>

    <div style="text-align: center; margin-top: 15px;">
        <a href="movimentacoes.php" class="btn">Movimentações</a>
    </div>
</div>
</body>
</html>
