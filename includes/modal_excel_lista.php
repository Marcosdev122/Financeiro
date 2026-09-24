<div
    class="modal fade"
    id="modalImportarExcel"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content excel-modal-content">

            <div class="modal-header">
                <div class="excel-modal-icon">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </div>
                <div>
                    <h5 class="modal-title mb-0">Importar Excel</h5>
                    <p class="excel-modal-subtitle mb-0" id="subtituloImportarExcel">
                        Selecione um arquivo .xlsx, .xls ou .csv
                    </p>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar">
                </button>
            </div>

            <div class="modal-body">
                <label class="excel-upload-area" for="inputImportarExcel">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <strong>Clique para escolher o arquivo</strong>
                    <span>ou arraste e solte aqui</span>
                    <small id="nomeArquivoExcel">Nenhum arquivo selecionado</small>
                </label>

                <div
                    class="excel-import-aviso"
                    id="avisoResumoImportacao"
                    hidden>
                </div>

                <input
                    type="file"
                    id="inputImportarExcel"
                    accept=".xlsx,.xls,.csv"
                    hidden>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    id="btnConfirmarImportarExcel"
                    disabled>
                    Importar
                </button>
            </div>

        </div>

    </div>

</div>
