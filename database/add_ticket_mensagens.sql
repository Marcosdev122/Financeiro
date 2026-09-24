CREATE TABLE IF NOT EXISTS ticket_mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    autor ENUM('usuario', 'suporte') NOT NULL,
    mensagem VARCHAR(1000) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ticket_mensagens_ticket (ticket_id),
    CONSTRAINT fk_ticket_mensagens_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
