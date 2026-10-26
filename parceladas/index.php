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
                <h2>Parceladas</h2>
                <p class="text-muted mb-0">Compras do cartão. A parcela anda sozinha todo mês.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="movement-card movement-card-parcelada">
                    <div class="movement-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="movement-info">
                        <span>Ainda a pagar</span>
                        <strong id="resumoParceladas">R$ 0,00</strong>
                        <small id="resumoParceladasQtd">0 compras ativas</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm lista-tabela-card">
            <div class="card-body">
                <div class="devedores-toolbar">
                    <label class="devedores-pesquisa" for="pesquisaParcelada">
                        <i class="bi bi-search"></i>
                        <input
                            type="search"
                            id="pesquisaParcelada"
                            placeholder="Pesquisar compra ou situação"
                            autocomplete="off">
                    </label>
                    <span class="devedores-toolbar-info" id="pesquisaParceladaInfo"></span>
                </div>
                <table class="table" id="tabelaParceladas">
                    <thead>
                        <tr>
                            <th>Compra</th>
                            <th>Parcela</th>
                            <th class="text-end">Valor</th>
                            <th>Faltam</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyParceladas">
                        <tr>
                            <td colspan="5" class="text-muted">Nenhuma compra parcelada ainda.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>