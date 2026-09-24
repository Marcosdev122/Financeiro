<?php

require_once __DIR__ . "/../config/conexao.php";

$conn->exec("
    CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        protocolo VARCHAR(20) NOT NULL,
        motivo VARCHAR(120) NOT NULL,
        detalhe VARCHAR(300) NULL,
        pagina VARCHAR(500) NULL,
        status ENUM('Aberto', 'Em atendimento', 'Resolvido') NOT NULL DEFAULT 'Aberto',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL DEFAULT NULL,
        UNIQUE KEY uk_tickets_protocolo (protocolo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "Tabela tickets pronta.\n";
