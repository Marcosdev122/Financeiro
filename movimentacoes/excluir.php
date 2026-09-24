<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "ID da movimentação não informado."
    ]);
    exit;
}

try {
    $stmtBusca = $conn->prepare("
        SELECT categoria, tipo, valor, data
        FROM movimentacoes
        WHERE id = :id
          AND usuario_id = :usuario_id
        LIMIT 1
    ");
    $stmtBusca->bindValue(":id", $id);
    $stmtBusca->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmtBusca->execute();
    $movimentacao = $stmtBusca->fetch(PDO::FETCH_ASSOC);

    if (!$movimentacao) {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Movimentação não encontrada."
        ]);
        exit;
    }

    $conn->beginTransaction();

    $stmtExcluir = $conn->prepare("
        DELETE FROM movimentacoes
        WHERE id = :id
          AND usuario_id = :usuario_id
    ");
    $stmtExcluir->bindValue(":id", $id);
    $stmtExcluir->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmtExcluir->execute();

    $tabela = $movimentacao['tipo'] === 'Receita' ? 'receitas' : 'despesas';

    $stmtEspelho = $conn->prepare("
        DELETE FROM {$tabela}
        WHERE usuario_id = :usuario_id
          AND categoria = :categoria
          AND valor = :valor
          AND data = :data
        LIMIT 1
    ");
    $stmtEspelho->execute([
        ":usuario_id" => $usuarioId,
        ":categoria" => $movimentacao['categoria'],
        ":valor" => $movimentacao['valor'],
        ":data" => $movimentacao['data'],
    ]);

    $conn->commit();

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Movimentação excluída com sucesso."
    ]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao excluir: " . $e->getMessage()
    ]);
}
