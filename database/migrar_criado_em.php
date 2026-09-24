<?php

require_once __DIR__ . "/../config/conexao.php";

$coluna = $conn->query("SHOW COLUMNS FROM movimentacoes LIKE 'criado_em'")->fetch();

if (!$coluna) {
    $conn->exec("
        ALTER TABLE movimentacoes
        ADD COLUMN criado_em DATETIME NULL DEFAULT NULL
        AFTER data
    ");

    echo "Coluna criado_em criada.\n";
} else {
    echo "Coluna criado_em ja existe.\n";
}

$conn->exec("
    UPDATE movimentacoes
    SET criado_em = atualizado_em
    WHERE criado_em IS NULL
      AND atualizado_em IS NOT NULL
");

$conn->exec("
    UPDATE movimentacoes
    SET criado_em = TIMESTAMP(data)
    WHERE criado_em IS NULL
");

$conn->exec("
    UPDATE movimentacoes
    SET atualizado_em = NULL
    WHERE atualizado_em IS NOT NULL
      AND criado_em IS NOT NULL
      AND ABS(TIMESTAMPDIFF(SECOND, criado_em, atualizado_em)) < 2
");

echo "Migracao criado_em concluida.\n";
