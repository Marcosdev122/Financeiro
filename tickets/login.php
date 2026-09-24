<?php

require_once __DIR__ . "/../config/tickets_auth.php";

tickets_iniciar_sessao();

if (tickets_autenticado()) {
    header("Location: /Financeiro/tickets/index.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $senha = (string) ($_POST["senha"] ?? "");

    if (tickets_tentar_login($senha)) {
        header("Location: /Financeiro/tickets/index.php");
        exit;
    }

    $erro = "Senha incorreta.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suporte — Login</title>
    <script>
        (function () {
            try {
                var tema = localStorage.getItem("financeiro-tema-v2") || "claro";
                if (tema !== "escuro" && tema !== "claro") tema = "claro";
                document.documentElement.setAttribute("data-tema", tema);
            } catch (e) {
                document.documentElement.setAttribute("data-tema", "claro");
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/Financeiro/assets/css/style.css">
    <link rel="stylesheet" href="/Financeiro/assets/css/suporte.css">
</head>
<body>
<div class="tickets-login-wrap">
    <div class="tickets-login-card">
        <div class="tickets-login-icone">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h1>Área de suporte</h1>
        <p class="text-muted mb-4">
            Só quem atende os tickets entra aqui. O usuário do sistema só usa “Fale conosco”.
        </p>

        <?php if ($erro !== ""): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label" for="senhaTickets">Senha do suporte</label>
                <input
                    type="password"
                    class="form-control"
                    id="senhaTickets"
                    name="senha"
                    required
                    autofocus
                    autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                Entrar
            </button>
        </form>

        <a href="/Financeiro/dashboard/index.php" class="tickets-login-voltar">
            Voltar ao sistema
        </a>
    </div>
</div>
</body>
</html>
