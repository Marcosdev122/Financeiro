<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../includes/parcelas.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$dados = listarComprasParceladas($conn, $usuarioId);

$totalAberto = 0;
$quantidadeAtivas = 0;

foreach ($dados as $item) {
    if (!$item["quitada"]) {
        $totalAberto += $item["valor_parcela"] * $item["faltam"];
        $quantidadeAtivas++;
    }
}

echo json_encode([
    "status" => "sucesso",
    "total" => $totalAberto,
    "quantidade" => $quantidadeAtivas,
    "dados" => $dados
]);
