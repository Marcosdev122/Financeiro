<div
    class="modal fade"
    id="modalSuporte"
    tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content suporte-modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="suporte-modal-titulo">
                    <span class="suporte-modal-icone">
                        <i class="bi bi-headset"></i>
                    </span>
                    <div>
                        <h5 class="modal-title mb-0">Fale conosco</h5>
                        <p class="text-muted small mb-0">Gera um ticket e abre o WhatsApp</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="suporte-motivos" id="listaMotivosSuporte">
                    <button
                        type="button"
                        class="suporte-motivo"
                        data-motivo="Problema no dashboard">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Problema no dashboard / gráficos</span>
                    </button>
                    <button
                        type="button"
                        class="suporte-motivo"
                        data-motivo="Erro na importação">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Erro ao importar Excel</span>
                    </button>
                    <button
                        type="button"
                        class="suporte-motivo"
                        data-motivo="Dúvida receitas/despesas">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Dúvida sobre receitas ou despesas</span>
                    </button>
                    <button
                        type="button"
                        class="suporte-motivo"
                        data-motivo="Sugestão">
                        <i class="bi bi-lightbulb"></i>
                        <span>Sugestão de melhoria</span>
                    </button>
                    <button
                        type="button"
                        class="suporte-motivo"
                        data-motivo="Outro">
                        <i class="bi bi-chat-left-text"></i>
                        <span>Outro</span>
                    </button>
                </div>

                <div class="mt-3">
                    <label class="form-label" for="mensagemSuporte">Detalhe (opcional)</label>
                    <textarea
                        class="form-control"
                        id="mensagemSuporte"
                        rows="3"
                        maxlength="300"
                        placeholder="Descreva em poucas palavras..."></textarea>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button
                    type="button"
                    class="btn btn-success"
                    id="btnEnviarSuporte"
                    disabled>
                    <i class="bi bi-whatsapp me-1"></i>
                    Abrir ticket
                </button>
            </div>
        </div>
    </div>
</div>
