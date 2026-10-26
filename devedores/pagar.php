<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "ID inválido"
    ]);
    exit;
}

$sql = "
    UPDATE devedores
    SET status = 'Pago'
    WHERE id = :id
      AND usuario_id = :usuario_id
      AND status = 'Aberto'
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id" => $id,
    ":usuario_id" => $usuarioId
]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Devedor não encontrado."
    ]);
    exit;
}

echo json_encode([
    "status" => "sucesso",
    "mensagem" => "Marcado como pago."
]);
