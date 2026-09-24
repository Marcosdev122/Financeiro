<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json");

$usuarioId = auth_exigir_api();
$anoAtual = (int) date("Y");

$sql = "
    SELECT DISTINCT YEAR(data) AS ano
    FROM movimentacoes
    WHERE usuario_id = :usuario_id
    ORDER BY ano DESC
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
$stmt->execute();

$anos = $stmt->fetchAll(PDO::FETCH_COLUMN);
$anos = array_map("intval", $anos);

if (!in_array($anoAtual, $anos, true)) {
    array_unshift($anos, $anoAtual);
}

$anos = array_values(array_unique($anos));
rsort($anos);

echo json_encode([
    "status" => "sucesso",
    "dados" => $anos
]);
