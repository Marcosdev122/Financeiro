<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json");

$usuarioId = auth_exigir_api();

$sql = "
    SELECT
        DATE_FORMAT(data, '%Y-%m') AS mes,
        SUM(
            CASE
                WHEN tipo = 'Receita' THEN valor
                ELSE 0
            END
        ) AS receitas,
        SUM(
            CASE
                WHEN tipo = 'Despesa' THEN valor
                ELSE 0
            END
        ) AS despesas
    FROM movimentacoes
    WHERE usuario_id = :usuario_id
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes ASC
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
$stmt->execute();

$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "sucesso",
    "dados" => $dados
]);
