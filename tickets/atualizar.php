<?php

require_once __DIR__ . "/../config/tickets_auth.php";
tickets_exigir_acesso();

require_once __DIR__ . "/../config/conexao.php";

header("Content-Type: application/json; charset=utf-8");
$id = (int) ($_POST["id"] ?? 0);
$statusNovo = trim($_POST["status"] ?? "");

if ($id <= 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Ticket inválido."
    ]);
    exit;
}

if (!in_array($statusNovo, ["Aberto", "Em atendimento", "Resolvido"], true)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Status inválido."
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE tickets
        SET status = :status, atualizado_em = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ":status" => $statusNovo,
        ":id" => $id
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Ticket não encontrado."
        ]);
        exit;
    }

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Status atualizado."
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível atualizar o ticket."
    ]);
}
