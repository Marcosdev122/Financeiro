<?php

require_once __DIR__ . "/../config/tickets_auth.php";
tickets_exigir_acesso();

require_once __DIR__ . "/../config/conexao.php";

header("Content-Type: application/json; charset=utf-8");

$statusFiltro = $_GET["status"] ?? "";

$sql = "
    SELECT
        id,
        protocolo,
        usuario_id,
        usuario_nome,
        usuario_email,
        usuario_telefone,
        motivo,
        detalhe,
        pagina,
        status,
        criado_em,
        atualizado_em
    FROM tickets
";

$params = [];

if (in_array($statusFiltro, ["Aberto", "Em atendimento", "Resolvido"], true)) {
    $sql .= " WHERE status = :status";
    $params[":status"] = $statusFiltro;
}

$sql .= " ORDER BY criado_em DESC, id DESC LIMIT 100";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resumo = [
        "abertos" => 0,
        "atendimento" => 0,
        "resolvidos" => 0,
        "total" => count($tickets)
    ];

    $stmtResumo = $conn->query("
        SELECT status, COUNT(*) AS total
        FROM tickets
        GROUP BY status
    ");

    foreach ($stmtResumo->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if ($linha["status"] === "Aberto") {
            $resumo["abertos"] = (int) $linha["total"];
        } elseif ($linha["status"] === "Em atendimento") {
            $resumo["atendimento"] = (int) $linha["total"];
        } elseif ($linha["status"] === "Resolvido") {
            $resumo["resolvidos"] = (int) $linha["total"];
        }
    }

    echo json_encode([
        "status" => "sucesso",
        "dados" => [
            "tickets" => $tickets,
            "resumo" => $resumo
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível listar os tickets."
    ]);
}
