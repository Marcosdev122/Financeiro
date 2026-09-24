<div
    class="modal fade"
    id="modalMovimentacao"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="tituloModalMovimentacao">
                    Nova movimentação
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar">
                </button>

            </div>

            <div class="modal-body">

                <form id="formMovimentacao">

                    <input
                        type="hidden"
                        name="id"
                        id="idMovimentacao">

                    <input
                        type="hidden"
                        name="tipo"
                        id="tipo">

                    <div class="mb-3">

                        <label class="form-label" for="categoria">
                            Categoria
                        </label>

                        <select
                            class="form-select"
                            name="categoria"
                            id="categoria">

                            <option value="">Selecione</option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label" for="descricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            name="descricao"
                            id="descricao"
                            rows="3"
                            maxlength="255"
                            placeholder="Ex.: Cinema com a família"></textarea>

                    </div>

                    <div class="mb-3">

                        <label class="form-label" for="valor">
                            Valor
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="valor"
                            id="valor"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="R$ 0,00"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label" for="data">
                            Data
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="data"
                            id="data"
                            max="<?= date('Y-m-d') ?>"
                            required>

                    </div>

                </form>

                <p
                    class="movimentacao-atualizado-info text-muted small mb-0"
                    id="infoCriadoMovimentacao"
                    hidden>
                    Criado em:
                    <strong id="textoCriadoMovimentacao"></strong>
                </p>

                <p
                    class="movimentacao-atualizado-info text-muted small mb-0"
                    id="infoAtualizadoMovimentacao"
                    hidden>
                    Atualizado em:
                    <strong id="textoAtualizadoMovimentacao"></strong>
                </p>

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
                    id="btnSalvarMovimentacao">
                    Salvar
                </button>

            </div>

        </div>

    </div>

</div>
