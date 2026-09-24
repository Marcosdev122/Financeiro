-- Separar dados financeiros por usuário
ALTER TABLE movimentacoes
    ADD COLUMN usuario_id INT NULL AFTER id;

ALTER TABLE receitas
    ADD COLUMN usuario_id INT NULL AFTER id;

ALTER TABLE despesas
    ADD COLUMN usuario_id INT NULL AFTER id;
