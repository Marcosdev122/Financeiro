<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json");

$usuarioId = auth_exigir_api();

function dataValidaComparacao($data)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return false;
    }

    [$ano, $mes, $dia] = array_map("intval", explode("-", $data));

    return checkdate($mes, $dia, $ano);
}

function fimExclusivoComparacao($dataInclusiva)
{
    return date("Y-m-d", strtotime($dataInclusiva . " +1 day"));
}

function deslocarAnoComparacao($data, $ano)
{
    $mes = (int) substr($data, 5, 2);
    $dia = (int) substr($data, 8, 2);
    $ultimoDia = (int) date("t", strtotime(sprintf("%04d-%02d-01", $ano, $mes)));

    if ($dia > $ultimoDia) {
        $dia = $ultimoDia;
    }

    return sprintf("%04d-%02d-%02d", $ano, $mes, $dia);
}

function buscarResumoComparacao(PDO $conn, int $usuarioId, $inicio, $fim)
{
    $sql = "
        SELECT
            COALESCE(SUM(CASE WHEN tipo = 'Receita' THEN valor ELSE 0 END), 0) AS receitas,
            COALESCE(SUM(CASE WHEN tipo = 'Despesa' THEN valor ELSE 0 END), 0) AS despesas
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
        "saldo" => $receitas - $despesas
    ];
}

function anosCadastrados(PDO $conn, int $usuarioId)
{
    $sql = "
        SELECT DISTINCT YEAR(data) AS ano
        FROM movimentacoes
        WHERE usuario_id = :usuario_id
        ORDER BY ano ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmt->execute();

    return array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$inicio = $_GET["inicio"] ?? date("Y-01-01");
$fimInclusivo = $_GET["fim"] ?? date("Y-m-d");

if (!dataValidaComparacao($inicio)) {
    $inicio = date("Y-01-01");
}

if (!dataValidaComparacao($fimInclusivo)) {
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

$anoInicio = (int) substr($inicio, 0, 4);
$anoFim = (int) substr($fimInclusivo, 0, 4);
$anosBanco = anosCadastrados($conn, $usuarioId);
$mesmoAno = $anoInicio === $anoFim;

if ($mesmoAno) {
    $anos = array_values(array_unique(array_merge($anosBanco, [$anoInicio])));
    sort($anos);
} else {
    $anos = range($anoInicio, $anoFim);
}

$listaAnos = [];
$receitas = [];
$despesas = [];
$saldos = [];
$totalReceitas = 0;
$totalDespesas = 0;

foreach ($anos as $ano) {
    if ($mesmoAno) {
        $inicioAno = deslocarAnoComparacao($inicio, $ano);
        $fimAno = deslocarAnoComparacao($fimInclusivo, $ano);
    } else {
        $inicioAno = sprintf("%04d-01-01", $ano);
        $fimAno = sprintf("%04d-12-31", $ano);

        if ($inicioAno < $inicio) {
            $inicioAno = $inicio;
        }

        if ($fimAno > $fimInclusivo) {
            $fimAno = $fimInclusivo;
        }
    }

    if ($inicioAno > $fimAno) {
        $resumo = [
            "receitas" => 0,
            "despesas" => 0,
            "saldo" => 0
        ];
    } else {
        $resumo = buscarResumoComparacao(
            $conn,
            $usuarioId,
            $inicioAno,
            fimExclusivoComparacao($fimAno)
        );
    }

    $listaAnos[] = $ano;
    $receitas[] = $resumo["receitas"];
    $despesas[] = $resumo["despesas"];
    $saldos[] = $resumo["saldo"];

    $totalReceitas += $resumo["receitas"];
    $totalDespesas += $resumo["despesas"];
}

echo json_encode([
    "status" => "sucesso",
    "dados" => [
        "inicio" => $inicio,
        "fim" => $fimInclusivo,
        "anos" => $listaAnos,
        "receitas" => $receitas,
        "despesas" => $despesas,
        "saldos" => $saldos,
        "totais" => [
            "receitas" => $totalReceitas,
            "despesas" => $totalDespesas,
            "saldo" => $totalReceitas - $totalDespesas
        ]
    ]
]);
