<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json");

$usuarioId = auth_exigir_api();
$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "ID inválido."
    ]);
    exit;
}

$sql = "
    SELECT id, categoria, descricao, tipo, valor, data, criado_em, atualizado_em
    FROM movimentacoes
    WHERE id = :id
      AND usuario_id = :usuario_id
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":id", $id);
$stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
$stmt->execute();

$movimentacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movimentacao) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Movimentação não encontrada."
    ]);
    exit;
}

echo json_encode([
    "status" => "sucesso",
    "dados" => $movimentacao
]);
