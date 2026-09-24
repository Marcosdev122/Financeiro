<?php
require_once __DIR__ . "/../config/conexao.php";

function garantirColuna(PDO $conn, string $tabela, string $coluna, string $definicao): void
{
    $stmt = $conn->query("SHOW COLUMNS FROM `{$tabela}` LIKE " . $conn->quote($coluna));
    if ($stmt->fetch()) {
        echo "{$tabela}.{$coluna} já existe.\n";
        return;
    }
    $conn->exec("ALTER TABLE `{$tabela}` ADD COLUMN {$coluna} {$definicao}");
    echo "{$tabela}.{$coluna} adicionada.\n";
}

garantirColuna($conn, "usuarios", "telefone", "VARCHAR(20) NULL AFTER email");
garantirColuna($conn, "tickets", "usuario_telefone", "VARCHAR(20) NULL AFTER usuario_email");

echo "Migração telefone ok.\n";
