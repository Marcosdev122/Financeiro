<div
    class="modal fade"
    id="modalPeriodoLista"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Filtrar período</h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar">
                </button>
            </div>

            <div class="modal-body">
                <p class="text-muted mb-3">
                    Escolha a data inicial e final para filtrar a tabela e os cards.
                </p>

                <div class="lista-periodo-campos">
                    <div>
                        <label class="form-label mb-1" for="listaDataInicio">De</label>
                        <input
                            type="date"
                            class="form-control filtro-data"
                            id="listaDataInicio">
                    </div>
                    <div>
                        <label class="form-label mb-1" for="listaDataFim">Até</label>
                        <input
                            type="date"
                            class="form-control filtro-data"
                            id="listaDataFim">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-redefinir"
                    id="btnRedefinirPeriodoLista">
                    Redefinir
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    id="btnAplicarPeriodoLista">
                    Aplicar
                </button>
            </div>

        </div>

    </div>

</div>
