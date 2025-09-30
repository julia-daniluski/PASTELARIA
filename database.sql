CREATE DATABASE pastelaria;
USE pastelaria;

-- =========================
-- Tabela de usuários
-- =========================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  senha VARCHAR(255) NOT NULL
);

-- =========================
-- Tabela de pastéis
-- =========================
CREATE TABLE pasteis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  ingredientes TEXT NOT NULL,
  preco DECIMAL(8,2) NOT NULL,
  estoque_minimo INT DEFAULT 5,
  estoque_atual INT DEFAULT 0,
  ativo BOOLEAN DEFAULT TRUE
);

-- =========================
-- Tabela de movimentações
-- =========================
CREATE TABLE movimentacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pastel_id INT NOT NULL,
  usuario_id INT NOT NULL,
  data_hora DATETIME NOT NULL,
  tipo ENUM('entrada','saida') NOT NULL,
  quantidade INT NOT NULL,
  observacoes TEXT,
  FOREIGN KEY (pastel_id) REFERENCES pasteis(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

-- =========================
-- Inserção de usuários (com senha criptografada SHA2)
-- =========================
INSERT INTO usuarios (nome, senha) VALUES
('Admin', SHA2('admin123', 256)),
('Gerente', SHA2('gerente123', 256)),
('Atendente', SHA2('atendente123', 256));

-- =========================
-- Inserção de pastéis (preço fixo R$12)
-- =========================
INSERT INTO pasteis (nome, ingredientes, preco, estoque_atual) VALUES
('Margherita', 'Massa de pizza, mussarela, manjericão', 12.00, 5),
('Calabresa', 'Massa de pizza, mussarela, calabresa, cebola', 12.00, 3),
('Portuguesa', 'Massa de pizza, mussarela, presunto, ovos, ervilha, cebola', 12.00, 8),
('Frango com Catupiry', 'Massa de pizza, mussarela, frango desfiado, catupiry', 12.00, 4),
('Quatro Queijos', 'Massa de pizza, mussarela, gorgonzola, parmesão, provolone', 12.00, 6);

-- =========================
-- Inserção de movimentações
-- =========================
INSERT INTO movimentacoes (pastel_id, usuario_id, data_hora, tipo, quantidade, observacoes) VALUES
(1, 1, '2025-01-15 10:30:00', 'entrada', 5, 'Estoque inicial'),
(2, 1, '2025-01-15 10:35:00', 'entrada', 3, 'Estoque inicial'),
(3, 2, '2025-01-15 11:00:00', 'saida', 2, 'Venda para cliente'),
(4, 2, '2025-01-15 11:15:00', 'entrada', 4, 'Reposição de estoque');