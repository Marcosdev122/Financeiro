<?php

require_once __DIR__ . "/../config/conexao.php";

function colunaExiste(PDO $conn, string $tabela, string $coluna): bool
{
    $stmt = $conn->query("SHOW COLUMNS FROM `{$tabela}` LIKE " . $conn->quote($coluna));
    return (bool) $stmt->fetch();
}

$alteracoes = [
    "usuario_id" => "INT NULL AFTER id",
    "usuario_nome" => "VARCHAR(120) NULL AFTER usuario_id",
    "usuario_email" => "VARCHAR(180) NULL AFTER usuario_nome",
];

foreach ($alteracoes as $coluna => $definicao) {
    if (colunaExiste($conn, "tickets", $coluna)) {
        echo "tickets.{$coluna} já existe.\n";
        continue;
    }

    $conn->exec("ALTER TABLE tickets ADD COLUMN {$coluna} {$definicao}");
    echo "tickets.{$coluna} adicionada.\n";
}

try {
    $conn->exec("CREATE INDEX idx_tickets_usuario ON tickets (usuario_id)");
    echo "índice idx_tickets_usuario criado.\n";
} catch (Throwable $e) {
    echo "índice já existe ou não foi necessário.\n";
}

echo "Migração tickets × usuário concluída.\n";
