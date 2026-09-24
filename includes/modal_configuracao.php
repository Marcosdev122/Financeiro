<div
    class="config-modal-overlay"
    id="modalConfiguracao"
    hidden>
    <div class="config-modal" role="dialog" aria-modal="true" aria-labelledby="tituloModalConfig">
        <div class="config-modal-header">
            <div class="config-modal-gear">
                <i class="bi bi-gear-fill"></i>
            </div>
            <div>
                <h5 id="tituloModalConfig">Configurações</h5>
                <p>Escolha uma opção para ajustar o sistema</p>
            </div>
            <button
                type="button"
                class="config-modal-close"
                id="btnFecharConfig"
                aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="config-modal-options">
            <button type="button" class="config-option" data-opcao="categorias">
                <i class="bi bi-tags"></i>
                <span>
                    <strong>Categorias</strong>
                    <small>Receitas e despesas</small>
                </span>
            </button>
            <button type="button" class="config-option" data-opcao="periodo">
                <i class="bi bi-calendar-range"></i>
                <span>
                    <strong>Período padrão</strong>
                    <small>Filtro inicial do dashboard</small>
                </span>
            </button>
            <button type="button" class="config-option" data-opcao="aparencia">
                <i class="bi bi-palette"></i>
                <span>
                    <strong>Aparência</strong>
                    <small>Cores e layout</small>
                </span>
            </button>
            <button type="button" class="config-option" data-opcao="dados">
                <i class="bi bi-database"></i>
                <span>
                    <strong>Dados</strong>
                    <small>Movimentações e backup</small>
                </span>
            </button>
            <button type="button" class="config-option" data-opcao="sobre">
                <i class="bi bi-info-circle"></i>
                <span>
                    <strong>Sobre</strong>
                    <small>Sistema Financeiro</small>
                </span>
            </button>
            <a
                href="/Financeiro/meus-tickets/index.php"
                class="config-option config-option-link"
                id="linkMeusTickets">
                <i class="bi bi-chat-left-text"></i>
                <span>
                    <strong>Meus tickets</strong>
                    <small>Protocolos e suporte</small>
                </span>
            </a>
        </div>

        <div class="config-modal-detail" id="configDetalhe">
            <p class="config-detail-vazio">Selecione uma opção acima.</p>
        </div>
    </div>
</div>
