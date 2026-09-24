<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Método não permitido."
    ]);
    exit;
}

$email = trim($_POST["email"] ?? "");
$senha = (string) ($_POST["senha"] ?? "");

if ($email === "" || $senha === "") {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Informe e-mail e senha."
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, nome, email, telefone, senha, ativo FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([
        ":email" => mb_strtolower($email)
    ]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$usuario
        || !(int) $usuario["ativo"]
        || !password_verify($senha, $usuario["senha"])
    ) {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "E-mail ou senha inválidos."
        ]);
        exit;
    }

    auth_login($usuario);

    $upd = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
    $upd->execute([":id" => $usuario["id"]]);

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Login realizado.",
        "redirect" => "/Financeiro/dashboard/index.php"
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível entrar."
    ]);
}
