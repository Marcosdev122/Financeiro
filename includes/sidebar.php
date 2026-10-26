<?php

$scriptAtual = $_SERVER['SCRIPT_NAME'] ?? '';

$ativoDashboard = str_contains($scriptAtual, '/dashboard/');
$ativoReceitas = str_contains($scriptAtual, '/receitas/');
$ativoDespesas = str_contains($scriptAtual, '/despesas/');
$ativoParceladas = str_contains($scriptAtual, '/parceladas/');

?>

<div class="sidebar">

    <h3 class="text-center text-white py-3">
        Financeiro
    </h3>

    <ul class="sidebar-menu">

        <li>
            <a
                href="/Financeiro/dashboard/index.php"
                class="<?= $ativoDashboard ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a
                href="/Financeiro/receitas/index.php"
                class="sidebar-link-receita <?= $ativoReceitas ? 'active' : '' ?>">
                <i class="bi bi-arrow-up-circle"></i>
                Receitas
            </a>
        </li>

        <li>
            <a
                href="/Financeiro/despesas/index.php"
                class="sidebar-link-despesa <?= $ativoDespesas ? 'active' : '' ?>">
                <i class="bi bi-arrow-down-circle"></i>
                Despesas
            </a>
        </li>


    </ul>

    <?php if ($ativoReceitas || $ativoDespesas): ?>
        <div class="sidebar-action">
            <button
                type="button"
                class="btn w-100 <?= $ativoReceitas ? 'btn-success' : 'btn-danger' ?>"
                id="btnNovaMovimentacao"
                data-tipo="<?= $ativoReceitas ? 'Receita' : 'Despesa' ?>">
                <i class="bi bi-plus-circle"></i>
                <?= $ativoReceitas ? 'Nova receita' : 'Nova despesa' ?>
            </button>
        </div>
    <?php endif; ?>

    <div class="sidebar-config">
        <button
            type="button"
            id="btnAlternarTema"
            title="Alternar tema claro/escuro">
            <i class="bi bi-moon-stars icone-tema-claro"></i>
            <i class="bi bi-sun icone-tema-escuro"></i>
            <span id="textoTema">Tema escuro</span>
        </button>

        <button
            type="button"
            id="btnEngrenagemConfig">
            <i class="bi bi-gear"></i>
            Configuração
        </button>

        <a
            href="/Financeiro/auth/sair.php"
            class="btn-sidebar-sair"
            title="Sair">
            <i class="bi bi-box-arrow-right"></i>
            Sair
        </a>
    </div>

</div>

<button type="button" id="btnToggleSidebar" class="btn-toggle-sidebar" aria-label="Menu">
    <i class="bi bi-chevron-left"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>