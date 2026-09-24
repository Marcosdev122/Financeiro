<?php

require_once __DIR__ . "/../config/conexao.php";

function garantirColunaUsuario(PDO $conn, string $tabela): void
{
    $stmt = $conn->query("SHOW COLUMNS FROM `{$tabela}` LIKE 'usuario_id'");

    if ($stmt->fetch()) {
        echo "{$tabela}: usuario_id já existe.\n";
        return;
    }

    $conn->exec("
        ALTER TABLE `{$tabela}`
        ADD COLUMN usuario_id INT NULL AFTER id
    ");
    echo "{$tabela}: coluna usuario_id adicionada.\n";
}

foreach (["movimentacoes", "receitas", "despesas"] as $tabela) {
    garantirColunaUsuario($conn, $tabela);
}

// Dados antigos: vincula ao primeiro usuário cadastrado (se houver)
$primeiro = $conn->query("SELECT id FROM usuarios ORDER BY id ASC LIMIT 1")->fetchColumn();

if ($primeiro) {
    $id = (int) $primeiro;

    foreach (["movimentacoes", "receitas", "despesas"] as $tabela) {
        $upd = $conn->prepare("
            UPDATE `{$tabela}`
            SET usuario_id = :uid
            WHERE usuario_id IS NULL
        ");
        $upd->execute([":uid" => $id]);
        echo "{$tabela}: {$upd->rowCount()} registro(s) vinculados ao usuário #{$id}.\n";
    }
} else {
    echo "Nenhum usuário cadastrado — registros antigos ficam sem dono até existir conta.\n";
}

// Índices (ignora se já existirem)
foreach (["movimentacoes", "receitas", "despesas"] as $tabela) {
    try {
        $conn->exec("CREATE INDEX idx_{$tabela}_usuario ON `{$tabela}` (usuario_id)");
        echo "{$tabela}: índice criado.\n";
    } catch (Throwable $e) {
        echo "{$tabela}: índice já existe ou não foi necessário.\n";
    }
}

echo "Migração usuario_id concluída.\n";
