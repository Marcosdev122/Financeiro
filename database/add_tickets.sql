CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    usuario_nome VARCHAR(120) NULL,
    usuario_email VARCHAR(180) NULL,
    protocolo VARCHAR(20) NOT NULL,
    motivo VARCHAR(120) NOT NULL,
    detalhe VARCHAR(300) NULL,
    pagina VARCHAR(500) NULL,
    status ENUM('Aberto', 'Em atendimento', 'Resolvido') NOT NULL DEFAULT 'Aberto',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL,
    UNIQUE KEY uk_tickets_protocolo (protocolo),
    KEY idx_tickets_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
