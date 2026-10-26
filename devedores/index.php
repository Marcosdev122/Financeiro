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
                <h2>Devedores</h2>
                <p class="text-muted mb-0">Quem está te devendo e do quê.</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnNovoDevedor">
                <i class="bi bi-plus-circle"></i>
                <span class="devedor-label-completo">Novo devedor</span>
                <span class="devedor-label-curto">Novo</span>
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="movement-card movement-card-despesa">
                    <div class="movement-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div class="movement-info">
                        <span>Em aberto</span>
                        <strong id="resumoDevedores">R$ 0,00</strong>
                        <small id="resumoDevedoresQtd">0 pessoas</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm lista-tabela-card">
            <div class="card-body">
                <div class="devedores-toolbar">
                    <label class="devedores-pesquisa" for="pesquisaDevedor">
                        <i class="bi bi-search"></i>
                        <input
                            type="search"
                            id="pesquisaDevedor"
                            placeholder="Pesquisar pelo nome"
                            autocomplete="off">
                    </label>
                    <span class="devedores-toolbar-info" id="pesquisaDevedorInfo"></span>
                </div>
                <table class="table" id="tabelaDevedores">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDevedores">
                        <tr>
                            <td colspan="5" class="text-muted">Nenhum devedor ainda.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalDevedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalDevedor">Novo devedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="devedorId">
                <div class="mb-3">
                    <label class="form-label" for="devedorNome">Nome</label>
                    <input type="text" class="form-control" id="devedorNome" maxlength="120">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="devedorDescricao">Descrição</label>
                    <input type="text" class="form-control" id="devedorDescricao" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="devedorValor">Valor</label>
                    <input type="text" class="form-control" id="devedorValor" inputmode="decimal">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="devedorData">Data</label>
                    <input type="date" class="form-control" id="devedorData">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarDevedor">Salvar</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>