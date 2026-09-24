<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json");

$usuarioId = auth_exigir_api();

function nomesMeses()
{
    return [
        1 => "Jan",
        2 => "Fev",
        3 => "Mar",
        4 => "Abr",
        5 => "Mai",
        6 => "Jun",
        7 => "Jul",
        8 => "Ago",
        9 => "Set",
        10 => "Out",
        11 => "Nov",
        12 => "Dez"
    ];
}

function dataValida($data)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return false;
    }

    [$ano, $mes, $dia] = array_map("intval", explode("-", $data));

    return checkdate($mes, $dia, $ano);
}

function fimExclusivo($dataInclusiva)
{
    return date("Y-m-d", strtotime($dataInclusiva . " +1 day"));
}

function deslocarAno($data, $ano)
{
    $mes = (int) substr($data, 5, 2);
    $dia = (int) substr($data, 8, 2);
    $ultimoDia = (int) date("t", strtotime(sprintf("%04d-%02d-01", $ano, $mes)));

    if ($dia > $ultimoDia) {
        $dia = $ultimoDia;
    }

    return sprintf("%04d-%02d-%02d", $ano, $mes, $dia);
}

function buscarResumo(PDO $conn, int $usuarioId, $inicio, $fim)
{
    $sql = "
        SELECT
            COALESCE(SUM(CASE WHEN tipo = 'Receita' THEN valor ELSE 0 END), 0) AS receitas,
            COALESCE(SUM(CASE WHEN tipo = 'Despesa' THEN valor ELSE 0 END), 0) AS despesas,
            COUNT(*) AS movimentacoes
        FROM movimentacoes
        WHERE usuario_id = :usuario_id
          AND data >= :inicio
          AND data < :fim
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(":inicio", $inicio);
    $stmt->bindValue(":fim", $fim);
    $stmt->execute();

    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    $receitas = (float) $dados["receitas"];
    $despesas = (float) $dados["despesas"];

    return [
        "receitas" => $receitas,
        "despesas" => $despesas,
        "saldo" => $receitas - $despesas,
        "movimentacoes" => (int) $dados["movimentacoes"]
    ];
}

function buscarEvolucao(PDO $conn, int $usuarioId, $inicio, $fimInclusivo)
{
    $fim = fimExclusivo($fimInclusivo);
    $meses = nomesMeses();

    $sql = "
        SELECT
            YEAR(data) AS ano,
            MONTH(data) AS numero_mes,
            COALESCE(SUM(CASE WHEN tipo = 'Receita' THEN valor ELSE 0 END), 0) AS receitas,
            COALESCE(SUM(CASE WHEN tipo = 'Despesa' THEN valor ELSE 0 END), 0) AS despesas
        FROM movimentacoes
        WHERE usuario_id = :usuario_id
          AND data >= :inicio
          AND data < :fim
        GROUP BY YEAR(data), MONTH(data)
        ORDER BY ano ASC, numero_mes ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(":inicio", $inicio);
    $stmt->bindValue(":fim", $fim);
    $stmt->execute();

    $porMes = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $chave = $item["ano"] . "-" . $item["numero_mes"];
        $porMes[$chave] = $item;
    }

    $mensal = [];
    $saldoAcumulado = 0;
    $cursor = date("Y-m-01", strtotime($inicio));
    $fimMes = date("Y-m-01", strtotime($fimInclusivo));

    while ($cursor <= $fimMes) {
        $ano = (int) date("Y", strtotime($cursor));
        $mes = (int) date("n", strtotime($cursor));
        $chave = $ano . "-" . $mes;
        $receitas = 0;
        $despesas = 0;

        if (isset($porMes[$chave])) {
            $receitas = (float) $porMes[$chave]["receitas"];
            $despesas = (float) $porMes[$chave]["despesas"];
        }

        $fluxoMes = $receitas - $despesas;
        $saldoAcumulado += $fluxoMes;

        $mensal[] = [
            "mes" => $meses[$mes] . "/" . $ano,
            "receitas" => $receitas,
            "despesas" => $despesas,
            "fluxo" => $fluxoMes,
            "saldo" => $saldoAcumulado
        ];

        $cursor = date("Y-m-01", strtotime($cursor . " +1 month"));
    }

    return [
        "visao" => "mes",
        "textoVisao" => "por mês",
        "evolucao" => $mensal
    ];
}

$inicio = $_GET["inicio"] ?? date("Y-01-01");
$fimInclusivo = $_GET["fim"] ?? date("Y-m-d");

if (!dataValida($inicio)) {
    $inicio = date("Y-01-01");
}

if (!dataValida($fimInclusivo)) {
    $fimInclusivo = date("Y-m-d");
}

if ($inicio > $fimInclusivo) {
    $tmp = $inicio;
    $inicio = $fimInclusivo;
    $fimInclusivo = $tmp;
}

$hoje = date("Y-m-d");

if ($inicio > $hoje) {
    $inicio = $hoje;
}

if ($fimInclusivo > $hoje) {
    $fimInclusivo = $hoje;
}

$fim = fimExclusivo($fimInclusivo);
$anoPrincipal = (int) substr($inicio, 0, 4);

function buscarAgrupamento(PDO $conn, int $usuarioId, $inicio, $fim, $modo)
{
    if ($modo === "descricao") {
        // Cada lançamento (não soma)
        $sql = "
            SELECT
                id,
                COALESCE(
                    NULLIF(TRIM(descricao), ''),
                    COALESCE(NULLIF(TRIM(categoria), ''), 'Outros')
                ) AS categoria,
                tipo,
                valor AS total,
                data
            FROM movimentacoes
            WHERE usuario_id = :usuario_id
              AND data >= :inicio
              AND data < :fim
            ORDER BY valor DESC, data DESC, id DESC
        ";
    } else {
        // Por categoria (soma iguais)
        $sql = "
            SELECT
                COALESCE(NULLIF(TRIM(categoria), ''), 'Outros') AS categoria,
                tipo,
                COALESCE(SUM(valor), 0) AS total
            FROM movimentacoes
            WHERE usuario_id = :usuario_id
              AND data >= :inicio
              AND data < :fim
            GROUP BY COALESCE(NULLIF(TRIM(categoria), ''), 'Outros'), tipo
            ORDER BY total DESC
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(":inicio", $inicio);
    $stmt->bindValue(":fim", $fim);
    $stmt->execute();

    $receitas = [];
    $despesas = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $linha = [
            "categoria" => $item["categoria"],
            "total" => (float) $item["total"]
        ];

        if ($item["tipo"] === "Receita") {
            $receitas[] = $linha;
        } else {
            $despesas[] = $linha;
        }
    }

    return [
        "receitas" => $receitas,
        "despesas" => $despesas
    ];
}

$resumo = buscarResumo($conn, $usuarioId, $inicio, $fim);
$principal = buscarEvolucao($conn, $usuarioId, $inicio, $fimInclusivo);

$porCategoria = buscarAgrupamento($conn, $usuarioId, $inicio, $fim, "categoria");
$porDescricao = buscarAgrupamento($conn, $usuarioId, $inicio, $fim, "descricao");
$receitasPorCategoria = $porCategoria["receitas"];
$despesasPorCategoria = $porCategoria["despesas"];
$receitasPorDescricao = $porDescricao["receitas"];
$despesasPorDescricao = $porDescricao["despesas"];

echo json_encode([
    "status" => "sucesso",
    "dados" => [
        "inicio" => $inicio,
        "fim" => $fimInclusivo,
        "visao" => $principal["visao"],
        "textoVisao" => $principal["textoVisao"],
        "ano" => $anoPrincipal,
        "receitas" => $resumo["receitas"],
        "despesas" => $resumo["despesas"],
        "saldo" => $resumo["saldo"],
        "movimentacoes" => $resumo["movimentacoes"],
        "evolucao" => $principal["evolucao"],
        "totalAnos" => ((int) substr($fimInclusivo, 0, 4) - (int) substr($inicio, 0, 4)) + 1,
        "receitasPorCategoria" => $receitasPorCategoria,
        "despesasPorCategoria" => $despesasPorCategoria,
        "receitasPorDescricao" => $receitasPorDescricao,
        "despesasPorDescricao" => $despesasPorDescricao
    ]
]);
