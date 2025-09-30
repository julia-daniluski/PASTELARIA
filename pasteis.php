<?php
// Mostrar erros (debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Começar a sessão
session_start();

// Se não está logado, vai para o login
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Conectar no banco
include 'config.php';
include 'funcoes_estoque.php';

// Pegar o que o usuário digitou na busca
$texto_busca = $_GET['busca'] ?? '';

// Se o usuário clicou em "Cadastrar"
if ($_POST['add'] ?? false) {
    $nome_pastel = $_POST['nome'];
    $ingredientes_pastel = $_POST['ingredientes'];
    $preco_pastel = $_POST['preco'];
    $categoria_pastel = $_POST['categoria'];
    $estoque_minimo_pastel = $_POST['estoque_minimo'];
    
    // Inserir novo pastel de forma segura
    $stmt = $conn->prepare("INSERT INTO pasteis (nome, ingredientes, preco, categoria, estoque_minimo) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $nome_pastel, $ingredientes_pastel, $preco_pastel, $categoria_pastel, $estoque_minimo_pastel);
    $stmt->execute();
    $stmt->close();
    
    // Voltar para a mesma página
    header("Location: pasteis.php");
    exit();
}

// Buscar pasteis no banco
if ($texto_busca) {
    $stmt = $conn->prepare("SELECT * FROM pasteis WHERE nome LIKE ? OR ingredientes LIKE ? ORDER BY nome");
    $like = "%" . $texto_busca . "%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM pasteis ORDER BY nome");
}
$stmt->execute();
$resultado_pasteis = $stmt->get_result();
$stmt->close();
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

        <div>
            <form method="get" style="display: flex; gap: 10px;">
                <input name="busca" placeholder="Buscar..." 
                       value="<?= htmlspecialchars($texto_busca) ?>" 
                       style="flex: 1; padding: 8px;">
                <button type="submit" class="btn">Buscar</button>
                <a href="index.php" class="btn">Voltar</a>
            </form>
        </div>

        <table>
            <tr><th>Nome</th><th>Ingredientes</th><th>Preço</th><th>Categoria</th><th>Estoque</th><th>Ações</th></tr>
            <?php while($pastel = $resultado_pasteis->fetch_assoc()): 
                $pastel_id = $pastel['id'];
                
                // Calcular estoque atual deste pastel
                $sql_entradas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = ? AND tipo = 'entrada'";
                $stmt = $conn->prepare($sql_entradas);
                $stmt->bind_param("i", $pastel_id);
                $stmt->execute();
                $entradas = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $total_entradas = $entradas['total'] ?? 0;
                
                $sql_saidas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = ? AND tipo = 'saida'";
                $stmt = $conn->prepare($sql_saidas);
                $stmt->bind_param("i", $pastel_id);
                $stmt->execute();
                $saidas = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $total_saidas = $saidas['total'] ?? 0;
                
                $estoque_atual = $total_entradas - $total_saidas;
                $estoque_minimo = $pastel['estoque_minimo'];
                $estoque_baixo = $estoque_atual <= $estoque_minimo;
            ?>
                <tr class="<?= $estoque_baixo ? 'estoque-baixo' : 'estoque-ok' ?>">
                    <td>
                        <span class="status-indicator <?= $estoque_baixo ? 'status-baixo' : 'status-ok' ?>"></span>
                        <?= htmlspecialchars($pastel['nome']) ?>
                    </td>
                    <td><?= htmlspecialchars($pastel['ingredientes']) ?></td>
                    <td>R$ <?= number_format($pastel['preco'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pastel['categoria']) ?></td>
                    <td>
                        <strong><?= $estoque_atual ?></strong>/<?= $estoque_minimo ?>
                        <?= $estoque_baixo ? '<br><small>⚠️ Baixo!</small>' : '' ?>
                    </td>
                    <td>
                        <a href="editar_pastel.php?id=<?= $pastel['id'] ?>" class="btn" 
                           style="padding: 3px 8px; font-size: 11px;">Editar</a>
                        <a href="deletar_pastel.php?id=<?= $pastel['id'] ?>" class="btn" 
                           style="padding: 3px 8px; font-size: 11px;" 
                           onclick="return confirm('Excluir?')">Excluir</a>
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
