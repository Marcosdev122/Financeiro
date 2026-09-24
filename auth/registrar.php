<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/validacao.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Método não permitido."
    ]);
    exit;
}

$nome = trim($_POST["nome"] ?? "");
$email = trim($_POST["email"] ?? "");
$senha = (string) ($_POST["senha"] ?? "");
$senhaConfirma = (string) ($_POST["senha_confirma"] ?? "");
$telefone = trim($_POST["telefone"] ?? "");

$erros = [];

if ($e = exigirTexto($nome, "Nome", 2, 120)) {
    $erros[] = $e;
}
if ($e = exigirEmail($email)) {
    $erros[] = $e;
}

$telefoneNormalizado = "";
if ($e = exigirTelefoneBr($telefone, $telefoneNormalizado)) {
    $erros[] = $e;
} else {
    $telefone = $telefoneNormalizado;
}

if ($senha === "") {
    $erros[] = "Preencha a senha.";
} elseif (strlen($senha) < 6) {
    $erros[] = "A senha deve ter no mínimo 6 caracteres.";
}

if ($senha !== $senhaConfirma) {
    $erros[] = "As senhas não coincidem.";
}

if ($erros) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => $erros[0],
        "erros" => $erros
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nome, email, telefone, senha, ativo, criado_em)
        VALUES (:nome, :email, :telefone, :senha, 1, NOW())
    ");
    $stmt->execute([
        ":nome" => $nome,
        ":email" => mb_strtolower($email),
        ":telefone" => $telefone,
        ":senha" => password_hash($senha, PASSWORD_DEFAULT)
    ]);

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Conta criada. Faça login."
    ]);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Este e-mail já está cadastrado."
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível criar a conta."
    ]);
}
