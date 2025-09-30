<?php
// Função para pegar pasteis com estoque baixo
function getPasteisEstoqueBaixo($conn) {
    // Buscar todas as pasteis
    $sql = "SELECT * FROM pasteis WHERE ativo = 1";
    $resultado = $conn->query($sql);
    
    $pasteis_estoque_baixo = array();
    
    // Para cada pastel, calcular o estoque
    while ($pastel = $resultado->fetch_assoc()) {
        $pastel_id = $pastel['id'];
        
        // Somar todas as entradas
        $sql_entradas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'entrada'";
        $entradas = $conn->query($sql_entradas)->fetch_assoc();
        $total_entradas = $entradas['total'] ? $entradas['total'] : 0;
        
        // Somar todas as saídas
        $sql_saidas = "SELECT SUM(quantidade) as total FROM movimentacoes WHERE pastel_id = $pastel_id AND tipo = 'saida'";
        $saidas = $conn->query($sql_saidas)->fetch_assoc();
        $total_saidas = $saidas['total'] ? $saidas['total'] : 0;
        
        // Calcular estoque atual
        $estoque_atual = $total_entradas - $total_saidas;
        
        // Se estoque está baixo, adicionar na lista
        if ($estoque_atual <= $pastel['estoque_minimo']) {
            $pastel['estoque_atual'] = $estoque_atual;
            $pasteis_estoque_baixo[] = $pastel;
        }
    }
    
    return $pasteis_estoque_baixo;
}

// Função para gerar alerta de estoque baixo
function gerarAlertaEstoque($conn) {
    $pasteis_com_estoque_baixo = getPasteisEstoqueBaixo($conn);
    
    // Se não tem pizzas com estoque baixo
    if (empty($pasteis_com_estoque_baixo)) {
        return null;
    }
    
    // Se tem pizzas com estoque baixo, criar o alerta
    $quantidade_pasteis = count($pasteis_com_estoque_baixo);
    
    return array(
        'quantidade' => $quantidade_pasteis,
        'pasteis' => $pasteis_com_estoque_baixo,
        'mensagem' => $quantidade_pasteis . " pasteis com estoque baixo!"
    );
}
?>