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

// Pegar o que o usuário digitou na busca
$texto_busca = $_GET['busca'] ?? '';

// Se o usuário clicou em "Cadastrar"
if ($_POST['add'] ?? false) {
    $nome_pastel = $_POST['nome'];
    $ingredientes_pastel = $_POST['ingredientes'];
    $preco_pastel = $_POST['preco'];
    $categoria_pastel = $_POST['categoria'];
    $estoque_minimo_pastel = $_POST['estoque_minimo'];
    
    // Inserir nova pizza no banco
    $sql = "INSERT INTO pasteis (nome, ingredientes, preco, categoria, estoque_minimo) 
            VALUES ('$nome_pastel', '$ingredientes_pizza', $preco_pastel, '$categoria_pastel', $estoque_minimo_pastel)";
    $conn->query($sql);
    
    // Voltar para a mesma página
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
    <title>Gerenciar Pasteis - Sistema Pizzaria</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Gerenciar Pasteis</h1>

        <div>
            <form method="get" style="display: flex; gap: 10px;">
                <input name="busca" placeholder="Buscar..." value="<?= htmlspecialchars($texto_busca) ?>" style="flex: 1; padding: 8px;">
                <button type="submit" class="btn">Buscar</button>
                <a href="index.php" class="btn">Voltar</a>
            </form>
        </div>

        <table>
            <tr><th>Nome</th><th>Ingredientes</th><th>Preço</th><th>Tamanho</th><th>Categoria</th><th>Estoque</th><th>Ações</th></tr>
            <?php 
            // Mostrar cada pizza na tabela
            while($pastel = $resultado_pasteis->fetch_assoc()): 
                $pastel_id = $pastel['id'];
                
                // Calcular estoque atual deste pastel
                $sql_entradas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'entrada'";
                $entradas = $conn->query($sql_entradas)->fetch_assoc();
                $total_entradas = $entradas['total'] ? $entradas['total'] : 0;
                
                $sql_saidas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'saida'";
                $saidas = $conn->query($sql_saidas)->fetch_assoc();
                $total_saidas = $saidas['total'] ? $saidas['total'] : 0;
                
                $estoque_atual = $total_entradas - $total_saidas;
                $estoque_minimo = $pastel['estoque_minimo'];
                
                // Verificar se estoque está baixo
                $estoque_baixo = $estoque_atual <= $estoque_minimo;
            ?>
                <tr class="<?= $estoque_baixo ? 'estoque-baixo' : 'estoque-ok' ?>">
                    <td><span class="status-indicator <?= $estoque_baixo ? 'status-baixo' : 'status-ok' ?>"></span><?= htmlspecialchars($pizza['nome']) ?></td>
                    <td><?= htmlspecialchars($pastel['ingredientes']) ?></td>
                    <td>R$ <?= number_format($pastel['preco'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($pastel['tamanho']) ?></td>
                    <td><?= htmlspecialchars($pastel['categoria']) ?></td>
                    <td><strong><?= $estoque_atual ?></strong>/<?= $estoque_minimo ?><?= $estoque_baixo ? '<br><small>⚠️ Baixo!</small>' : '' ?></td>
                    <td>
                        <a href="editar_pastel.php?id=<?= $pizza['id'] ?>" class="btn" style="padding: 3px 8px; font-size: 11px;">Editar</a>
                        <a href="deletar_pastel.php?id=<?= $pizza['id'] ?>" class="btn" style="padding: 3px 8px; font-size: 11px;" onclick="return confirm('Excluir?')">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <br>

        <div>
            <h3>Adicionar Pastel:</h3>
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
