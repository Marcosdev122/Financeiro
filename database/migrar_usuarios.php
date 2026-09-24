<?php

require_once __DIR__ . "/../config/conexao.php";

$conn->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(180) NOT NULL,
        senha VARCHAR(255) NOT NULL COMMENT 'password_hash do PHP',
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NULL DEFAULT NULL,
        ultimo_login DATETIME NULL DEFAULT NULL,
        UNIQUE KEY uk_usuarios_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

echo "Tabela usuarios pronta.\n";
