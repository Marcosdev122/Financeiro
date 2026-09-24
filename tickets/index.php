<?php
require_once __DIR__ . "/../config/tickets_auth.php";
tickets_exigir_acesso();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suporte — Tickets</title>
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
<body class="suporte-desk">

    <header class="suporte-topbar">
        <div class="suporte-topbar-brand">
            <span class="suporte-topbar-icone"><i class="bi bi-headset"></i></span>
            <div>
                <strong>Central de suporte</strong>
                <small>Financeiro — visão exclusiva do atendimento</small>
            </div>
        </div>
        <div class="suporte-topbar-acoes">
            <a href="/Financeiro/dashboard/index.php" class="suporte-link-suave">
                <i class="bi bi-box-arrow-up-right"></i>
                Sistema
            </a>
            <a href="/Financeiro/tickets/logout.php" class="suporte-btn-sair">
                <i class="bi bi-box-arrow-right"></i>
                Sair
            </a>
        </div>
    </header>

    <main class="suporte-main">
        <div class="suporte-toolbar">
            <div>
                <h1>Tickets</h1>
                <p>Acompanhe o status e responda pelo WhatsApp do cliente.</p>
            </div>
            <div class="suporte-filtro">
                <label for="filtroStatusTicket">Status</label>
                <select id="filtroStatusTicket">
                    <option value="">Todos</option>
                    <option value="Aberto">Aberto</option>
                    <option value="Em atendimento">Em atendimento</option>
                    <option value="Resolvido">Resolvido</option>
                </select>
            </div>
        </div>

        <div class="suporte-kpis">
            <article class="suporte-kpi suporte-kpi-aberto">
                <span>Abertos</span>
                <strong id="ticketResumoAbertos">0</strong>
            </article>
            <article class="suporte-kpi suporte-kpi-atende">
                <span>Em atendimento</span>
                <strong id="ticketResumoAtendimento">0</strong>
            </article>
            <article class="suporte-kpi suporte-kpi-ok">
                <span>Resolvidos</span>
                <strong id="ticketResumoResolvidos">0</strong>
            </article>
        </div>

        <div class="suporte-lista" id="corpoTabelaTickets" aria-live="polite">
            <div class="suporte-lista-vazio">Carregando tickets...</div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/Financeiro/assets/js/script.js"></script>
</body>
</html>
