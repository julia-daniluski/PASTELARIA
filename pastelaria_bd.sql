CREATE DATABASE pastelaria;
USE pastelaria;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  senha VARCHAR(255) NOT NULL
);

CREATE TABLE pasteis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  ingredientes TEXT NOT NULL,
  preco DECIMAL(8,2) NOT NULL,
  categoria VARCHAR(50) NOT NULL,
  estoque_minimo INT DEFAULT 5,
  ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE movimentacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pastel_id INT NOT NULL,
  usuario_id INT NOT NULL,
  data_hora DATETIME NOT NULL,
  tipo ENUM('entrada','saida') NOT NULL,
  quantidade INT NOT NULL,
  observacoes TEXT,
  FOREIGN KEY (pastel_id) REFERENCES pasteis(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

INSERT INTO usuarios (nome, senha) VALUES
('Adm', 'adm123'),
('Gerente', 'gerente123'),
('Atendente', 'atendente123');

INSERT INTO pasteis (nome, ingredientes, preco, categoria) VALUES
('Margherita', 'mussarela, manjericão', 12.90, 'Tradicional'),
('Calabresa', 'mussarela, calabresa, cebola', 12.90, 'Tradicional'),
('Portuguesa', 'mussarela, presunto, ovos, ervilha, cebola', 15.90, 'Especial'),
('Frango com Catupiry', 'mussarela, frango desfiado, catupiry', 15.90, 'Especial'),
('Quatro Queijos', 'mussarela, gorgonzola, parmesão, provolone', 15.90, 'Especial');

INSERT INTO movimentacoes (pastel_id, usuario_id, data_hora, tipo, quantidade, observacoes) VALUES
(1, 1, '2025-01-15 10:30:00', 'entrada', 5, 'Estoque inicial'),
(2, 1, '2025-01-15 10:35:00', 'entrada', 3, 'Estoque inicial'),
(3, 2, '2025-01-15 11:00:00', 'saida', 2, 'Venda para cliente'),
(4, 2, '2025-01-15 11:15:00', 'entrada', 4, 'Reposição de estoque');