<?php

require_once __DIR__ . "/../config/auth.php";

if (auth_logado()) {
    header("Location: /Financeiro/dashboard/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — Financeiro</title>
    <script>
        (function () {
            // Login/cadastro sempre no tema claro
            document.documentElement.setAttribute("data-tema", "claro");
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/Financeiro/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <aside class="auth-brand" aria-hidden="false">
            <div class="auth-brand-inner">
                <span class="auth-brand-mark">
                    <i class="bi bi-wallet2"></i>
                </span>
                <p class="auth-brand-name">Financeiro</p>
                <h1 class="auth-brand-title">Controle suas finanças com clareza</h1>
                <p class="auth-brand-text">
                    Receitas, despesas e dashboard em um só lugar.
                </p>
            </div>
            <div class="auth-brand-glow" aria-hidden="true"></div>
        </aside>

        <main class="auth-main">
            <div class="auth-card auth-card-enter">
                <div class="auth-card-header">
                    <h2>Entrar</h2>
                    <p>Acesse sua conta para continuar</p>
                </div>

                <div id="alertaAuth" class="auth-alerta d-none" role="alert"></div>

                <form id="formLogin" class="auth-form" method="post" action="/Financeiro/auth/entrar.php" novalidate>
                    <div class="auth-field">
                        <label for="email">E-mail</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="email" name="email" required autocomplete="email" placeholder="seu@email.com">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="senha">Senha</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="auth-submit" id="btnLogin">
                        <span>Entrar</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <p class="auth-switch">
                    Não tem conta?
                    <a href="/Financeiro/auth/cadastro.php">Cadastre-se</a>
                </p>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $("#formLogin").on("submit", function (e) {
            e.preventDefault();
            $("#alertaAuth").addClass("d-none").removeClass("erro").text("");
            $("#btnLogin").prop("disabled", true);

            $.ajax({
                url: "/Financeiro/auth/entrar.php",
                method: "POST",
                dataType: "json",
                data: $(this).serialize(),
                success: function (res) {
                    if (!res || res.status !== "sucesso") {
                        $("#alertaAuth").removeClass("d-none").addClass("erro")
                            .text((res && res.mensagem) || "Falha no login.");
                        $("#btnLogin").prop("disabled", false);
                        return;
                    }
                    window.location.href = res.redirect || "/Financeiro/dashboard/index.php";
                },
                error: function () {
                    $("#alertaAuth").removeClass("d-none").addClass("erro")
                        .text("Não foi possível entrar.");
                    $("#btnLogin").prop("disabled", false);
                }
            });
        });
    </script>
</body>
</html>
