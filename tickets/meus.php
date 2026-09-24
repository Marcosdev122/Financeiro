<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            protocolo,
            motivo,
            detalhe,
            status,
            criado_em,
            atualizado_em
        FROM tickets
        WHERE usuario_id = :usuario_id
        ORDER BY criado_em DESC, id DESC
        LIMIT 50
    ");
    $stmt->execute([":usuario_id" => $usuarioId]);

    echo json_encode([
        "status" => "sucesso",
        "dados" => [
            "tickets" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível listar seus tickets."
    ]);
}
