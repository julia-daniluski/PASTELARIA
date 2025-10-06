<?php
// Mostrar erros no navegador (útil para desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Se não está logado, vai para o login
if (!isset($_SESSION['usuario_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// Conectar ao banco
include 'config.php';

// Pegar o ID do pastel que queremos editar
$pastel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar os dados do pastel
$sql = "SELECT * FROM pasteis WHERE id = $pastel_id";
$resultado = $conn->query($sql);
$pastel = $resultado ? $resultado->fetch_assoc() : null;

// Se não encontrou o pastel, voltar para a lista
if (!$pastel) { 
    header('Location: pasteis.php'); 
    exit(); 
}

// Se o usuário clicou em "Atualizar"
if (isset($_POST['update'])) {
    $nome_pastel = trim($_POST['nome'] ?? '');
    $ingredientes_pastel = trim($_POST['ingredientes'] ?? '');
    $preco_pastel = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
    $categoria_pastel = trim($_POST['categoria'] ?? '');
    $estoque_minimo_pastel = trim($_POST['estoque_minimo'] ?? '0');

    $erros = [];

    if ($nome_pastel === '') $erros[] = 'Informe o nome.';
    if ($ingredientes_pastel === '') $erros[] = 'Informe os ingredientes.';
    if ($categoria_pastel === '') $erros[] = 'Informe a categoria.';
    if (!is_numeric($preco_pastel)) $erros[] = 'Preço inválido.';
    if (!ctype_digit((string)$estoque_minimo_pastel)) $erros[] = 'Estoque mínimo inválido.';

    if (empty($erros)) {
        $preco = (float)$preco_pastel;
        $estoque_min = (int)$estoque_minimo_pastel;

        $stmt = $conn->prepare("UPDATE pasteis 
            SET nome = ?, ingredientes = ?, preco = ?, categoria = ?, estoque_minimo = ? 
            WHERE id = ?");

        if ($stmt) {
            // Tipos: s = string, d = double, i = integer
            $stmt->bind_param('ssdsii', 
                $nome_pastel, 
                $ingredientes_pastel, 
                $preco, 
                $categoria_pastel, 
                $estoque_min, 
                $pastel_id
            );

            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'Pastel atualizado com sucesso.';
            } else {
                $_SESSION['flash_error'] = 'Erro ao atualizar pastel: ' . $stmt->error;
            }

            $stmt->close();
        } else {
            $_SESSION['flash_error'] = 'Erro ao preparar atualização: ' . $conn->error;
        }

        header("Location: pasteis.php");
        exit();
    } else {
        $_SESSION['flash_error'] = implode(' ', $erros);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar pastel - Sistema pastelazzo</title>
    <link rel="shortcut icon" href="img/favicon.png" type="image/x-icon"> 
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Editar pastel</h1>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" required value="<?= htmlspecialchars($pastel['nome']) ?>">
                </div>
                <div class="form-group">
                    <label>Preço (R$):</label>
                    <input type="number" name="preco" step="0.01" required value="<?= $pastel['preco'] ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Categoria:</label>
                    <input type="text" name="categoria" required value="<?= htmlspecialchars($pastel['categoria']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Estoque Mínimo:</label>
                    <input type="number" name="estoque_minimo" min="0" required value="<?= $pastel['estoque_minimo'] ?>">
                </div>
                <div class="form-group">
                    <label>Ingredientes:</label>
                    <textarea name="ingredientes" required><?= htmlspecialchars($pastel['ingredientes']) ?></textarea>
                </div>
            </div>
            <button type="submit" name="update" class="btn">Atualizar</button>
            <a href="pasteis.php" class="btn">Cancelar</a>
        </form>
    </div>
</body>
</html>
