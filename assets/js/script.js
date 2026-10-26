let tabelaMovimentacoes = null;
let graficoEvolucao = null;
let graficoSaldo = null;
let categoriasPorTipo = null;
let graficoReceitasCategoria = null;
let graficoDespesasCategoria = null;
let graficoComparacaoAnos = null;
let anosDisponiveis = [];
let dadosComparacaoAnos = null;
let graficoFluxoCaixa = null;
let dadosGraficosCategoria = null;

const BASE_MOVIMENTACOES = "/Financeiro/movimentacoes";
const BASE_DASHBOARD = "/Financeiro/dashboard";
const CHAVE_TEMA = "financeiro-tema-v2";

function temaAtual() {
    const tema = document.documentElement.getAttribute("data-tema");
    return tema === "escuro" ? "escuro" : "claro";
}

function atualizarBotaoTema() {
    const escuro = temaAtual() === "escuro";
    const $texto = $("#textoTema");

    if ($texto.length) {
        $texto.text(escuro ? "Tema claro" : "Tema escuro");
    }

    $("#btnAlternarTema").attr(
        "title",
        escuro ? "Mudar para tema claro" : "Mudar para tema escuro"
    );
}

function aplicarTema(tema, recarregarGraficos) {
    const valor = tema === "escuro" ? "escuro" : "claro";

    document.documentElement.setAttribute("data-tema", valor);

    try {
        localStorage.setItem(CHAVE_TEMA, valor);
    } catch (e) {
        // ignore
    }

    atualizarBotaoTema();

    if (recarregarGraficos && $("#totalReceitas").length) {
        recarregarDashboard();
    } else if (recarregarGraficos && $("#graficoComparacaoAnos").length) {
        carregarComparacaoAnos();
    }
}

function inicializarTema() {
    let tema = "claro";

    try {
        tema = localStorage.getItem(CHAVE_TEMA) || "claro";
    } catch (e) {
        tema = "claro";
    }

    aplicarTema(tema, false);
}

function alternarTema() {
    aplicarTema(temaAtual() === "escuro" ? "claro" : "escuro", true);
}

$(document).on("click", "#btnAlternarTema", function () {
    alternarTema();
});

function destruirGrafico(instancia) {
    if (!instancia) {
        return null;
    }

    try {
        if (typeof instancia.dispose === "function") {
            instancia.dispose();
        }
    } catch (erro) {
        console.log(erro);
    }

    return null;
}

function obterInstanciaEcharts(elId, instanciaAtual) {
    const el = document.getElementById(elId);

    if (!el || typeof echarts === "undefined") {
        return null;
    }

    if (instanciaAtual && typeof instanciaAtual.isDisposed === "function" && !instanciaAtual.isDisposed()) {
        return instanciaAtual;
    }

    return echarts.init(el);
}

function dataZoomEcharts(quantidade, limite, eixo) {
    if (quantidade <= limite) {
        return [];
    }

    const end = Math.max(8, Math.min(100, (limite / quantidade) * 100));
    const eixoKey = eixo === "y" ? "yAxisIndex" : "xAxisIndex";
    const zoomBase = {};
    zoomBase[eixoKey] = 0;
    zoomBase.start = 0;
    zoomBase.end = end;

    const inside = Object.assign({ type: "inside" }, zoomBase);
    const slider = Object.assign({
        type: "slider",
        brushSelect: false,
        borderColor: "#e5e7eb",
        fillerColor: "rgba(37, 99, 235, 0.12)",
        handleSize: 10,
        showDetail: false
    }, zoomBase);

    if (eixo === "y") {
        slider.width = 14;
        slider.right = 4;
    } else {
        slider.height = 16;
        slider.bottom = 4;
    }

    return [inside, slider];
}

function tooltipMoeda() {
    const escuro = temaAtual() === "escuro";

    return {
        trigger: "axis",
        backgroundColor: escuro ? "rgba(15, 23, 42, 0.96)" : "rgba(17, 24, 39, 0.92)",
        borderWidth: 0,
        textStyle: { color: "#fff", fontSize: 12 },
        valueFormatter: function (valor) {
            return formatarMoeda(valor);
        }
    };
}

function corEixoGrafico() {
    return temaAtual() === "escuro" ? "#94a3b8" : "#6b7280";
}

function corGradeGrafico() {
    return temaAtual() === "escuro"
        ? "rgba(148, 163, 184, 0.12)"
        : "rgba(148, 163, 184, 0.12)";
}

function corLabelGrafico() {
    return temaAtual() === "escuro" ? "#cbd5e1" : "#374151";
}

function eixoCategoria(labels, rotacao) {
    return {
        type: "category",
        data: labels,
        axisTick: { show: false },
        axisLine: { lineStyle: { color: temaAtual() === "escuro" ? "#334155" : "#e5e7eb" } },
        axisLabel: {
            color: corEixoGrafico(),
            fontSize: 11,
            rotate: rotacao || 0,
            hideOverlap: true
        }
    };
}

function eixoValorMoeda(opcoes) {
    const cfg = opcoes || {};

    return {
        type: "value",
        scale: !!cfg.scale,
        min: cfg.beginAtZero === false ? undefined : 0,
        axisLine: { show: false },
        axisTick: { show: false },
        splitLine: {
            lineStyle: { color: corGradeGrafico() }
        },
        axisLabel: {
            color: corEixoGrafico(),
            fontSize: 11,
            formatter: function (valor) {
                return formatarMoeda(valor);
            }
        }
    };
}

function rotuloMesAno(mes, ano) {
    return mes + "/" + ano;
}
function formatarValorPonto(valor) {
    return formatarMoeda(valor);
}

function formatarMoeda(valor) {
    return Number(valor).toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    });
}

function formatarMoedaEixo(valor) {
    const numero = Number(valor) || 0;
    const abs = Math.abs(numero);

    if (abs >= 1000000) {
        return "R$ " + (numero / 1000000).toLocaleString("pt-BR", {
            maximumFractionDigits: abs >= 10000000 ? 0 : 1
        }) + " mi";
    }

    if (abs >= 1000) {
        return "R$ " + (numero / 1000).toLocaleString("pt-BR", {
            maximumFractionDigits: abs >= 10000 ? 0 : 1
        }) + " mil";
    }

    return "R$ " + numero.toLocaleString("pt-BR", {
        maximumFractionDigits: 0
    });
}

function formatarNumeroBr(valor) {
    return Number(valor).toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function mascaraMoeda(valor) {
    let digitos = String(valor).replace(/\D/g, "");

    if (!digitos) {
        return "";
    }

    // Evita travar o JS com números absurdamente longos (sem “teto” útil de valor)
    if (digitos.length > 18) {
        digitos = digitos.slice(-18);
    }

    const numero = Number(digitos) / 100;

    if (!isFinite(numero)) {
        return "";
    }

    return formatarMoeda(numero);
}

function valorMascaraParaNumero(texto) {
    return parseValorLinha(texto);
}

function parseValorLinha(texto) {
    return parseFloat(
        String(texto)
            .replace("R$", "")
            .replace(/\./g, "")
            .replace(",", ".")
            .replace("+", "")
            .replace("-", "")
            .trim()
    ) || 0;
}

function tipoPaginaAtual() {
    return $("#tabelaMovimentacoes").data("tipo") || "";
}

function garantirOutrosNoFim(lista) {
    const categorias = (lista || []).filter(function (item) {
        return item !== "Outros";
    });

    categorias.push("Outros");
    return categorias;
}

function carregarCategorias(callback) {
    if (categoriasPorTipo !== null) {
        if (typeof callback === "function") {
            callback();
        }
        return;
    }

    $.ajax({
        url: BASE_MOVIMENTACOES + "/categorias.php",
        type: "GET",
        dataType: "json",
        success: function (res) {
            if (res.status === "sucesso") {
                categoriasPorTipo = res.dados;
            } else {
                categoriasPorTipo = {
                    Receita: ["Outros"],
                    Despesa: ["Outros"]
                };
            }

            if (typeof callback === "function") {
                callback();
            }
        },
        error: function () {
            categoriasPorTipo = {
                Receita: ["Outros"],
                Despesa: ["Outros"]
            };

            if (typeof callback === "function") {
                callback();
            }
        }
    });
}

function preencherSelectCategorias(tipo, categoriaSelecionada) {
    const select = $("#categoria");
    const lista = garantirOutrosNoFim(
        (categoriasPorTipo && categoriasPorTipo[tipo]) || []
    );

    select.html('<option value="">Selecione</option>');

    lista.forEach(function (nome) {
        select.append(
            `<option value="${nome}">${nome}</option>`
        );
    });

    if (categoriaSelecionada) {
        if (lista.indexOf(categoriaSelecionada) === -1) {
            select.append(
                `<option value="${categoriaSelecionada}">${categoriaSelecionada}</option>`
            );
        }
        select.val(categoriaSelecionada);
    }
}

function atualizarResumoMovimentacoes() {
    if (!tabelaMovimentacoes) {
        return;
    }

    let total = 0;
    let quantidade = 0;

    tabelaMovimentacoes
        .rows({ search: "applied" })
        .every(function () {
            const valor = parseValorLinha(
                $(this.node()).find("td").eq(1).text()
            );
            total += valor;
            quantidade += 1;
        });

    $("#resumoTotal").text(formatarMoeda(total));

    if ($("#resumoMovimentacoes").length) {
        $("#resumoMovimentacoes").text(quantidade);
    }
}

function sincronizarLarguraTabelaMovimentacoes() {
    const $table = $("#tabelaMovimentacoes");
    if (!$table.length) {
        return;
    }

    $table.find("colgroup").remove();
    $table.css({
        width: "100%",
        minWidth: "100%",
        maxWidth: "100%"
    });

    $table.find("th, td").each(function () {
        this.style.width = "";
        this.style.minWidth = "";
        this.style.maxWidth = "";
    });

    $("#tabelaMovimentacoes_wrapper, #tabelaMovimentacoes_wrapper .dt-container, #tabelaMovimentacoes_wrapper .dt-layout-table")
        .css({ width: "100%", maxWidth: "100%" });
}

function carregarMovimentacoes() {
    const tipo = tipoPaginaAtual();
    const periodo = parametrosPeriodoLista();

    if (!tipo) {
        return;
    }

    $.ajax({
        url: BASE_MOVIMENTACOES + "/listar.php",
        type: "GET",
        data: {
            tipo: tipo,
            inicio: periodo.inicio,
            fim: periodo.fim
        },
        success: function (dados) {
            if (tabelaMovimentacoes !== null) {
                tabelaMovimentacoes.clear().destroy();
                tabelaMovimentacoes = null;
            }

            $("#tbodyMovimentacoes").html(dados);

            tabelaMovimentacoes = $("#tabelaMovimentacoes").DataTable({
                pageLength: 10,
                autoWidth: false,
                lengthMenu: [
                    [5, 10, 25, 50],
                    [5, 10, 25, 50]
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Pesquisar categoria...",
                    lengthMenu: "_MENU_ por página",
                    info: "Mostrando _START_–_END_ de _TOTAL_ registros",
                    infoEmpty: "Nenhum registro",
                    zeroRecords: "Nenhum registro encontrado",
                    paginate: {
                        first: "«",
                        last: "»",
                        previous: "‹",
                        next: "›"
                    }
                },
                order: [[2, "desc"]],
                columnDefs: [
                    {
                        targets: 3,
                        className: "col-atualizado"
                    },
                    {
                        targets: 1,
                        className: "text-end text-nowrap"
                    },
                    {
                        targets: 2,
                        className: "text-nowrap"
                    },
                    {
                        targets: 4,
                        orderable: false,
                        searchable: false
                    }
                ],
                initComplete: function () {
                    sincronizarLarguraTabelaMovimentacoes();
                },
                drawCallback: function () {
                    $("#tabelaMovimentacoes_wrapper").addClass("lista-datatable");
                    sincronizarLarguraTabelaMovimentacoes();
                }
            });

            sincronizarLarguraTabelaMovimentacoes();

            tabelaMovimentacoes.on("draw", function () {
                atualizarResumoMovimentacoes();
            });

            atualizarResumoMovimentacoes();
        },
        error: function (xhr) {
            console.log(
                "Erro ao carregar movimentações:",
                xhr.responseText
            );
        }
    });
}

function abrirModalMovimentacao(tipo, titulo, categoriaSelecionada) {
    $("#formMovimentacao")[0].reset();
    $("#idMovimentacao").val("");
    $("#tipo").val(tipo);
    $("#valor").val("");
    $("#descricao").val("");
    $("#tituloModalMovimentacao").text(titulo);
    atualizarDatasMovimentacao("", "");

    const hoje = hojeISO();
    $("#data").attr("max", hoje).val(hoje);

    carregarCategorias(function () {
        preencherSelectCategorias(tipo, categoriaSelecionada || "");

        const modalEl = document.getElementById("modalMovimentacao");
        const modal = new bootstrap.Modal(modalEl);

        modal.show();
    });
}

const PALETA_COMPARACAO = [
    { receita: "#86efac", despesa: "#fca5a5", saldo: "#93c5fd" },
    { receita: "#4ade80", despesa: "#f87171", saldo: "#60a5fa" },
    { receita: "#bbf7d0", despesa: "#fecaca", saldo: "#bfdbfe" },
    { receita: "#22c55e", despesa: "#ef4444", saldo: "#3b82f6" },
    { receita: "#15803d", despesa: "#b91c1c", saldo: "#1d4ed8" }
];

const PADROES_TRACEJADO = [
    [6, 4],
    [2, 3],
    [8, 4, 2, 4],
    [4, 2],
    [10, 5]
];

function montarLegendaAnos(containerId, chart, corFixa) {
    const container = document.getElementById(containerId);

    if (!container || !chart) {
        return;
    }

    container.innerHTML = "";

    const grupos = [];

    chart.data.datasets.forEach(function (dataset, indice) {
        const ano = String(dataset.ano || "");

        if (!ano) {
            return;
        }

        let grupo = grupos.find(function (item) {
            return item.ano === ano;
        });

        if (!grupo) {
            grupo = {
                ano: ano,
                indices: [],
                dash: dataset.borderDash,
                cor: dataset.borderColor
            };
            grupos.push(grupo);
        }

        grupo.indices.push(indice);
    });

    grupos.forEach(function (grupo) {
        const visivel = grupo.indices.some(function (indice) {
            return chart.isDatasetVisible(indice);
        });
        const botao = document.createElement("button");
        const linha = document.createElement("span");

        botao.type = "button";
        botao.className = "legenda-ano" + (visivel ? "" : " inativo");
        linha.className = "legenda-ano-linha";
        linha.style.borderTopColor = corFixa || grupo.cor;
        linha.style.borderTopStyle = grupo.dash ? "dashed" : "solid";

        botao.appendChild(linha);
        botao.appendChild(document.createTextNode(grupo.ano));

        botao.addEventListener("click", function () {
            const mostrar = !chart.isDatasetVisible(grupo.indices[0]);

            grupo.indices.forEach(function (indice) {
                chart.setDatasetVisibility(indice, mostrar);
            });

            chart.update();
            montarLegendaAnos(containerId, chart, corFixa);
        });

        container.appendChild(botao);
    });
}

function dataISO(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, "0");
    const dia = String(data.getDate()).padStart(2, "0");

    return ano + "-" + mes + "-" + dia;
}

function montarData(lado) {
    if (lado === "inicio") {
        return $("#dataInicio").val();
    }

    return $("#dataFim").val();
}

function montarDataLista(lado) {
    if (lado === "inicio") {
        return $("#listaDataInicio").val();
    }

    return $("#listaDataFim").val();
}

function parametrosPeriodoLista() {
    return {
        inicio: montarDataLista("inicio"),
        fim: montarDataLista("fim")
    };
}

function inicializarPeriodoLista() {
    const hoje = hojeISO();

    $("#listaDataInicio, #listaDataFim").attr("max", hoje);
    $("#listaDataInicio").val(hoje.slice(0, 4) + "-01-01");
    $("#listaDataFim").val(hoje);
}

function ajustarPeriodoLista() {
    const hoje = hojeISO();

    $("#listaDataInicio, #listaDataFim").attr("max", hoje);

    limitarDataHoje("#listaDataInicio");
    limitarDataHoje("#listaDataFim");

    const inicio = montarDataLista("inicio");
    const fim = montarDataLista("fim");

    if (inicio && fim && inicio > fim) {
        $("#listaDataFim").val(inicio);
    }
}

function atualizarSubtituloPeriodoLista() {
    const inicio = montarDataLista("inicio");
    const fim = montarDataLista("fim");

    if (!inicio || !fim || !$("#subtituloPeriodoLista").length) {
        return;
    }

    const totalAnos = totalAnosPeriodo(inicio, fim);
    const texto = formatarDataBr(inicio) + " até " + formatarDataBr(fim)
        + " · " + textoAnos(totalAnos);

    $("#subtituloPeriodoLista").text("Período: " + texto);
}

function aplicarPeriodoLista() {
    ajustarPeriodoLista();
    atualizarSubtituloPeriodoLista();
    carregarMovimentacoes();

    const modal = bootstrap.Modal.getInstance(
        document.getElementById("modalPeriodoLista")
    );

    if (modal) {
        modal.hide();
    }
}

function formatarDataBr(dataIso) {
    const partes = String(dataIso).split("-");

    if (partes.length !== 3) {
        return dataIso;
    }

    return partes[2] + "/" + partes[1] + "/" + partes[0];
}

function formatarDataHoraBr(valor) {
    if (!valor) {
        return "";
    }

    const normalizado = String(valor).trim().replace(" ", "T");
    const data = new Date(normalizado);

    if (isNaN(data.getTime())) {
        return valor;
    }

    const dia = String(data.getDate()).padStart(2, "0");
    const mes = String(data.getMonth() + 1).padStart(2, "0");
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, "0");
    const minuto = String(data.getMinutes()).padStart(2, "0");

    return dia + "/" + mes + "/" + ano + " " + hora + ":" + minuto;
}

function atualizarDatasMovimentacao(criado, atualizado) {
    const $infoCriado = $("#infoCriadoMovimentacao");
    const $textoCriado = $("#textoCriadoMovimentacao");
    const $infoAtualizado = $("#infoAtualizadoMovimentacao");
    const $textoAtualizado = $("#textoAtualizadoMovimentacao");

    if (!criado) {
        $infoCriado.attr("hidden", true);
        $textoCriado.text("");
    } else {
        $textoCriado.text(formatarDataHoraBr(criado));
        $infoCriado.removeAttr("hidden");
    }

    if (!atualizado) {
        $infoAtualizado.attr("hidden", true);
        $textoAtualizado.text("");
    } else {
        $textoAtualizado.text(formatarDataHoraBr(atualizado));
        $infoAtualizado.removeAttr("hidden");
    }
}

function mediaValores(valores) {
    if (!valores.length) {
        return 0;
    }

    const soma = valores.reduce(function (total, valor) {
        return total + valor;
    }, 0);

    return soma / valores.length;
}

function totalAnosPeriodo(inicio, fim, fallback) {
    if (typeof fallback === "number" && fallback > 0) {
        return fallback;
    }

    const anoInicio = parseInt(String(inicio).slice(0, 4), 10);
    const anoFim = parseInt(String(fim).slice(0, 4), 10);

    if (isNaN(anoInicio) || isNaN(anoFim)) {
        return 1;
    }

    return Math.abs(anoFim - anoInicio) + 1;
}

function textoAnos(total) {
    return total === 1 ? "1 ano" : total + " anos";
}

function montarTextoPeriodo(inicio, fim, totalAnos) {
    return formatarDataBr(inicio) + " até " + formatarDataBr(fim)
        + " · " + textoAnos(totalAnos)
        + " · por mês";
}

function parametrosDashboard() {
    return {
        inicio: montarData("inicio"),
        fim: montarData("fim")
    };
}

function hojeISO() {
    return dataISO(new Date());
}

function limitarDataHoje(campo) {
    const hoje = hojeISO();
    const valor = $(campo).val();

    if (valor && valor > hoje) {
        $(campo).val(hoje);
    }
}

function inicializarPeriodo() {
    const hoje = hojeISO();

    $("#dataInicio, #dataFim").attr("max", hoje);
    $("#dataInicio").val(hoje.slice(0, 4) + "-01-01");
    $("#dataFim").val(hoje);
}

function ajustarPeriodo() {
    const hoje = hojeISO();

    $("#dataInicio, #dataFim").attr("max", hoje);

    limitarDataHoje("#dataInicio");
    limitarDataHoje("#dataFim");

    const inicio = montarData("inicio");
    const fim = montarData("fim");

    if (inicio && fim && inicio > fim) {
        $("#dataFim").val(inicio);
    }
}

function atualizarFiltrosPeriodo() {
    const inicio = montarData("inicio");
    const fim = montarData("fim");

    if (!inicio || !fim) {
        return;
    }

    const totalAnos = totalAnosPeriodo(inicio, fim);
    const texto = montarTextoPeriodo(inicio, fim, totalAnos);

    $("#subtituloEvolucao").text(texto);
    $("#subtituloFluxoCaixa").text("Fluxo de caixa de " + texto);
    $("#subtituloSaldo").text("Saldo acumulado de " + texto);
}

function atualizarTextoComparacao() {
    const selecionados = [];

    $("#filtroAnosMenu input:checked").each(function () {
        selecionados.push($(this).val());
    });

    const texto = $("#filtroAnosTexto");

    if (!selecionados.length) {
        texto.text("Nenhum");
        return;
    }

    if (selecionados.length === 1) {
        texto.text(selecionados[0]);
        return;
    }

    texto.text(selecionados.length + " anos");
}

function montarCheckboxesComparacao(anos, anoPrincipal) {
    const menu = $("#filtroAnosMenu");
    const selecionados = [];

    menu.find("input:checked").each(function () {
        selecionados.push(String($(this).val()));
    });

    menu.empty();
    $("#filtroAnosComparacao").removeClass("aberto");

    const outrosAnos = anos.filter(function (ano) {
        return String(ano) !== String(anoPrincipal);
    });

    if (outrosAnos.length === 0) {
        menu.html(
            '<span class="anos-comparacao-vazio">Sem outros anos no banco</span>'
        );
        $("#filtroAnosTexto").text("Nenhum");
        $("#filtroAnosToggle").prop("disabled", true);
        return;
    }

    $("#filtroAnosToggle").prop("disabled", false);

    outrosAnos.forEach(function (ano) {
        const marcado = selecionados.indexOf(String(ano)) !== -1;

        menu.append(`
            <label class="ano-comparacao-item">
                <input
                    type="checkbox"
                    value="${ano}"
                    ${marcado ? "checked" : ""}>
                ${ano}
            </label>
        `);
    });

    if (menu.find("input:checked").length === 0) {
        menu.find("input").first().prop("checked", true);
    }

    atualizarTextoComparacao();
}

function carregarAnos(callback) {
    const anoAtual = new Date().getFullYear();

    $.ajax({
        url: BASE_DASHBOARD + "/anos.php",
        type: "GET",
        dataType: "json",
        success: function (res) {
            let anos = (res.status === "sucesso" && res.dados)
                ? res.dados
                : [];

            if (anos.indexOf(anoAtual) === -1) {
                anos.push(anoAtual);
            }

            anos = anos
                .map(function (ano) {
                    return parseInt(ano, 10);
                })
                .filter(function (ano) {
                    return !isNaN(ano);
                })
                .sort(function (a, b) {
                    return b - a;
                });

            montarCheckboxesComparacao(anos, anoPrincipalPeriodo());
            anosDisponiveis = anos;

            if (typeof callback === "function") {
                callback();
            }
        },
        error: function () {
            $("#filtroAnosMenu").html(
                '<span class="anos-comparacao-vazio">Sem anos disponíveis</span>'
            );
            $("#filtroAnosTexto").text("Nenhum");
            $("#filtroAnosToggle").prop("disabled", true);

            if (typeof callback === "function") {
                callback();
            }
        }
    });
}

function anoPrincipalPeriodo() {
    return parseInt((montarData("inicio") || "").slice(0, 4), 10)
        || new Date().getFullYear();
}

function textoComparacao(atual, comparado, rotulo, moeda) {
    if (!rotulo) {
        return "";
    }

    const diferenca = atual - comparado;
    const sinal = diferenca > 0 ? "+" : "";
    const valor = moeda
        ? formatarMoeda(Math.abs(diferenca))
        : String(Math.abs(diferenca));

    if (diferenca === 0) {
        return "Igual a " + rotulo;
    }

    if (diferenca > 0) {
        return sinal + valor + " vs " + rotulo;
    }

    return "-" + valor + " vs " + rotulo;
}

function aplicarClasseComparacao(seletor, atual, comparado, temComparacao) {
    const el = $(seletor);

    el.removeClass("comparacao-up comparacao-down comparacao-igual");

    if (!temComparacao) {
        return;
    }

    if (atual > comparado) {
        el.addClass("comparacao-up");
    } else if (atual < comparado) {
        el.addClass("comparacao-down");
    } else {
        el.addClass("comparacao-igual");
    }
}

function carregarDashboard() {
    if (!$("#totalReceitas").length) {
        return;
    }

    const params = parametrosDashboard();

    $.ajax({
        url: BASE_DASHBOARD + "/dados.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function (res) {
            if (res.status !== "sucesso") {
                return;
            }

            const dados = res.dados;

            $("#totalReceitas").text(formatarMoeda(dados.receitas));
            $("#totalDespesas").text(formatarMoeda(dados.despesas));
            $("#saldo").text(formatarMoeda(dados.saldo));
            $("#totalMovimentacoes").text(dados.movimentacoes);
            $("#comparacaoReceitas").text("Total recebido");
            $("#comparacaoDespesas").text("Total gasto");
            $("#comparacaoSaldo").text("Saldo disponível");
            $("#comparacaoMovimentacoes").text("Registros no período");
            aplicarClasseComparacao("#comparacaoReceitas", 0, 0, false);
            aplicarClasseComparacao("#comparacaoDespesas", 0, 0, false);
            aplicarClasseComparacao("#comparacaoSaldo", 0, 0, false);
            aplicarClasseComparacao("#comparacaoMovimentacoes", 0, 0, false);

            const totalAnos = totalAnosPeriodo(
                dados.inicio,
                dados.fim,
                parseInt(dados.totalAnos, 10)
            );
            const textoPeriodo = montarTextoPeriodo(
                dados.inicio,
                dados.fim,
                totalAnos
            );

            $("#subtituloEvolucao").text(textoPeriodo);
            $("#subtituloFluxoCaixa").text("Fluxo de caixa de " + textoPeriodo);
            $("#subtituloSaldo").text("Saldo acumulado de " + textoPeriodo);
        },
        error: function (xhr) {
            console.log("ERRO DASHBOARD:", xhr.responseText);
        }
    });
}

function serieLinhaEcharts(nome, dados, cor, opcoes) {
    const cfg = opcoes || {};
    const valorAbsolutoNoLabel = !!cfg.valorAbsolutoNoLabel;

    const pontos = (dados || []).map(function (valor) {
        const atual = Number(valor) || 0;

        return {
            value: atual,
            label: {
                show: true,
                position: "top",
                distance: 8,
                color: cor,
                fontSize: 9,
                fontWeight: 600,
                backgroundColor: temaAtual() === "escuro"
                    ? "rgba(30, 41, 59, 0.92)"
                    : "rgba(255, 255, 255, 0.92)",
                padding: [2, 4],
                borderRadius: 3,
                formatter: function (params) {
                    const bruto = Number(params.value) || 0;
                    const exibir = valorAbsolutoNoLabel ? Math.abs(bruto) : bruto;

                    if (Math.abs(exibir) >= 1000) {
                        return formatarMoedaEixo(exibir);
                    }

                    return formatarValorPonto(exibir);
                }
            }
        };
    });

    return {
        name: nome,
        type: "line",
        data: pontos,
        smooth: false,
        symbol: "circle",
        symbolSize: 7,
        showSymbol: true,
        connectNulls: false,
        lineStyle: { width: 2.5, color: cor },
        itemStyle: { color: cor, borderColor: "#fff", borderWidth: 2 },
        label: {
            show: true,
            position: "top",
            distance: 8
        },
        z: cfg.papel === "receita" ? 3 : 2,
        emphasis: { focus: "series" }
    };
}

function serieMediaEcharts(nome, mediaValor, tamanho, cor) {
    const dados = [];

    for (let i = 0; i < tamanho; i++) {
        dados.push(mediaValor);
    }

    return {
        name: nome,
        type: "line",
        data: dados,
        symbol: "none",
        lineStyle: { width: 2, type: "dashed", color: cor },
        itemStyle: { color: cor },
        label: { show: false },
        tooltip: {
            valueFormatter: function (valor) {
                return formatarMoeda(valor);
            }
        },
        silent: true,
        z: 1
    };
}

function carregarGraficoEvolucao() {
    if (!document.getElementById("graficoEvolucao")) {
        return;
    }

    const params = parametrosDashboard();

    $.ajax({
        url: BASE_DASHBOARD + "/dados.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function (res) {
            if (res.status !== "sucesso") {
                return;
            }

            const evolucao = res.dados.evolucao || [];
            const meses = [];
            const receitas = [];
            const despesas = [];

            evolucao.forEach(function (item) {
                meses.push(item.mes);
                const receita = Number(item.receitas);
                const despesa = Number(item.despesas);
                receitas.push(isFinite(receita) ? receita : 0);
                despesas.push(isFinite(despesa) ? despesa : 0);
            });

            const despesasNegativas = despesas.map(function (valor) {
                return -Math.abs(Number(valor) || 0);
            });

            const mediaReceitas = mediaValores(receitas);
            const mediaDespesas = -Math.abs(mediaValores(despesas));

            const maxPositivo = Math.max.apply(
                null,
                receitas.concat([mediaReceitas, 0])
            );
            const minNegativo = Math.min.apply(
                null,
                despesasNegativas.concat([mediaDespesas, 0])
            );

            const paddingPos = Math.max(Math.abs(maxPositivo) * 0.12, 100);
            const paddingNeg = Math.max(Math.abs(minNegativo) * 0.12, 100);

            graficoEvolucao = obterInstanciaEcharts("graficoEvolucao", graficoEvolucao);

            if (!graficoEvolucao) {
                return;
            }

            const host = document.getElementById("graficoEvolucao");
            const container = host ? host.closest(".chart-container") : null;
            const mobile = window.matchMedia("(max-width: 768px)").matches;

            if (container) {
                container.classList.add("chart-scroll-y", "chart-container-evolucao");
            }

            // No PC, altura maior quando a faixa negativa é grande.
            // No celular, o host preenche o container (CSS) — sem altura inline alta.
            if (host) {
                if (mobile) {
                    host.style.height = "";
                    host.style.minHeight = "";
                } else {
                    const faixa = Math.abs(maxPositivo) + Math.abs(minNegativo);
                    const alturaHost = faixa > 0 && Math.abs(minNegativo) > Math.abs(maxPositivo) * 3
                        ? 640
                        : 520;
                    host.style.height = alturaHost + "px";
                    host.style.minHeight = alturaHost + "px";
                }
            }

            const rotacao = mobile ? (meses.length > 6 ? 35 : 0) : (meses.length > 12 ? 40 : 0);

            graficoEvolucao.setOption({
                animation: false,
                grid: {
                    left: mobile ? 8 : 12,
                    right: mobile ? 16 : 48,
                    top: mobile ? 24 : 40,
                    bottom: mobile ? (rotacao ? 56 : 40) : 44,
                    containLabel: true
                },
                tooltip: {
                    trigger: "axis",
                    backgroundColor: "rgba(17, 24, 39, 0.92)",
                    borderWidth: 0,
                    textStyle: { color: "#fff", fontSize: mobile ? 11 : 11 },
                    valueFormatter: function (valor) {
                        return formatarMoeda(valor);
                    }
                },
                legend: { show: false },
                dataZoom: dataZoomEcharts(meses.length, mobile ? 5 : 12, "x"),
                xAxis: Object.assign(eixoCategoria(meses, rotacao), {
                    axisLine: {
                        onZero: true,
                        lineStyle: { color: "#111827", width: 1.5 }
                    },
                    axisLabel: {
                        color: corEixoGrafico(),
                        fontSize: mobile ? 9 : 10,
                        rotate: rotacao,
                        hideOverlap: true,
                        margin: mobile ? 8 : 12
                    }
                }),
                yAxis: {
                    type: "value",
                    min: minNegativo - paddingNeg,
                    max: maxPositivo + paddingPos,
                    scale: false,
                    axisLine: { show: false },
                    axisTick: { show: false },
                    splitLine: {
                        lineStyle: { color: corGradeGrafico() }
                    },
                    axisLabel: {
                        color: corEixoGrafico(),
                        fontSize: mobile ? 9 : 10,
                        hideOverlap: true,
                        formatter: function (valor) {
                            return formatarMoedaEixo(valor);
                        }
                    }
                },
                series: [
                    serieLinhaEcharts("Receitas", receitas, "#16a34a", {
                        posicaoLabel: "top",
                        papel: "receita",
                        seriePar: despesasNegativas,
                        limiarProximo: mobile ? 999999 : 800
                    }),
                    serieLinhaEcharts("Despesas", despesasNegativas, "#dc2626", {
                        posicaoLabel: "top",
                        papel: "despesa",
                        seriePar: receitas,
                        limiarProximo: mobile ? 999999 : 800
                    }),
                    Object.assign(
                        serieMediaEcharts(
                            "Média receitas",
                            mediaReceitas,
                            meses.length,
                            "#86efac"
                        ),
                        {
                            markLine: {
                                silent: true,
                                symbol: "none",
                                label: { show: false },
                                lineStyle: {
                                    color: "#9ca3af",
                                    width: 1,
                                    type: "solid"
                                },
                                data: [{ yAxis: 0 }]
                            }
                        }
                    ),
                    serieMediaEcharts(
                        "Média despesas",
                        mediaDespesas,
                        meses.length,
                        "#fca5a5"
                    )
                ]
            }, true);

            graficoEvolucao.resize();
            if (mobile) {
                setTimeout(function () {
                    if (graficoEvolucao) {
                        graficoEvolucao.resize();
                    }
                }, 120);
            }

            // Centraliza o scroll na linha do zero
            if (container) {
                const ratio =
                    Math.abs(maxPositivo + paddingPos) /
                    Math.max(faixa + paddingPos + paddingNeg, 1);
                const scrollMax = Math.max(container.scrollHeight - container.clientHeight, 0);
                container.scrollTop = Math.round(scrollMax * (1 - Math.min(Math.max(ratio, 0.15), 0.85)));
            }
        }
    });
}

function carregarGraficoSaldo() {
    if (!document.getElementById("graficoSaldo")) {
        return;
    }

    const params = parametrosDashboard();

    $.ajax({
        url: BASE_DASHBOARD + "/dados.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function (res) {
            if (res.status !== "sucesso") {
                return;
            }

            const evolucao = res.dados.evolucao || [];
            const meses = [];
            const saldos = [];

            evolucao.forEach(function (item) {
                meses.push(item.mes);
                saldos.push(parseFloat(item.saldo));
            });

            graficoSaldo = obterInstanciaEcharts("graficoSaldo", graficoSaldo);

            if (!graficoSaldo) {
                return;
            }

            const rotacao = meses.length > 12 ? 45 : 0;

            graficoSaldo.setOption({
                animation: false,
                grid: {
                    left: 16,
                    right: 24,
                    top: 36,
                    bottom: meses.length > 8 ? 56 : 36,
                    containLabel: true
                },
                tooltip: tooltipMoeda(),
                legend: { show: false },
                dataZoom: dataZoomEcharts(meses.length, 8, "x"),
                xAxis: eixoCategoria(meses, rotacao),
                yAxis: eixoValorMoeda({ beginAtZero: false, scale: true }),
                series: [
                    serieLinhaEcharts("Saldo", saldos, "#2563eb", {
                        posicaoLabel: "top"
                    }),
                    serieMediaEcharts(
                        "Média",
                        mediaValores(saldos),
                        meses.length,
                        "#93c5fd"
                    )
                ]
            }, true);

            graficoSaldo.resize();
        }
    });
}

function carregarGraficoFluxoCaixa() {
    if (!document.getElementById("graficoFluxoCaixa")) {
        return;
    }

    const params = parametrosDashboard();

    $.ajax({
        url: BASE_DASHBOARD + "/dados.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function (res) {
            if (res.status !== "sucesso") {
                return;
            }

            const evolucao = res.dados.evolucao || [];
            const meses = evolucao.map(function (item) {
                return item.mes;
            });
            const fluxos = evolucao.map(function (item) {
                return parseFloat(item.fluxo) || 0;
            });

            graficoFluxoCaixa = obterInstanciaEcharts(
                "graficoFluxoCaixa",
                graficoFluxoCaixa
            );

            if (!graficoFluxoCaixa) {
                return;
            }

            graficoFluxoCaixa.setOption({
                animation: false,
                grid: {
                    left: 16,
                    right: 16,
                    top: 40,
                    bottom: 64,
                    containLabel: true
                },
                tooltip: {
                    trigger: "axis",
                    backgroundColor: "rgba(17, 24, 39, 0.92)",
                    borderWidth: 0,
                    textStyle: { color: "#fff", fontSize: 12 },
                    valueFormatter: function (valor) {
                        return formatarMoeda(valor);
                    }
                },
                legend: { show: false },
                dataZoom: dataZoomEcharts(meses.length, 12, "x"),
                xAxis: Object.assign(eixoCategoria(meses, 0), {
                    axisLabel: {
                        color: corEixoGrafico(),
                        fontSize: 11,
                        margin: 18,
                        hideOverlap: true
                    }
                }),
                yAxis: {
                    type: "value",
                    scale: true,
                    axisLabel: { show: false },
                    axisTick: { show: false },
                    axisLine: { show: false },
                    splitLine: { show: false }
                },
                series: [{
                    name: "Fluxo de caixa",
                    type: "bar",
                    data: fluxos.map(function (valor) {
                        const positivo = valor >= 0;
                        const cor = positivo ? "#22c55e" : "#ef4444";

                        return {
                            value: valor,
                            itemStyle: {
                                color: cor,
                                borderRadius: positivo
                                    ? [4, 4, 0, 0]
                                    : [0, 0, 4, 4]
                            },
                            label: {
                                show: !!valor,
                                position: positivo ? "top" : "bottom",
                                distance: 10,
                                color: cor,
                                fontSize: meses.length > 12 ? 9 : 10,
                                fontWeight: 600,
                                formatter: function (params) {
                                    if (!params.value) {
                                        return "";
                                    }

                                    return formatarNumeroBr(params.value);
                                }
                            }
                        };
                    }),
                    barMaxWidth: 52,
                    markLine: {
                        silent: true,
                        symbol: "none",
                        label: { show: false },
                        data: [{
                            yAxis: 0,
                            lineStyle: {
                                color: temaAtual() === "escuro" ? "#94a3b8" : "#111827",
                                width: 1,
                                type: "solid"
                            }
                        }]
                    },
                    label: { show: true },
                    labelLayout: { hideOverlap: true }
                }]
            }, true);

            graficoFluxoCaixa.resize();
        }
    });
}

function montarGraficoCategoria(elId, dados, cor, instanciaRef) {
    if (!document.getElementById(elId)) {
        return null;
    }

    if (!Array.isArray(dados)) {
        dados = [];
    }

    const labels = dados.map(function (item) {
        return item.categoria;
    });

    const valores = dados.map(function (item) {
        return parseFloat(item.total);
    });

    const chart = obterInstanciaEcharts(elId, instanciaRef);

    if (!chart) {
        return null;
    }

    const mobile = window.matchMedia("(max-width: 768px)").matches;
    const limiteZoom = mobile ? 4 : 6;

    chart.setOption({
        animation: false,
        grid: {
            left: mobile ? 4 : 8,
            right: mobile ? 56 : 80,
            top: 8,
            bottom: mobile ? 36 : 28,
            containLabel: true
        },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
            backgroundColor: "rgba(17, 24, 39, 0.92)",
            borderWidth: 0,
            textStyle: { color: "#fff", fontSize: mobile ? 11 : 12 },
            valueFormatter: function (valor) {
                return formatarMoeda(valor);
            }
        },
        legend: { show: false },
        dataZoom: dataZoomEcharts(labels.length, limiteZoom, "y"),
        xAxis: {
            type: "value",
            min: 0,
            scale: false,
            splitNumber: mobile ? 3 : 4,
            axisLabel: {
                color: corEixoGrafico(),
                fontSize: mobile ? 9 : 10,
                hideOverlap: true,
                interval: "auto",
                formatter: function (valor) {
                    return formatarMoedaEixo(valor);
                }
            },
            splitLine: {
                lineStyle: { color: corGradeGrafico() }
            }
        },
        yAxis: {
            type: "category",
            data: labels,
            inverse: true,
            axisTick: { show: false },
            axisLine: { show: false },
            axisLabel: {
                color: corLabelGrafico(),
                fontSize: mobile ? 10 : 12,
                width: mobile ? 72 : 90,
                overflow: "truncate"
            }
        },
        series: [{
            type: "bar",
            data: valores,
            barWidth: mobile ? 12 : 16,
            itemStyle: {
                color: cor,
                borderRadius: [0, 6, 6, 0]
            },
            label: {
                show: !mobile,
                position: "right",
                color: corLabelGrafico(),
                fontSize: 11,
                fontWeight: 600,
                formatter: function (params) {
                    if (!params.value) {
                        return "";
                    }

                    return formatarValorPonto(params.value);
                }
            }
        }]
    }, true);

    chart.resize();
    if (mobile) {
        setTimeout(function () {
            chart.resize();
        }, 120);
    }

    return chart;
}

function renderizarGraficosCategoria() {
    if (!dadosGraficosCategoria) {
        return;
    }

    const modoReceitas = $("#agrupamentoReceitasCategoria").val() || "categoria";
    const modoDespesas = $("#agrupamentoDespesasCategoria").val() || "categoria";

    const dadosReceitas = modoReceitas === "descricao"
        ? dadosGraficosCategoria.receitasPorDescricao
        : dadosGraficosCategoria.receitasPorCategoria;

    const dadosDespesas = modoDespesas === "descricao"
        ? dadosGraficosCategoria.despesasPorDescricao
        : dadosGraficosCategoria.despesasPorCategoria;

    graficoReceitasCategoria = montarGraficoCategoria(
        "graficoReceitasCategoria",
        dadosReceitas || [],
        "#16a34a",
        graficoReceitasCategoria
    );

    graficoDespesasCategoria = montarGraficoCategoria(
        "graficoDespesasCategoria",
        dadosDespesas || [],
        "#dc2626",
        graficoDespesasCategoria
    );
}

function carregarGraficosCategoria() {
    const params = parametrosDashboard();

    $.ajax({
        url: BASE_DASHBOARD + "/dados.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function (res) {
            if (res.status !== "sucesso" || !res.dados) {
                return;
            }

            dadosGraficosCategoria = res.dados;
            renderizarGraficosCategoria();
        }
    });
}

function renderizarGraficoComparacaoAnos() {
    if (!document.getElementById("graficoComparacaoAnos") || !dadosComparacaoAnos) {
        return;
    }

    const anos = (dadosComparacaoAnos.anos || []).map(String);
    const receitas = dadosComparacaoAnos.receitas || [];
    const despesas = dadosComparacaoAnos.despesas || [];
    const saldos = dadosComparacaoAnos.saldos || [];
    const muitosAnos = anos.length > 6;

    graficoComparacaoAnos = obterInstanciaEcharts(
        "graficoComparacaoAnos",
        graficoComparacaoAnos
    );

    if (!graficoComparacaoAnos) {
        return;
    }

    function serieBarra(nome, dadosSerie, cor) {
        return {
            name: nome,
            type: "bar",
            data: dadosSerie,
            barMaxWidth: muitosAnos ? 28 : 36,
            itemStyle: {
                color: cor,
                borderRadius: [4, 4, 0, 0]
            },
            label: {
                show: true,
                position: "top",
                color: cor,
                fontSize: muitosAnos ? 9 : 10,
                fontWeight: 600,
                formatter: function (params) {
                    if (!params.value) {
                        return "";
                    }

                    return formatarValorPonto(params.value);
                }
            },
            labelLayout: { hideOverlap: true }
        };
    }

    graficoComparacaoAnos.setOption({
        animation: false,
        grid: {
            left: 16,
            right: 16,
            top: 40,
            bottom: anos.length > 6 ? 56 : 28,
            containLabel: true
        },
        tooltip: tooltipMoeda(),
        legend: { show: false },
        dataZoom: dataZoomEcharts(anos.length, 6, "x"),
        xAxis: {
            type: "category",
            data: anos,
            axisTick: { show: false },
            axisLine: { lineStyle: { color: "#e5e7eb" } },
            axisLabel: {
                color: "#6b7280",
                fontWeight: 600,
                fontSize: 12
            }
        },
        yAxis: eixoValorMoeda({ beginAtZero: true }),
        series: [
            serieBarra("Receitas", receitas, "#16a34a"),
            serieBarra("Despesas", despesas, "#dc2626"),
            serieBarra("Saldo", saldos, "#2563eb")
        ]
    }, true);

    graficoComparacaoAnos.resize();
}
function carregarComparacaoAnos() {
    if (!$("#graficoComparacaoAnos").length) {
        return;
    }

    const params = parametrosDashboard();

    $.ajax({
        url: BASE_DASHBOARD + "/comparacao-anos.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function (res) {
            if (res.status !== "sucesso" || !res.dados) {
                return;
            }

            dadosComparacaoAnos = res.dados;

            renderizarGraficoComparacaoAnos();
        },
        error: function (xhr) {
            console.log("ERRO COMPARACAO ANOS:", xhr.responseText);
        }
    });
}

function fecharMenuExcelLista() {
    $("#menuExcelLista").attr("hidden", true);
    $("#btnExcelGear").removeClass("aberto").attr("aria-expanded", "false");
}

function abrirMenuExcelLista() {
    $("#menuExcelLista").removeAttr("hidden");
    $("#btnExcelGear").addClass("aberto").attr("aria-expanded", "true");
}

function tipoListaAtual() {
    return tipoPaginaAtual() || "Receita";
}

function tituloListaAtual() {
    return tipoListaAtual() === "Receita" ? "Receitas" : "Despesas";
}

function resetarImportacaoExcel() {
    $("#inputImportarExcel").val("");
    $("#nomeArquivoExcel").text("Nenhum arquivo selecionado");
    $("#btnConfirmarImportarExcel").prop("disabled", true);
    limparAvisoResumoImportacao();
}

function arquivoExcelValido(arquivo) {
    if (!arquivo) {
        return false;
    }

    const nome = String(arquivo.name || "").toLowerCase();

    return (
        nome.endsWith(".xlsx")
        || nome.endsWith(".xls")
        || nome.endsWith(".csv")
    );
}

function limparAvisoResumoImportacao() {
    $("#avisoResumoImportacao")
        .attr("hidden", true)
        .removeClass("aviso-info aviso-alerta")
        .html("");
}

function textoQuantidadeImportacao(qtd, singular, plural) {
    return qtd === 1 ? "1 " + singular : qtd + " " + plural;
}

function montarAvisoResumoImportacao(receitas, despesas) {
    const $aviso = $("#avisoResumoImportacao");

    if (!$aviso.length || (receitas === 0 && despesas === 0)) {
        limparAvisoResumoImportacao();
        return;
    }

    let texto = "";
    let classe = "aviso-info";

    if (tipoListaAtual() === "Receita") {
        classe = "aviso-alerta";

        if (receitas === 0 && despesas > 0) {
            texto = "Este documento só tem despesas. Nenhuma receita com sinal <strong>+</strong> foi encontrada.";
        } else if (receitas > 0 && despesas > 0) {
            texto = "Este documento tem só "
                + textoQuantidadeImportacao(receitas, "receita", "receitas")
                + " e o resto são despesas ("
                + despesas
                + ").";
        } else {
            texto = "Detectamos "
                + textoQuantidadeImportacao(receitas, "receita", "receitas")
                + " no arquivo.";
            classe = "aviso-info";
        }
    } else if (receitas > 0 && despesas > 0) {
        texto = "Detectamos "
            + textoQuantidadeImportacao(receitas, "receita", "receitas")
            + " e "
            + textoQuantidadeImportacao(despesas, "despesa", "despesas")
            + " no arquivo.";
    } else if (despesas > 0) {
        texto = "Detectamos "
            + textoQuantidadeImportacao(despesas, "despesa", "despesas")
            + " no arquivo.";
    } else {
        texto = "Detectamos "
            + textoQuantidadeImportacao(receitas, "receita", "receitas")
            + " no arquivo.";
    }

    $aviso
        .removeClass("aviso-info aviso-alerta")
        .addClass(classe)
        .html('<i class="bi bi-info-circle"></i><span>' + texto + "</span>")
        .removeAttr("hidden");
}

function detectarDelimitadorCsvImportacao(texto) {
    const primeiraLinha = String(texto || "").split(/\r?\n/)[0] || "";
    const cabecalho = primeiraLinha.toLowerCase();

    if (cabecalho.indexOf("date,title,amount") !== -1) {
        return ",";
    }

    const pontoEVirgula = (primeiraLinha.match(/;/g) || []).length;
    const virgula = (primeiraLinha.match(/,/g) || []).length;

    return pontoEVirgula > virgula ? ";" : ",";
}

function parseLinhaCsvImportacao(linha, delimitador) {
    const campos = [];
    let atual = "";
    let dentroAspas = false;

    for (let i = 0; i < linha.length; i++) {
        const char = linha[i];

        if (char === '"') {
            if (dentroAspas && linha[i + 1] === '"') {
                atual += '"';
                i++;
            } else {
                dentroAspas = !dentroAspas;
            }
            continue;
        }

        if (char === delimitador && !dentroAspas) {
            campos.push(atual);
            atual = "";
            continue;
        }

        atual += char;
    }

    campos.push(atual);
    return campos;
}

function indiceColunaValorImportacao(cabecalho) {
    const normalizados = cabecalho.map(function (coluna) {
        return String(coluna || "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, "");
    });

    const aliases = ["amount", "valor", "montante"];

    for (let i = 0; i < normalizados.length; i++) {
        if (aliases.indexOf(normalizados[i]) !== -1) {
            return i;
        }
    }

    return cabecalho.length >= 5 ? 3 : cabecalho.length - 1;
}

function classificarValorImportacaoFront(valorBruto) {
    const valor = String(valorBruto || "").trim();

    if (!valor || !/\d/.test(valor)) {
        return null;
    }

    if (/^\s*\+/.test(valor) || /\+\s*R?\$/i.test(valor)) {
        return "Receita";
    }

    return "Despesa";
}

function analisarArquivoImportacao(arquivo, callback) {
    if (!arquivo || !String(arquivo.name || "").toLowerCase().endsWith(".csv")) {
        limparAvisoResumoImportacao();
        if (typeof callback === "function") {
            callback();
        }
        return;
    }

    const leitor = new FileReader();

    leitor.onload = function (evento) {
        try {
            let texto = String(evento.target.result || "");

            if (texto.charCodeAt(0) === 0xfeff) {
                texto = texto.slice(1);
            }

            const delimitador = detectarDelimitadorCsvImportacao(texto);
            const linhas = texto.split(/\r?\n/).filter(function (linha) {
                return linha.trim() !== "";
            });

            if (linhas.length < 2) {
                limparAvisoResumoImportacao();
                if (typeof callback === "function") {
                    callback();
                }
                return;
            }

            const cabecalho = parseLinhaCsvImportacao(linhas[0], delimitador);
            const indiceValor = indiceColunaValorImportacao(cabecalho);
            let receitas = 0;
            let despesas = 0;

            for (let i = 1; i < linhas.length; i++) {
                const campos = parseLinhaCsvImportacao(linhas[i], delimitador);
                const tipo = classificarValorImportacaoFront(campos[indiceValor]);

                if (tipo === "Receita") {
                    receitas++;
                } else if (tipo === "Despesa") {
                    despesas++;
                }
            }

            montarAvisoResumoImportacao(receitas, despesas);
        } catch (erro) {
            limparAvisoResumoImportacao();
        }

        if (typeof callback === "function") {
            callback();
        }
    };

    leitor.onerror = function () {
        limparAvisoResumoImportacao();
        if (typeof callback === "function") {
            callback();
        }
    };

    leitor.readAsText(arquivo);
}

function atualizarArquivoExcelSelecionado(arquivo) {
    if (!arquivoExcelValido(arquivo)) {
        resetarImportacaoExcel();

        Swal.fire({
            icon: "error",
            title: "Arquivo inválido",
            text: "Use .xlsx, .xls ou .csv."
        });
        return;
    }

    $("#nomeArquivoExcel").text(arquivo.name);
    $("#btnConfirmarImportarExcel").prop("disabled", false);
    analisarArquivoImportacao(arquivo);
}

function atualizarTelasAposSalvar() {
    if ($("#tabelaMovimentacoes").length) {
        carregarMovimentacoes();
    }

    if ($("#totalReceitas").length) {
        carregarDashboard();
        carregarGraficoEvolucao();
        carregarGraficoFluxoCaixa();
        carregarGraficoSaldo();
        carregarGraficosCategoria();
        carregarComparacaoAnos();
    }
}

$(document).on("click", "#btnAbrirPeriodoLista", function () {
    ajustarPeriodoLista();

    const modal = new bootstrap.Modal(
        document.getElementById("modalPeriodoLista")
    );

    modal.show();
});

$(document).on("change", "#listaDataInicio, #listaDataFim", function () {
    ajustarPeriodoLista();
});

$(document).on("click", "#btnAplicarPeriodoLista", function () {
    aplicarPeriodoLista();
});

$(document).on("click", "#btnRedefinirPeriodoLista", function () {
    inicializarPeriodoLista();
    aplicarPeriodoLista();
});

$(document).on("click", "#btnExcelGear", function (evento) {
    evento.stopPropagation();

    if ($("#menuExcelLista").is("[hidden]")) {
        abrirMenuExcelLista();
        return;
    }

    fecharMenuExcelLista();
});

$(document).on("click", "#btnExportarExcel", function () {
    fecharMenuExcelLista();

    const tipo = tipoListaAtual();
    const periodo = parametrosPeriodoLista();

    const params = new URLSearchParams({
        tipo: tipo,
        inicio: periodo.inicio,
        fim: periodo.fim
    });

    // força o download
    window.location.href =
        BASE_MOVIMENTACOES + "/exportar.php?" + params.toString();
});

$(document).on("click", "#btnImportarExcel", function () {
    fecharMenuExcelLista();
    resetarImportacaoExcel();

    const modal = new bootstrap.Modal(
        document.getElementById("modalImportarExcel")
    );

    modal.show();
});

$(document).on("change", "#inputImportarExcel", function () {
    const arquivo = this.files && this.files[0];
    atualizarArquivoExcelSelecionado(arquivo);
});

$(document).on("dragover", ".excel-upload-area", function (evento) {
    evento.preventDefault();
    $(this).addClass("dragover");
});

$(document).on("dragleave dragend drop", ".excel-upload-area", function () {
    $(this).removeClass("dragover");
});

$(document).on("drop", ".excel-upload-area", function (evento) {
    evento.preventDefault();

    const arquivo = evento.originalEvent.dataTransfer.files[0];
    const input = document.getElementById("inputImportarExcel");

    if (input && arquivo) {
        input.files = evento.originalEvent.dataTransfer.files;
    }

    atualizarArquivoExcelSelecionado(arquivo);
});

$(document).on("click", "#btnConfirmarImportarExcel", function () {
    const arquivo = $("#inputImportarExcel")[0].files[0];

    if (!arquivoExcelValido(arquivo)) {
        Swal.fire({
            icon: "error",
            title: "Selecione um arquivo válido"
        });
        return;
    }

    const formData = new FormData();
    formData.append("tipo", tipoListaAtual());
    formData.append("arquivo", arquivo);

    const $botao = $("#btnConfirmarImportarExcel");
    const textoOriginal = $botao.text();

    $botao.prop("disabled", true).text("Importando...");

    $.ajax({
        url: BASE_MOVIMENTACOES + "/importar.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (res) {
            const modal = bootstrap.Modal.getInstance(
                document.getElementById("modalImportarExcel")
            );

            if (modal) {
                modal.hide();
            }

            resetarImportacaoExcel();

            if (res.status === "sucesso") {
                let html = res.mensagem;

                if (res.aviso) {
                    html += "<br><br><strong>" + res.aviso + "</strong>";
                }

                if (res.erros && res.erros.length) {
                    html += "<br><br><small>" + res.erros.join("<br>") + "</small>";
                }

                Swal.fire({
                    icon: res.aviso ? "info" : "success",
                    title: res.aviso ? "Importação concluída" : "Importação concluída",
                    html: html,
                    confirmButtonText: "Ok"
                });

                carregarMovimentacoes();
                return;
            }

            let htmlErro = res.mensagem || "Não foi possível importar o arquivo.";

            if (res.erros && res.erros.length) {
                htmlErro += "<br><br><small>" + res.erros.join("<br>") + "</small>";
            }

            Swal.fire({
                icon: "error",
                title: "Falha na importação",
                html: htmlErro
            });
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Erro ao importar",
                text: "Não foi possível enviar o arquivo. Tente novamente."
            });
        },
        complete: function () {
            $botao.prop("disabled", false).text(textoOriginal);
        }
    });
});

$(document).on("click", function (evento) {
    if (!$(evento.target).closest(".excel-gear-wrap").length) {
        fecharMenuExcelLista();
    }
});

$(document).on("click", "#btnNovaMovimentacao", function () {
    const tipo =
        $(this).data("tipo") || tipoPaginaAtual() || "Receita";

    const titulo =
        tipo === "Receita" ? "Nova receita" : "Nova despesa";

    abrirModalMovimentacao(tipo, titulo);
});

$(document).on("input", "#valor", function () {
    this.value = mascaraMoeda(this.value);
});

$(document).on("change", "#data", function () {
    $("#data").attr("max", hojeISO());
    limitarDataHoje("#data");
});

$(document).on("click", "#btnSalvarMovimentacao", function () {
    let categoria = $("#categoria").val();

    if (!categoria) {
        categoria = "Outros";
        $("#categoria").val("Outros");
    }

    const valor = valorMascaraParaNumero($("#valor").val());
    const hoje = hojeISO();

    $("#data").attr("max", hoje);
    limitarDataHoje("#data");

    const data = $("#data").val();

    if (!valor) {
        Swal.fire({
            icon: "error",
            title: "Informe um valor válido."
        });
        return;
    }

    if (!data) {
        Swal.fire({
            icon: "error",
            title: "Informe a data."
        });
        return;
    }

    if (data > hoje) {
        Swal.fire({
            icon: "error",
            title: "A data não pode ser futura.",
            text: "Use uma data de hoje ou do passado."
        });
        $("#data").val(hoje);
        return;
    }

    $.ajax({
        url: BASE_MOVIMENTACOES + "/salvar.php",
        type: "POST",
        data: {
            id: $("#idMovimentacao").val(),
            tipo: $("#tipo").val(),
            categoria: categoria,
            descricao: $("#descricao").val().trim(),
            valor: valor,
            data: data
        },
        dataType: "json",
        success: function (res) {
            if (res.status === "sucesso") {
                Swal.fire({
                    icon: "success",
                    title: res.mensagem,
                    timer: 1500,
                    showConfirmButton: false
                });

                const modal = bootstrap.Modal.getInstance(
                    document.getElementById("modalMovimentacao")
                );

                if (modal) {
                    modal.hide();
                }

                $("#formMovimentacao")[0].reset();
                atualizarTelasAposSalvar();
            } else {
                Swal.fire({
                    icon: "error",
                    title: res.mensagem
                });
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            Swal.fire({
                icon: "error",
                title: "Erro ao salvar movimentação."
            });
        }
    });
});

$(document).on("click", ".btnEditarMovimentacao", function () {
    const id = $(this).data("id");

    $.ajax({
        url: BASE_MOVIMENTACOES + "/buscar.php",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (res) {
            if (res.status === "sucesso") {
                const dados = res.dados;

                $("#idMovimentacao").val(dados.id);
                $("#tipo").val(dados.tipo);
                $("#descricao").val(dados.descricao || "");
                $("#valor").val(formatarMoeda(dados.valor));

                const hoje = hojeISO();
                let data = dados.data || hoje;

                $("#data").attr("max", hoje);

                if (data > hoje) {
                    data = hoje;
                }

                $("#data").val(data);

                $("#tituloModalMovimentacao").text(
                    dados.tipo === "Receita"
                        ? "Editar receita"
                        : "Editar despesa"
                );

                atualizarDatasMovimentacao(
                    dados.criado_em || "",
                    dados.atualizado_em || ""
                );

                carregarCategorias(function () {
                    preencherSelectCategorias(
                        dados.tipo,
                        dados.categoria || "Outros"
                    );

                    const modalEl = document.getElementById("modalMovimentacao");
                    const modal = new bootstrap.Modal(modalEl);

                    modal.show();
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: res.mensagem
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Erro ao buscar movimentação."
            });
        }
    });
});

$(document).on("click", ".btnExcluirMovimentacao", function () {
    const id = $(this).data("id");

    Swal.fire({
        title: "Excluir registro?",
        text: "Essa ação não poderá ser desfeita.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sim, excluir",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: BASE_MOVIMENTACOES + "/excluir.php",
            type: "POST",
            data: { id: id },
            dataType: "json",
            success: function (res) {
                if (res.status === "sucesso") {
                    Swal.fire({
                        icon: "success",
                        title: res.mensagem,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    atualizarTelasAposSalvar();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: res.mensagem
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Erro ao excluir movimentação."
                });
            }
        });
    });
});

function recarregarDashboard() {
    carregarDashboard();
    carregarGraficoEvolucao();
    carregarGraficoFluxoCaixa();
    carregarGraficoSaldo();
    carregarGraficosCategoria();
    carregarComparacaoAnos();
}

$(document).on("change", "#agrupamentoReceitasCategoria, #agrupamentoDespesasCategoria", function () {
    renderizarGraficosCategoria();
});

$(document).on("change", "#dataInicio, #dataFim", function () {
    ajustarPeriodo();
    atualizarFiltrosPeriodo();
    recarregarDashboard();
});

$(document).on("click", "#btnRedefinirFiltros", function () {
    inicializarPeriodo();
    atualizarFiltrosPeriodo();
    recarregarDashboard();
});

function abrirModalConfig() {
    const overlay = $("#modalConfiguracao");

    overlay.removeAttr("hidden");
    $("body").addClass("config-modal-open");
    $("#btnEngrenagemConfig").addClass("aberto").removeClass("girando");

    requestAnimationFrame(function () {
        overlay.addClass("aberto");
    });
}

function fecharModalConfig() {
    const overlay = $("#modalConfiguracao");

    overlay.removeClass("aberto");
    $("body").removeClass("config-modal-open");
    $("#btnEngrenagemConfig").removeClass("girando aberto");

    setTimeout(function () {
        if (!overlay.hasClass("aberto")) {
            overlay.attr("hidden", true);
        }
    }, 250);
}

function htmlCategoriasConfig() {
    const receitas = garantirOutrosNoFim(
        (categoriasPorTipo && categoriasPorTipo.Receita) || []
    );
    const despesas = garantirOutrosNoFim(
        (categoriasPorTipo && categoriasPorTipo.Despesa) || []
    );

    return `
        <p class="config-detail-title">Categorias</p>
        <p class="config-detail-vazio">Receitas: ${receitas.join(", ")}</p>
        <p class="config-detail-vazio mb-0">Despesas: ${despesas.join(", ")}</p>
    `;
}

function mostrarOpcaoConfig(opcao) {
    $(".config-option").removeClass("ativa");
    $('.config-option[data-opcao="' + opcao + '"]').addClass("ativa");

    const detalhe = $("#configDetalhe");
    const textos = {
        periodo: `
            <p class="config-detail-title">Período padrão</p>
            <p class="config-detail-vazio mb-0">
                O dashboard abre no ano atual, de 1º de janeiro até hoje.
            </p>
        `,
        aparencia: `
            <p class="config-detail-title">Aparência</p>
            <p class="config-detail-vazio mb-0">
                Tema claro, com verde para receitas, vermelho para despesas e azul para saldo.
            </p>
        `,
        dados: `
            <p class="config-detail-title">Dados</p>
            <p class="config-detail-vazio mb-0">
                As movimentações ficam na tabela movimentacoes do banco gestao_financeira.
            </p>
        `,
        sobre: `
            <p class="config-detail-title">Sobre</p>
            <p class="config-detail-vazio mb-0">
                Sistema Financeiro — dashboard, receitas, despesas e fluxo de caixa.
            </p>
        `
    };

    if (opcao === "categorias") {
        carregarCategorias(function () {
            detalhe.html(htmlCategoriasConfig());
        });
        return;
    }

    detalhe.html(textos[opcao] || '<p class="config-detail-vazio">Selecione uma opção acima.</p>');
}

$(document).on("click", "#btnEngrenagemConfig", function () {
    const btn = $(this);

    if ($("#modalConfiguracao").hasClass("aberto")) {
        fecharModalConfig();
        return;
    }

    btn.addClass("girando");

    setTimeout(function () {
        abrirModalConfig();
    }, 520);
});

$(document).on("click", "#btnFecharConfig", function () {
    fecharModalConfig();
});

$(document).on("click", "#modalConfiguracao", function (evento) {
    if (evento.target.id === "modalConfiguracao") {
        fecharModalConfig();
    }
});

$(document).on("click", ".config-option", function () {
    if ($(this).is("a")) {
        return;
    }
    mostrarOpcaoConfig($(this).data("opcao"));
});

$(document).on("keydown", function (evento) {
    if (evento.key === "Escape" && $("#modalConfiguracao").hasClass("aberto")) {
        fecharModalConfig();
    }
});

$(document).ready(function () {
    carregarCategorias();
    inicializarTema();

    if ($("#tabelaMovimentacoes").length) {
        inicializarPeriodoLista();
        atualizarSubtituloPeriodoLista();
        carregarMovimentacoes();
        resetarImportacaoExcel();
    }

    if ($("#totalReceitas").length && $("#dataInicio").length) {
        inicializarPeriodo();
        atualizarFiltrosPeriodo();
        recarregarDashboard();
    } else if ($("#graficoComparacaoAnos").length) {
        carregarComparacaoAnos();
    }

    $(window).on("resize", function () {
        [
            graficoEvolucao,
            graficoSaldo,
            graficoFluxoCaixa,
            graficoReceitasCategoria,
            graficoDespesasCategoria,
            graficoComparacaoAnos
        ].forEach(function (grafico) {
            if (grafico && typeof grafico.resize === "function") {
                grafico.resize();
            }
        });
    });
});
// ========= SUPORTE / FALE CONOSCO / TICKETS =========
const SUPORTE_WHATSAPP = "5579988871385"; // DDI 55 + DDD 79 + número
let motivoSuporteSelecionado = "";

function escaparHtmlTicket(texto) {
    return String(texto ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function classeBadgeTicket(status) {
    if (status === "Em atendimento") {
        return "atendimento";
    }
    if (status === "Resolvido") {
        return "resolvido";
    }
    return "aberto";
}

function formatarDataTicket(valor) {
    if (!valor) {
        return "—";
    }

    const data = new Date(String(valor).replace(" ", "T"));

    if (Number.isNaN(data.getTime())) {
        return String(valor);
    }

    return data.toLocaleString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    });
}

function abrirModalSuporte() {
    motivoSuporteSelecionado = "";
    $("#mensagemSuporte").val("");
    $(".suporte-motivo").removeClass("ativo");
    $("#btnEnviarSuporte").prop("disabled", true);

    const modalEl = document.getElementById("modalSuporte");

    if (!modalEl) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function montarMensagemSuporte(protocolo, usuario) {
    const detalhe = ($("#mensagemSuporte").val() || "").trim();
    let texto = "Olá! Preciso de suporte no Sistema Financeiro.";

    if (protocolo) {
        texto += "\nProtocolo: " + protocolo;
    }

    if (usuario && usuario.nome) {
        texto += "\nUsuário: " + usuario.nome;
    }

    if (usuario && usuario.email) {
        texto += "\nE-mail: " + usuario.email;
    }

    texto += "\nMotivo: " + (motivoSuporteSelecionado || "Não informado");

    if (detalhe) {
        texto += "\nDetalhe: " + detalhe;
    }

    texto += "\nPágina: " + window.location.href;

    return texto;
}

function abrirWhatsAppSuporte(protocolo, usuario) {
    if (!SUPORTE_WHATSAPP || String(SUPORTE_WHATSAPP).indexOf("999999") !== -1) {
        Swal.fire({
            icon: "info",
            title: "Ticket criado",
            text: "Protocolo " + protocolo + ". Configure SUPORTE_WHATSAPP em assets/js/script.js para abrir o WhatsApp."
        });
        return;
    }

    const url =
        "https://wa.me/" +
        SUPORTE_WHATSAPP +
        "?text=" +
        encodeURIComponent(montarMensagemSuporte(protocolo, usuario));

    window.open(url, "_blank");
}

function carregarTickets() {
    if (!$("#corpoTabelaTickets").length) {
        return;
    }

    const status = $("#filtroStatusTicket").val() || "";
    const ehSuporteDesk = $("#corpoTabelaTickets").hasClass("suporte-lista")
        || $("body").hasClass("suporte-desk");

    $.ajax({
        url: "/Financeiro/tickets/listar.php",
        method: "GET",
        dataType: "json",
        data: status ? { status: status } : {},
        success: function (resposta) {
            if (!resposta || resposta.status !== "sucesso") {
                $("#corpoTabelaTickets").html(
                    ehSuporteDesk
                        ? '<div class="suporte-lista-vazio">Não foi possível carregar os tickets.</div>'
                        : '<tr><td colspan="7" class="text-center text-muted py-4">Não foi possível carregar os tickets.</td></tr>'
                );
                return;
            }

            const dados = resposta.dados || {};
            const resumo = dados.resumo || {};
            const tickets = dados.tickets || [];

            $("#ticketResumoAbertos").text(resumo.abertos || 0);
            $("#ticketResumoAtendimento").text(resumo.atendimento || 0);
            $("#ticketResumoResolvidos").text(resumo.resolvidos || 0);

            if (!tickets.length) {
                $("#corpoTabelaTickets").html(
                    ehSuporteDesk
                        ? '<div class="suporte-lista-vazio">Nenhum ticket ainda.</div>'
                        : '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum ticket ainda.</td></tr>'
                );
                return;
            }

            if (ehSuporteDesk) {
                const cards = tickets.map(function (ticket) {
                    const statusAtual = ticket.status || "Aberto";
                    const detalhe = ticket.detalhe
                        ? escaparHtmlTicket(ticket.detalhe)
                        : "Sem detalhe adicional.";
                    const nome = ticket.usuario_nome
                        ? escaparHtmlTicket(ticket.usuario_nome)
                        : "Usuário não identificado";
                    const email = ticket.usuario_email
                        ? escaparHtmlTicket(ticket.usuario_email)
                        : "";

                    return (
                        '<article class="suporte-ticket" data-ticket-card="' +
                        ticket.id +
                        '">' +
                        '<div class="suporte-ticket-corpo">' +
                        '<div class="suporte-ticket-meta">' +
                        '<span class="suporte-protocolo">' +
                        escaparHtmlTicket(ticket.protocolo) +
                        "</span>" +
                        '<span class="suporte-badge ' +
                        classeBadgeTicket(statusAtual) +
                        '">' +
                        escaparHtmlTicket(statusAtual) +
                        "</span>" +
                        '<span class="suporte-ticket-data">' +
                        formatarDataTicket(ticket.criado_em) +
                        "</span>" +
                        "</div>" +
                        '<div class="suporte-ticket-user">' +
                        "<strong>" +
                        nome +
                        "</strong>" +
                        (email ? "<span>" + email + "</span>" : "") +
                        "</div>" +
                        '<p class="suporte-ticket-motivo">' +
                        escaparHtmlTicket(ticket.motivo) +
                        "</p>" +
                        '<p class="suporte-ticket-detalhe">' +
                        detalhe +
                        "</p>" +
                        "</div>" +
                        '<div class="suporte-ticket-acoes">' +
                        '<button type="button" class="btn-status-ticket' +
                        (statusAtual === "Aberto" ? " is-ativo" : "") +
                        '" data-id="' +
                        ticket.id +
                        '" data-status="Aberto">Aberto</button>' +
                        '<button type="button" class="btn-status-ticket' +
                        (statusAtual === "Em atendimento" ? " is-ativo" : "") +
                        '" data-id="' +
                        ticket.id +
                        '" data-status="Em atendimento">Atendendo</button>' +
                        '<button type="button" class="btn-status-ticket' +
                        (statusAtual === "Resolvido" ? " is-ativo" : "") +
                        '" data-id="' +
                        ticket.id +
                        '" data-status="Resolvido">Resolvido</button>' +
                        "</div>" +
                        '<div class="suporte-zap-painel">' +
                        '<textarea class="suporte-resposta-texto" maxlength="1000" placeholder="Mensagem para enviar no WhatsApp do cliente..."></textarea>' +
                        '<button type="button" class="btn-whatsapp-resposta" data-id="' +
                        ticket.id +
                        '" data-protocolo="' +
                        escaparHtmlTicket(ticket.protocolo) +
                        '" data-telefone="' +
                        escaparHtmlTicket(ticket.usuario_telefone || "") +
                        '" data-nome="' +
                        escaparHtmlTicket(ticket.usuario_nome || "") +
                        '" data-motivo="' +
                        escaparHtmlTicket(ticket.motivo || "") +
                        '"><i class="bi bi-whatsapp"></i> WhatsApp</button>' +
                        "</div>" +
                        "</article>"
                    );
                });

                $("#corpoTabelaTickets").html(cards.join(""));
                return;
            }

            const linhas = tickets.map(function (ticket) {
                const statusAtual = ticket.status || "Aberto";
                const detalhe = ticket.detalhe
                    ? escaparHtmlTicket(ticket.detalhe)
                    : '<span class="text-muted">—</span>';

                const nome = ticket.usuario_nome
                    ? escaparHtmlTicket(ticket.usuario_nome)
                    : "";
                const email = ticket.usuario_email
                    ? escaparHtmlTicket(ticket.usuario_email)
                    : "";
                let celulaUsuario = '<span class="text-muted">—</span>';

                if (nome || email) {
                    celulaUsuario =
                        '<div class="ticket-usuario">' +
                        (nome ? "<strong>" + nome + "</strong>" : "") +
                        (email ? '<small class="d-block text-muted">' + email + "</small>" : "") +
                        "</div>";
                }

                return (
                    "<tr>" +
                    '<td><span class="ticket-protocolo">' +
                    escaparHtmlTicket(ticket.protocolo) +
                    "</span></td>" +
                    "<td>" +
                    celulaUsuario +
                    "</td>" +
                    "<td>" +
                    escaparHtmlTicket(ticket.motivo) +
                    "</td>" +
                    '<td><div class="ticket-detalhe" title="' +
                    escaparHtmlTicket(ticket.detalhe || "") +
                    '">' +
                    detalhe +
                    "</div></td>" +
                    '<td><span class="badge-ticket ' +
                    classeBadgeTicket(statusAtual) +
                    '">' +
                    escaparHtmlTicket(statusAtual) +
                    "</span></td>" +
                    "<td>" +
                    formatarDataTicket(ticket.criado_em) +
                    "</td>" +
                    '<td class="text-end ticket-acoes">' +
                    '<div class="btn-group btn-group-sm">' +
                    '<button type="button" class="btn btn-outline-secondary btn-status-ticket" data-id="' +
                    ticket.id +
                    '" data-status="Aberto" title="Marcar aberto">Aberto</button>' +
                    '<button type="button" class="btn btn-outline-primary btn-status-ticket" data-id="' +
                    ticket.id +
                    '" data-status="Em atendimento" title="Em atendimento">Atende</button>' +
                    '<button type="button" class="btn btn-outline-success btn-status-ticket" data-id="' +
                    ticket.id +
                    '" data-status="Resolvido" title="Resolvido">OK</button>' +
                    "</div>" +
                    "</td>" +
                    "</tr>"
                );
            });

            $("#corpoTabelaTickets").html(linhas.join(""));
        },
        error: function () {
            const ehDesk = $("body").hasClass("suporte-desk");
            $("#corpoTabelaTickets").html(
                ehDesk
                    ? '<div class="suporte-lista-vazio">Erro ao carregar tickets.</div>'
                    : '<tr><td colspan="7" class="text-center text-muted py-4">Erro ao carregar tickets.</td></tr>'
            );
        }
    });
}

function atualizarStatusTicket(id, status) {
    $.ajax({
        url: "/Financeiro/tickets/atualizar.php",
        method: "POST",
        dataType: "json",
        data: { id: id, status: status },
        success: function (resposta) {
            if (!resposta || resposta.status !== "sucesso") {
                Swal.fire({
                    icon: "error",
                    title: "Erro",
                    text: (resposta && resposta.mensagem) || "Não foi possível atualizar."
                });
                return;
            }

            carregarTickets();
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Erro",
                text: "Não foi possível atualizar o ticket."
            });
        }
    });
}

$(document).on("click", "#btnAssistenteFlutuante", function () {
    abrirModalSuporte();
});

$(document).on("click", ".suporte-motivo", function () {
    $(".suporte-motivo").removeClass("ativo");
    $(this).addClass("ativo");
    motivoSuporteSelecionado = String($(this).data("motivo") || "");
    $("#btnEnviarSuporte").prop("disabled", motivoSuporteSelecionado === "");
});

$(document).on("click", "#btnEnviarSuporte", function () {
    if (!motivoSuporteSelecionado) {
        return;
    }

    const $btn = $(this);
    $btn.prop("disabled", true);

    $.ajax({
        url: "/Financeiro/tickets/salvar.php",
        method: "POST",
        dataType: "json",
        data: {
            motivo: motivoSuporteSelecionado,
            detalhe: ($("#mensagemSuporte").val() || "").trim(),
            pagina: window.location.href
        },
        success: function (resposta) {
            if (!resposta || resposta.status !== "sucesso") {
                Swal.fire({
                    icon: "error",
                    title: "Erro",
                    text: (resposta && resposta.mensagem) || "Não foi possível criar o ticket."
                });
                $btn.prop("disabled", false);
                return;
            }

            const protocolo = (resposta.dados && resposta.dados.protocolo) || "";
            const usuarioTicket = {
                nome: (resposta.dados && resposta.dados.usuario_nome) || "",
                email: (resposta.dados && resposta.dados.usuario_email) || ""
            };
            const modalEl = document.getElementById("modalSuporte");

            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            }

            abrirWhatsAppSuporte(protocolo, usuarioTicket);
            carregarTickets();
            carregarMeusTickets();

            Swal.fire({
                icon: "success",
                title: "Ticket " + protocolo,
                text: "Protocolo gerado. Continuamos no WhatsApp.",
                timer: 2200,
                showConfirmButton: false
            });
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Erro",
                text: "Não foi possível criar o ticket."
            });
            $btn.prop("disabled", false);
        }
    });
});

$(document).on("change", "#filtroStatusTicket", function () {
    carregarTickets();
});

$(document).on("click", ".btn-status-ticket", function () {
    const id = $(this).data("id");
    const status = $(this).data("status");
    atualizarStatusTicket(id, status);
});

$(function () {
    carregarTickets();
});

function montarMensagemWhatsAppResposta(protocolo, textoResposta, usuario) {
    let t = "Olá! Resposta do suporte — Sistema Financeiro.";
    t += "\nProtocolo: " + (protocolo || "");
    if (usuario && usuario.nome) {
        t += "\nCliente: " + usuario.nome;
    }
    if (usuario && usuario.motivo) {
        t += "\nMotivo: " + usuario.motivo;
    }
    t += "\n\nObservação:\n" + (textoResposta || "");
    return t;
}

$(document).on("click", ".btn-whatsapp-resposta", function () {
    const $btn = $(this);
    const protocolo = $btn.data("protocolo") || "";
    const telefone = String($btn.data("telefone") || "").replace(/\D/g, "");
    const nome = $btn.data("nome") || "";
    const motivo = $btn.data("motivo") || "";
    const $card = $btn.closest(".suporte-ticket");
    let texto = ($card.find(".suporte-resposta-texto").val() || "").trim();

    if (!telefone) {
        Swal.fire({
            icon: "warning",
            title: "Sem WhatsApp",
            text: "Este ticket não tem telefone do cliente."
        });
        return;
    }

    if (!texto) {
        texto = "Estamos analisando sua solicitação. Em breve retornamos.";
    }

    const mensagem = montarMensagemWhatsAppResposta(protocolo, texto, {
        nome: nome,
        motivo: motivo
    });

    const url =
        "https://wa.me/" +
        telefone +
        "?text=" +
        encodeURIComponent(mensagem);

    window.open(url, "_blank");
});

// ========= MEUS TICKETS (cliente) =========
function carregarMeusTickets() {
    if (!$("#listaMeusTickets").length) {
        return;
    }

    $.ajax({
        url: "/Financeiro/tickets/meus.php",
        method: "GET",
        dataType: "json",
        success: function (res) {
            if (!res || res.status !== "sucesso") {
                $("#listaMeusTickets").html(
                    '<div class="meus-tickets-vazio">Não foi possível carregar.</div>'
                );
                return;
            }

            const tickets = (res.dados && res.dados.tickets) || [];
            if (!tickets.length) {
                $("#listaMeusTickets").html(
                    '<div class="meus-tickets-vazio">Você ainda não abriu nenhum ticket. Use “Fale conosco” ou “Novo ticket”.</div>'
                );
                return;
            }

            const html = tickets.map(function (t) {
                const detalhe = t.detalhe
                    ? '<p class="meus-ticket-detalhe">' + escaparHtmlTicket(t.detalhe) + "</p>"
                    : "";

                return (
                    '<article class="meus-ticket-card">' +
                    '<div class="meus-ticket-card-topo">' +
                    '<span class="meus-ticket-proto">' +
                    escaparHtmlTicket(t.protocolo) +
                    "</span>" +
                    '<span class="badge-ticket ' +
                    classeBadgeTicket(t.status) +
                    '">' +
                    escaparHtmlTicket(t.status) +
                    "</span>" +
                    "</div>" +
                    '<p class="meus-ticket-motivo">' +
                    escaparHtmlTicket(t.motivo) +
                    "</p>" +
                    detalhe +
                    '<span class="meus-ticket-data">' +
                    formatarDataTicket(t.criado_em) +
                    "</span>" +
                    "</article>"
                );
            }).join("");

            $("#listaMeusTickets").html(html);
        },
        error: function () {
            $("#listaMeusTickets").html(
                '<div class="meus-tickets-vazio">Erro ao carregar seus tickets.</div>'
            );
        }
    });
}


$(function () {
    carregarMeusTickets();
});

// ========================== SIDEBAR ==========================
const CHAVE_SIDEBAR = "financeiro-sidebar-recolhido";

function ehMobileSidebar() {
    return window.matchMedia("(max-width: 768px)").matches;
}

function aplicarSidebarRecolhido(recolhido, salvar) {
    $("body").toggleClass("sidebar-recolhido", !!recolhido);
    $("#sidebarOverlay").prop("hidden", !ehMobileSidebar() || !!recolhido);

    if (salvar) {
        try {
            localStorage.setItem(CHAVE_SIDEBAR, recolhido ? "1" : "0");
        } catch (e) {
            // ignore
        }
    }

    setTimeout(function () {
        $(window).trigger("resize");
    }, 280);
}

function inicializarSidebar() {
    let recolhido;

    try {
        const salvo = localStorage.getItem(CHAVE_SIDEBAR);
        if (salvo === "1" || salvo === "0") {
            recolhido = salvo === "1";
        } else {
            recolhido = ehMobileSidebar();
        }
    } catch (e) {
        recolhido = ehMobileSidebar();
    }

    aplicarSidebarRecolhido(recolhido, false);
}

$(document).on("click", "#btnToggleSidebar", function () {
    const recolhido = !$("body").hasClass("sidebar-recolhido");
    aplicarSidebarRecolhido(recolhido, true);
});

$(document).on("click", "#sidebarOverlay", function () {
    aplicarSidebarRecolhido(true, true);
});

$(document).on("click", ".sidebar-menu a", function () {
    if (ehMobileSidebar()) {
        aplicarSidebarRecolhido(true, true);
    }
});

$(window).on("resize", function () {
    $("#sidebarOverlay").prop(
        "hidden",
        !ehMobileSidebar() || $("body").hasClass("sidebar-recolhido")
    );
});

$(function () {
    inicializarSidebar();
});

// ========= DEVEDORES =========
const BASE_DEVEDORES = "/Financeiro/devedores";

function aplicarMascaraValorDevedor($input, digitos) {
    digitos = String(digitos || "").replace(/\D/g, "").replace(/^0+/, "").slice(0, 12);
    $input.data("digitos", digitos);

    if (!digitos) {
        $input.val("");
        return;
    }

    const centavos = Number(digitos) / 100;
    $input.val(centavos.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    }));
}

function valorDevedorParaEnvio() {
    const digitos = $("#devedorValor").data("digitos") || "";

    if (!digitos) {
        return "";
    }

    return (Number(digitos) / 100).toFixed(2);
}

$(document).on("keydown", "#devedorValor", function (evento) {
    const tecla = evento.key;
    let digitos = $(this).data("digitos") || "";

    if (tecla === "Backspace") {
        evento.preventDefault();
        aplicarMascaraValorDevedor($(this), digitos.slice(0, -1));
        return;
    }

    if (/^\d$/.test(tecla)) {
        evento.preventDefault();
        aplicarMascaraValorDevedor($(this), digitos + tecla);
        return;
    }

    if (tecla.length === 1 && !evento.ctrlKey && !evento.metaKey) {
        evento.preventDefault();
    }
});

function formatarDataDevedor(data) {
    if (!data) {
        return "";
    }
    const partes = String(data).slice(0, 10).split("-");
    if (partes.length !== 3) {
        return data;
    }
    return partes[2] + "/" + partes[1] + "/" + partes[0];
}

let devedoresCarregados = [];

function carregarDevedores() {
    $.ajax({
        url: BASE_DEVEDORES + "/listar.php",
        type: "GET",
        dataType: "json",
        success: function (res) {
            devedoresCarregados = res.dados || [];
            renderizarDevedores();
            const $tbody = $("#tbodyDevedores");

            $("#resumoDevedores").text(formatarMoeda(res.total || 0));
            $("#resumoDevedoresQtd").text(
                (res.quantidade || 0) === 1 ? "1 pessoa" : (res.quantidade || 0) + " pessoas"
            );

            if (!dados.length) {
                $tbody.html('<tr><td colspan="5" class="text-muted">Nenhum devedor ainda.</td></tr>');
                return;
            }

            let html = "";
            dados.forEach(function (item) {
                html += "<tr>"
                    + "<td>" + $("<div>").text(item.nome).html() + "</td>"
                    + "<td>" + $("<div>").text(item.descricao).html() + "</td>"
                    + '<td class="text-end">' + formatarMoeda(item.valor) + "</td>"
                    + "<td>" + formatarDataDevedor(item.data) + "</td>"
                    + '<td class="lista-acoes-botoes">'
                    + '<button type="button" class="btn btn-acao-lista btn-acao-editar btnPagarDevedor" data-id="' + item.id + '" title="Marcar pago"><i class="bi bi-check-lg"></i></button>'
                    + '<button type="button" class="btn btn-acao-lista btn-acao-excluir btnExcluirDevedor" data-id="' + item.id + '" title="Excluir"><i class="bi bi-trash"></i></button>'
                    + "</td>"
                    + "</tr>";
            });
            $tbody.html(html);

        }
    });
}

function renderizarDevedores() {
    const termo = ($("#pesquisaDevedor").val() || "").trim().toLowerCase();
    const dados = devedoresCarregados.filter(function (item) {
        return String(item.nome || "").toLowerCase().indexOf(termo) !== -1;
    });
    const $tbody = $("#tbodyDevedores");
    const $info = $("#pesquisaDevedorInfo");

    if (!termo) {
        $info.text("");
    } else if (dados.length === 1) {
        $info.text("1 encontrado");
    } else {
        $info.text(dados.length + " encontrados");
    }

    if (!devedoresCarregados.length) {
        $tbody.html('<tr><td colspan="5" class="text-muted">Nenhum devedor ainda.</td></tr>');
        return;
    }

    if (!dados.length) {
        $tbody.html('<tr><td colspan="5" class="text-muted">Nenhum nome encontrado.</td></tr>');
        return;
    }

    let html = "";
    dados.forEach(function (item) {
        html += "<tr>"
            + '<td data-label="Nome">' + $("<div>").text(item.nome).html() + "</td>"
            + '<td class="col-descricao" data-label="Descrição">' + $("<div>").text(item.descricao).html() + "</td>"
            + '<td class="text-end" data-label="Valor">' + formatarMoeda(item.valor) + "</td>"
            + '<td data-label="Data">' + formatarDataDevedor(item.data) + "</td>"
            + '<td class="lista-acoes-botoes col-acoes" data-label="Ações">'
            + '<button type="button" class="btn btn-acao-lista btn-acao-editar btnPagarDevedor" data-id="' + item.id + '" title="Marcar pago"><i class="bi bi-check-lg"></i></button>'
            + '<button type="button" class="btn btn-acao-lista btn-acao-excluir btnExcluirDevedor" data-id="' + item.id + '" title="Excluir"><i class="bi bi-trash"></i></button>'
            + "</td>"
            + "</tr>";
    });
    $tbody.html(html);
}

$(document).on("input", "#pesquisaDevedor", function () {
    renderizarDevedores();
});

function abrirModalDevedor() {
    $("#devedorId").val("");
    $("#devedorNome").val("");
    $("#devedorDescricao").val("");
    $("#devedorValor").val("");
    $("#devedorValor").val("").data("digitos", "");
    $("#devedorData").val(new Date().toISOString().slice(0, 10));

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDevedor"));
    modal.show();
}

$(document).on("click", "#btnNovoDevedor", function () {
    abrirModalDevedor();
});

$(document).on("click", "#btnSalvarDevedor", function () {
    const $botao = $(this);
    $botao.prop("disabled", true);

    $.ajax({
        url: BASE_DEVEDORES + "/salvar.php",
        type: "POST",
        dataType: "json",
        data: {
            nome: $("#devedorNome").val(),
            descricao: $("#devedorDescricao").val(),
            valor: valorDevedorParaEnvio(),
            data: $("#devedorData").val()
        },
        success: function (res) {
            if (res.status !== "sucesso") {
                Swal.fire({
                    icon: "error",
                    title: "Não salvou",
                    text: res.mensagem || "Confira os campos."
                });
                return;
            }

            bootstrap.Modal.getInstance(document.getElementById("modalDevedor")).hide();
            Swal.fire({
                title: "Novo devedor",
                text: "Registro salvo.",
                imageUrl: "/Financeiro/assets/img/new%20member.jpeg",
                imageAlt: "Novo devedor",
                imageWidth: 280,
                confirmButtonText: "Ok"
            });
            carregarDevedores();
        },
        complete: function () {
            $botao.prop("disabled", false);
        }
    });
});

$(document).ready(function () {
    if ($("#tabelaDevedores").length) {
        carregarDevedores();
    }
});

$(document).on("click", ".btnPagarDevedor", function () {
    const id = $(this).data("id");

    $.ajax({
        url: BASE_DEVEDORES + "/pagar.php",
        type: "POST",
        dataType: "json",
        data: { id: id },
        success: function (res) {
            if (res.status !== "sucesso") {
                Swal.fire({ icon: "error", title: res.mensagem || "Não foi possível marcar como pago." });
                return;
            }
            Swal.fire({
                title: "Pago",
                text: "Marcado como pago.",
                imageUrl: "/Financeiro/assets/img/paid.jpeg",
                imageAlt: "Pago",
                imageWidth: 280,
                confirmButtonText: "Ok"
            });
            carregarDevedores();
        }
    });
});

$(document).on("click", ".btnExcluirDevedor", function () {
    const id = $(this).data("id");

    Swal.fire({
        title: "Excluir devedor?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sim, excluir",
        cancelButtonText: "Cancelar"
    }).then(function (result) {
        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: BASE_DEVEDORES + "/excluir.php",
            type: "POST",
            dataType: "json",
            data: { id: id },
            success: function (res) {
                if (res.status !== "sucesso") {
                    Swal.fire({ icon: "error", title: res.mensagem || "Não foi possível excluir." });
                    return;
                }
                carregarDevedores();
            }
        });
    });
});
// ========= PARCELADAS =========
const BASE_PARCELADAS = "/Financeiro/parceladas";
let parceladasCarregadas = [];

function textoParcela(item) {
    return String(item.parcela_atual) + "/" + String(item.parcela_total);
}

function renderizarParceladas() {
    const $tbody = $("#tbodyParceladas");
    const busca = ($("#pesquisaParcelada").val() || "").toLowerCase().trim();

    const filtradas = parceladasCarregadas.filter(function (item) {
        if (!busca) {
            return true;
        }

        const situacao = item.quitada ? "quitada" : "ativa";
        const texto = [
            item.descricao,
            item.categoria,
            textoParcela(item),
            situacao,
            String(item.faltam)
        ].join(" ").toLowerCase();

        return texto.indexOf(busca) !== -1;
    });

    if (!parceladasCarregadas.length) {
        $tbody.html(
            '<tr><td colspan="5" class="text-muted">Nenhuma compra parcelada ainda.</td></tr>'
        );
        $("#pesquisaParceladaInfo").text("");
        return;
    }

    if (!filtradas.length) {
        $tbody.html(
            '<tr><td colspan="5" class="text-muted">Nenhuma compra encontrada.</td></tr>'
        );
        $("#pesquisaParceladaInfo").text("0 resultado");
        return;
    }

    $tbody.empty();

    filtradas.forEach(function (item) {
        const situacao = item.quitada ? "Quitada" : "Ativa";
        const classeSituacao = item.quitada ? "quitada" : "ativa";
        $tbody.append(
            "<tr>" +
            "<td data-label=\"Compra\">" + item.descricao + "</td>" +
            "<td data-label=\"Parcela\">" + textoParcela(item) + "</td>" +
            "<td data-label=\"Valor\" class=\"text-end\">" + formatarMoeda(item.valor_parcela) + "</td>" +
            "<td data-label=\"Faltam\">" + item.faltam + "</td>" +
            "<td data-label=\"Situação\"><span class=\"parcela-situacao " + classeSituacao + "\">" + situacao + "</span></td>" +
            "</tr>"
        );
    });

    $("#pesquisaParceladaInfo").text(
        filtradas.length === parceladasCarregadas.length
            ? ""
            : filtradas.length + " de " + parceladasCarregadas.length
    );
}

function carregarParceladas() {
    if (!$("#tbodyParceladas").length) {
        return;
    }

    $.ajax({
        url: BASE_PARCELADAS + "/listar.php",
        type: "GET",
        dataType: "json",
        success: function (res) {
            parceladasCarregadas = res.dados || [];

            $("#resumoParceladas").text(formatarMoeda(res.total || 0));
            $("#resumoParceladasQtd").text(
                (res.quantidade || 0) === 1
                    ? "1 compra ativa"
                    : (res.quantidade || 0) + " compras ativas"
            );

            renderizarParceladas();
        }
    });
}

$(document).on("input", "#pesquisaParcelada", function () {
    renderizarParceladas();
});

$(document).ready(function () {
    carregarParceladas();
});