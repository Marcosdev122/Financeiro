<?php
require_once __DIR__ . "/../config/auth.php";
auth_exigir_login();

include __DIR__ . "/../includes/header.php";
?>
<link rel="stylesheet" href="/Financeiro/assets/css/suporte.css">
<?php
include __DIR__ . "/../includes/sidebar.php";
?>

<div class="content">
    <div class="content-largura">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2>Meus tickets</h2>
            <p class="text-muted mb-0">
                Acompanhe seus protocolos. O atendimento continua pelo WhatsApp.
            </p>
        </div>
    </div>

    <div class="meus-tickets-lista" id="listaMeusTickets" aria-live="polite">
        <div class="meus-tickets-vazio">Carregando...</div>
    </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
