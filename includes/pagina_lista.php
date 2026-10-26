<?php

/**
 * Página de listagem por tipo.
 * Esperado: $tipoPagina ('Receita'|'Despesa'), $tituloPagina, $subtituloPagina
 */

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";
auth_exigir_login();

include __DIR__ . "/header.php";
include __DIR__ . "/sidebar.php";

$ehReceita = $tipoPagina === 'Receita';
$classeCard = $ehReceita ? 'movement-card-receita' : 'movement-card-despesa';
$classeCardMovimentacoes = $ehReceita
    ? 'movement-card-movimentacoes-receita'
    : 'movement-card-movimentacoes-despesa';
$iconeCard = $ehReceita ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
$labelTotal = $ehReceita ? 'Total de entradas' : 'Total de saídas';
$labelMovimentacoes = $ehReceita
    ? 'Registros de receita'
    : 'Registros de despesa';

?>

<div class="main">

    

    <div class="content">
        <div class="content-largura">

        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
            <div>
                <h2><?= htmlspecialchars($tituloPagina) ?></h2>
                <p class="text-muted mb-0" id="subtituloPeriodoLista">
                    Período: carregando...
                </p>
            </div>
            <div class="lista-acoes-topo">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-filtro-periodo-lista"
                    id="btnAbrirPeriodoLista"
                    title="Filtrar período">
                    <i class="bi bi-calendar-range"></i>
                    <span class="filtro-label-completo">Filtrar período</span>
                    <span class="filtro-label-curto">Filtro</span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="movement-card <?= $classeCard ?>">
                    <div class="movement-icon">
                        <i class="bi <?= $iconeCard ?>"></i>
                    </div>
                    <div class="movement-info">
                        <span><?= htmlspecialchars($tituloPagina) ?></span>
                        <strong id="resumoTotal">R$ 0,00</strong>
                        <small><?= $labelTotal ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="movement-card movement-card-movimentacoes <?= $classeCardMovimentacoes ?>">
                    <div class="movement-icon">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="movement-info">
                        <span>Movimentações</span>
                        <strong id="resumoMovimentacoes">0</strong>
                        <small><?= $labelMovimentacoes ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="lista-tabela-toolbar">
            <div class="excel-gear-wrap">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-excel-gear"
                    id="btnExcelGear"
                    aria-expanded="false"
                    aria-controls="menuExcelLista"
                    title="Excel">
                    <i class="bi bi-gear"></i>
                    Excel
                </button>

                <div
                    class="excel-gear-dropdown"
                    id="menuExcelLista"
                    hidden>
                    <button
                        type="button"
                        class="excel-gear-option"
                        id="btnExportarExcel">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        <span>
                            <strong>Exportar Excel</strong>
                            <small>Baixar movimentações do período</small>
                        </span>
                    </button>
                    <button
                        type="button"
                        class="excel-gear-option"
                        id="btnImportarExcel">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                        <span>
                            <strong>Importar Excel</strong>
                            <small>Enviar planilha para o sistema</small>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm lista-tabela-card">
            <div class="card-body">
                <table
                    class="table"
                    id="tabelaMovimentacoes"
                    data-tipo="<?= htmlspecialchars($tipoPagina) ?>">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-end">Valor</th>
                            <th>Data</th>
                            <th class="col-atualizado">Atualizado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyMovimentacoes"></tbody>
                </table>
            </div>
        </div>

        </div>
    </div>

    <?php include __DIR__ . "/modal_periodo_lista.php"; ?>
    <?php include __DIR__ . "/modal_excel_lista.php"; ?>
    <?php include __DIR__ . "/footer.php"; ?>

</div>
