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
    <title>Cadastro — Financeiro</title>
    <script>
        (function() {
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
                <h1 class="auth-brand-title">Comece a organizar seu dinheiro</h1>
                <p class="auth-brand-text">
                    Crie sua conta e acompanhe receitas e despesas com facilidade.
                </p>
            </div>
            <div class="auth-brand-glow" aria-hidden="true"></div>
        </aside>

        <main class="auth-main">
            <div class="auth-card auth-card-enter">
                <div class="auth-card-header">
                    <h2>Criar conta</h2>
                    <p>Preencha os dados para se cadastrar</p>
                </div>

                <div id="alertaAuth" class="auth-alerta d-none" role="alert"></div>

                <form id="formCadastro" class="auth-form" method="post" action="/Financeiro/auth/registrar.php" novalidate>
                    <div class="auth-field">
                        <label for="nome">Nome</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text" id="nome" name="nome" required maxlength="120" autocomplete="name" placeholder="Seu nome">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="email">E-mail</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-envelope"></i>
                            <input type="email" id="email" name="email" required maxlength="180" autocomplete="email" placeholder="seu@email.com">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="telefone">WhatsApp</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-whatsapp"></i>
                            <input
                                type="tel"
                                id="telefone"
                                name="telefone"
                                required
                                maxlength="15"
                                autocomplete="tel"
                                placeholder="(00) 00000-0000"
                                inputmode="numeric">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="senha">Senha</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="senha" name="senha" required minlength="6" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="senha_confirma">Confirmar senha</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-shield-check"></i>
                            <input type="password" id="senha_confirma" name="senha_confirma" required minlength="6" autocomplete="new-password" placeholder="Repita a senha">
                        </div>
                    </div>
                    <button type="submit" class="auth-submit" id="btnCadastro">
                        <span>Cadastrar</span>
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>

                <p class="auth-switch">
                    Já tem conta?
                    <a href="/Financeiro/auth/login.php">Entrar</a>
                </p>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function mascaraTelefone(valor) {
            let d = String(valor || "").replace(/\D/g, "");

            if (d.length > 11 && d.startsWith("55")) {
                d = d.slice(2);
            }
            d = d.slice(0, 11);

            if (d.length === 0) return "";
            if (d.length <= 2) return "(" + d;
            if (d.length <= 6) return "(" + d.slice(0, 2) + ") " + d.slice(2);
            if (d.length <= 10) {
                return "(" + d.slice(0, 2) + ") " + d.slice(2, 6) + "-" + d.slice(6);
            }
            return "(" + d.slice(0, 2) + ") " + d.slice(2, 7) + "-" + d.slice(7);
        }

        $("#telefone").on("input", function() {
            this.value = mascaraTelefone(this.value);
        });

        $("#formCadastro").on("submit", function(e) {
            e.preventDefault();
            const $alerta = $("#alertaAuth");
            $alerta.addClass("d-none").removeClass("erro ok").text("");
            $("#btnCadastro").prop("disabled", true);

            $.ajax({
                url: "/Financeiro/auth/registrar.php",
                method: "POST",
                dataType: "json",
                data: $(this).serialize(),
                success: function(res) {
                    if (!res || res.status !== "sucesso") {
                        $alerta.removeClass("d-none").addClass("erro")
                            .text((res && res.mensagem) || "Falha no cadastro.");
                        $("#btnCadastro").prop("disabled", false);
                        return;
                    }
                    $alerta.removeClass("d-none").addClass("ok")
                        .text(res.mensagem || "Conta criada.");
                    setTimeout(function() {
                        window.location.href = "/Financeiro/auth/login.php";
                    }, 900);
                },
                error: function() {
                    $alerta.removeClass("d-none").addClass("erro")
                        .text("Não foi possível cadastrar.");
                    $("#btnCadastro").prop("disabled", false);
                }
            });
        });
    </script>
</body>

</html>