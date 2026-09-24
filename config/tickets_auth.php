<?php

/**
 * Acesso restrito à área de tickets (suporte).
 * Altere a senha abaixo — só você deve conhecer.
 */
const TICKETS_SENHA = "suporte123";

function tickets_iniciar_sessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function tickets_autenticado(): bool
{
    tickets_iniciar_sessao();
    return !empty($_SESSION["tickets_admin"]);
}

function tickets_exigir_acesso(): void
{
    if (tickets_autenticado()) {
        return;
    }

    if (php_sapi_name() !== "cli" && str_contains($_SERVER["HTTP_ACCEPT"] ?? "", "application/json")) {
        http_response_code(401);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Acesso restrito ao suporte."
        ]);
        exit;
    }

    // APIs sem Accept json ainda respondem JSON
    $script = $_SERVER["SCRIPT_NAME"] ?? "";
    if (preg_match("#/(listar|atualizar)\\.php$#", $script)) {
        http_response_code(401);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Acesso restrito ao suporte."
        ]);
        exit;
    }

    header("Location: /Financeiro/tickets/login.php");
    exit;
}

function tickets_tentar_login(string $senha): bool
{
    tickets_iniciar_sessao();

    if (!hash_equals(TICKETS_SENHA, $senha)) {
        return false;
    }

    $_SESSION["tickets_admin"] = true;
    return true;
}

function tickets_logout(): void
{
    tickets_iniciar_sessao();
    unset($_SESSION["tickets_admin"]);
}
