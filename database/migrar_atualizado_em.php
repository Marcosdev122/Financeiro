<?php

require_once __DIR__ . "/../config/conexao.php";

$coluna = $conn->query("SHOW COLUMNS FROM movimentacoes LIKE 'atualizado_em'")->fetch();

if ($coluna) {
    echo "Coluna atualizado_em ja existe.\n";
    exit(0);
}

$conn->exec("
    ALTER TABLE movimentacoes
    ADD COLUMN atualizado_em DATETIME NULL DEFAULT NULL
    AFTER data
");

echo "Coluna atualizado_em criada com sucesso.\n";
