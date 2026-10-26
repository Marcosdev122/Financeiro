<?php 
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();

$sql = "SELECT id, nome, descricao, valor, data
FROM devedores 
WHERE usuario_id = :usuario_id 
AND status = 'Aberto'
ORDER BY data DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":usuario_id", $usuarioId);
$stmt->execute();
$linhas = $stmt->fetchALL(PDO::FETCH_ASSOC);

$total = 0;
foreach($linhas as $linha) {
$total += (float)$linha["valor"];
}

echo json_encode([
    "status" => "sucesso",
    "total" => $total,
    "quantidade" => count($linhas),
    "dados" => $linhas
]); 
?>

