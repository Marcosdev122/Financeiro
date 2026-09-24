<?php

require_once __DIR__ . "/../config/conexao.php";

$tabelas = ["movimentacoes", "receitas", "despesas"];

foreach ($tabelas as $tabela) {
    $existe = $conn->query("SHOW TABLES LIKE " . $conn->quote($tabela))->fetch();

    if (!$existe) {
        echo "Tabela {$tabela} nao existe.\n";
        continue;
    }

    $conn->exec("ALTER TABLE {$tabela} MODIFY valor DECIMAL(18,2) NOT NULL");
    echo "Tabela {$tabela}: valor -> DECIMAL(18,2)\n";
}

echo "Migracao concluida.\n";
