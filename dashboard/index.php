<?php
require_once __DIR__ . "/../config/auth.php";
auth_exigir_login();

include __DIR__ . "/../includes/header.php";
include __DIR__ . "/../includes/sidebar.php";
?>

<div class="content">
    <div class="content-largura">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2>Dashboard</h2>
            <p class="text-muted mb-0">
                Filtre de uma data inicial até uma data final.
            </p>
        </div>

        <div class="dashboard-filters">
            <div>
                <label class="form-label mb-1" for="dataInicio">De</label>
                <input
                    type="date"
                    class="form-control filtro-data"
                    id="dataInicio"
                    max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label mb-1" for="dataFim">Até</label>
                <input
                    type="date"
                    class="form-control filtro-data"
                    id="dataFim"
                    max="<?= date('Y-m-d') ?>">
            </div>
            <button
                type="button"
                class="btn btn-outline-secondary btn-redefinir"
                id="btnRedefinirFiltros">
                Redefinir
            </button>
        </div>
    </div>

    <div class="dashboard-cards">
        <div class="dashboard-card card-receitas">
            <div class="card-icon">
                <i class="bi bi-arrow-up-right"></i>
            </div>
            <div class="card-info">
                <span class="card-label">RECEITAS</span>
                <strong id="totalReceitas">R$ 0,00</strong>
                <small id="comparacaoReceitas">Total recebido</small>
            </div>
        </div>

        <div class="dashboard-card card-despesas">
            <div class="card-icon">
                <i class="bi bi-arrow-down-right"></i>
            </div>
            <div class="card-info">
                <span class="card-label">DESPESAS</span>
                <strong id="totalDespesas">R$ 0,00</strong>
                <small id="comparacaoDespesas">Total gasto</small>
            </div>
        </div>

        <div class="dashboard-card card-saldo">
            <div class="card-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="card-info">
                <span class="card-label">SALDO</span>
                <strong id="saldo">R$ 0,00</strong>
                <small id="comparacaoSaldo">Saldo disponível</small>
            </div>
        </div>

        <div class="dashboard-card card-movimentacoes">
            <div class="card-icon">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div class="card-info">
                <span class="card-label">MOVIMENTAÇÕES</span>
                <strong id="totalMovimentacoes">0</strong>
                <small id="comparacaoMovimentacoes">Registros no período</small>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card dashboard-chart-card comparacao-anos-card">
                <div class="card-body">
                    <div class="chart-header">
                        <div>
                            <h5 class="card-title mb-1">Comparação Receita, Despesa e Saldo</h5>
                        </div>
                        <div class="chart-legend-custom">
                            <span>
                                <i class="legend-dot receita"></i>
                                Receitas
                            </span>
                            <span>
                                <i class="legend-dot despesa"></i>
                                Despesas
                            </span>
                            <span>
                                <i class="legend-dot saldo"></i>
                                Saldo
                            </span>
                        </div>
                    </div>

                    <div class="chart-container chart-container-comparacao">
                        <div id="graficoComparacaoAnos" class="echarts-host"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <div class="card dashboard-chart-card h-100">
                <div class="card-body">
                    <div class="chart-header">
                        <div>
                            <h5 class="card-title mb-1">Receitas por categoria</h5>
                            <p class="chart-subtitle">Totais do período selecionado</p>
                        </div>
                        <select
                            class="form-select form-select-sm agrupamento-grafico-categoria"
                            id="agrupamentoReceitasCategoria"
                            aria-label="Agrupar receitas por">
                            <option value="categoria" selected>Por categoria</option>
                            <option value="descricao">Por descrição</option>
                        </select>
                    </div>
                    <div class="chart-container chart-container-category">
                        <div id="graficoReceitasCategoria" class="echarts-host"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card dashboard-chart-card h-100">
                <div class="card-body">
                    <div class="chart-header">
                        <div>
                            <h5 class="card-title mb-1">Despesas por categoria</h5>
                            <p class="chart-subtitle">Totais do período selecionado</p>
                        </div>
                        <select
                            class="form-select form-select-sm agrupamento-grafico-categoria"
                            id="agrupamentoDespesasCategoria"
                            aria-label="Agrupar despesas por">
                            <option value="categoria" selected>Por categoria</option>
                            <option value="descricao">Por descrição</option>
                        </select>
                    </div>
                    <div class="chart-container chart-container-category">
                        <div id="graficoDespesasCategoria" class="echarts-host"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div class="chart-header">
                        <div>
                            <h5 class="card-title mb-1">Receitas x Despesas</h5>
                            <p class="chart-subtitle" id="subtituloEvolucao">
                                Evolução mensal do período
                            </p>
                        </div>
                        <div class="chart-legend-custom">
                            <span>
                                <i class="legend-dot receita"></i>
                                Receitas
                            </span>
                            <span>
                                <i class="legend-dot despesa"></i>
                                Despesas
                            </span>
                            <span>
                                <i class="legend-dot media-receita"></i>
                                Média receitas
                            </span>
                            <span>
                                <i class="legend-dot media-despesa"></i>
                                Média despesas
                            </span>
                        </div>
                    </div>
                    <div class="chart-container chart-container-evolucao">
                        <div id="graficoEvolucao" class="echarts-host"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div class="chart-header">
                        <div>
                            <h5 class="card-title mb-1">Fluxo de caixa</h5>
                            <p class="chart-subtitle" id="subtituloFluxoCaixa">
                                Fluxo mensal do período
                            </p>
                        </div>
                        <div class="chart-legend-custom">
                            <span>
                                <i class="legend-dot receita"></i>
                                Positivo
                            </span>
                            <span>
                                <i class="legend-dot despesa"></i>
                                Negativo
                            </span>
                        </div>
                    </div>
                    <div class="chart-container chart-container-fluxo">
                        <div id="graficoFluxoCaixa" class="echarts-host"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card dashboard-chart-card">
                <div class="card-body">
                    <div class="chart-header">
                        <div>
                            <h5 class="card-title mb-1">Saldo acumulado</h5>
                            <p class="chart-subtitle" id="subtituloSaldo">
                                Saldo acumulado do período
                            </p>
                        </div>
                        <div class="chart-legend-custom">
                            <span>
                                <i class="legend-dot saldo"></i>
                                Saldo
                            </span>
                            <span>
                                <i class="legend-dot media-saldo"></i>
                                Média
                            </span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <div id="graficoSaldo" class="echarts-host"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
