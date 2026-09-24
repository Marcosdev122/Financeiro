<?php

function auth_iniciar_sessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function auth_usuario(): ?array
{
    auth_iniciar_sessao();
    return $_SESSION["usuario"] ?? null;
}

function auth_logado(): bool
{
    return auth_usuario() !== null;
}

function auth_exigir_login(): void
{
    if (auth_logado()) {
        return;
    }
    header("Location: /Financeiro/auth/login.php");
    exit;
}

function auth_login(array $usuario): void
{
    auth_iniciar_sessao();
    session_regenerate_id(true);
    $_SESSION["usuario"] = [
    "id" => (int) $usuario["id"],
    "nome" => $usuario["nome"],
    "email" => $usuario["email"],
    "telefone" => $usuario["telefone"] ?? "",
];
    
}

function auth_logout(): void
{
    auth_iniciar_sessao();
    $_SESSION = [];
    session_destroy();
}

function auth_id(): int
{
    $usuario = auth_usuario();

    if ($usuario === null) {
        return 0;
    }

    return (int) ($usuario["id"] ?? 0);
}

/** Exige login em endpoints JSON/API. Retorna o id do usuário. */
function auth_exigir_api(): int
{
    $id = auth_id();

    if ($id > 0) {
        return $id;
    }

    http_response_code(401);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Faça login para continuar."
    ]);
    exit;
}