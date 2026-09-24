<?php

require_once __DIR__ . "/../config/conexao.php";

$coluna = $conn->query("SHOW COLUMNS FROM movimentacoes LIKE 'descricao'")->fetch();

if ($coluna) {
    echo "Coluna descricao ja existe.\n";
    exit(0);
}

$conn->exec("
    ALTER TABLE movimentacoes
    ADD COLUMN descricao VARCHAR(255) NULL DEFAULT NULL
    AFTER categoria
");

echo "Coluna descricao criada com sucesso.\n";
