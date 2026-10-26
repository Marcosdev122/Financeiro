<?php 
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/validacao.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();

$nome = $_POST["nome"] ?? "";
$descricao = $_POST["descricao"] ?? "";
$valorBruto = str_replace(",", ".", trim((string) ($_POST["valor"] ?? "")));
$data = $_POST["data"] ?? "";

$erro = exigirTexto($nome, "Nome", 1, 120)
    ?? exigirTexto($descricao, "Descrição", 1, 255)
    ?? exigirValorPositivo($valorBruto)
    ?? exigirDataISO($data);

if($erro !== null){
    echo json_encode([
        "status" => "erro",
        "mensagem" => $erro
    ]);
    exit;
}

$sql = "INSERT INTO devedores (usuario_id, nome, descricao, valor, data) 
VALUES (:usuario_id, :nome, :descricao, :valor, :data)";
$stmt = $conn->prepare($sql);
$stmt->bindValue(":usuario_id", $usuarioId);
$stmt->bindValue(":nome", $nome);
$stmt->bindValue(":descricao", $descricao);
$stmt->bindValue(":valor", $valorBruto);
$stmt->bindValue(":data", $data);
$stmt->execute();

echo json_encode([
    "status" => "sucesso",
    "mensagem" => "Devedor salvo com sucesso",
    "id" =>(int) $conn->lastInsertId()
]);
?>