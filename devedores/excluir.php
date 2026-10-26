<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Devedor não informado."
    ]);
    exit;
}

$sql = "
    DELETE FROM devedores
    WHERE id = :id
      AND usuario_id = :usuario_id
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
    "mensagem" => "Devedor excluído."
]);