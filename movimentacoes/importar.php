<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../includes/movimentacao_regras.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$categoriasPorTipo = require __DIR__ . "/../config/categorias.php";

$tipoPagina = $_POST['tipo'] ?? '';

if (!in_array($tipoPagina, ['Receita', 'Despesa'], true)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Tipo inválido."
    ]);
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Nenhum arquivo enviado ou falha no upload."
    ]);
    exit;
}

$arquivo = $_FILES['arquivo'];
$nome = strtolower($arquivo['name'] ?? '');
$extensao = pathinfo($nome, PATHINFO_EXTENSION);

if (!in_array($extensao, ['csv', 'xlsx', 'xls'], true)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Formato inválido. Use .csv, .xlsx ou .xls."
    ]);
    exit;
}

if ($extensao !== 'csv') {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Por enquanto a importação aceita apenas CSV. Exporte pelo sistema, edite no Excel e salve como CSV."
    ]);
    exit;
}

function normalizarCabecalhoImportacao($texto)
{
    $texto = trim((string) $texto);
    $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
    $texto = strtolower($texto);
    $texto = str_replace(
        ['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
        ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
        $texto
    );
    $texto = preg_replace('/[^a-z0-9]/', '', $texto);

    return $texto;
}

function normalizarTipoTextoImportacao($texto)
{
    $texto = trim((string) $texto);
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[^a-z]/', '', $texto);

    $mapa = [
        'receita' => 'Receita',
        'receitas' => 'Receita',
        'entrada' => 'Receita',
        'entradas' => 'Receita',
        'despesa' => 'Despesa',
        'despesas' => 'Despesa',
        'saida' => 'Despesa',
        'saidas' => 'Despesa',
    ];

    return $mapa[$texto] ?? null;
}

function detectarTipoPorValorImportacao($texto)
{
    $texto = trim((string) $texto);

    if ($texto === '') {
        return null;
    }

    // Só + entra em Receita (ex: +40, + R$ 40)
    if (preg_match('/^\s*\+/', $texto) || preg_match('/\+\s*R?\$/i', $texto)) {
        return 'Receita';
    }

    // 40 ou -40 (e similares) entram em Despesa
    if (preg_match('/\d/', $texto)) {
        return 'Despesa';
    }

    return null;
}

function resolverTipoImportacao($tipoColuna, $valorBruto, $tipoPagina)
{
    // Prioridade: sinal do valor (+ = receita; sem sinal ou - = despesa)
    $tipo = detectarTipoPorValorImportacao($valorBruto);

    if ($tipo !== null) {
        return $tipo;
    }

    $tipo = normalizarTipoTextoImportacao($tipoColuna);

    if ($tipo !== null) {
        return $tipo;
    }

    // Sem sinal de receita: padrão é despesa
    return 'Despesa';
}

function parseValorImportacao($valorBruto)
{
    $bruto = trim((string) $valorBruto);
    $bruto = trim($bruto, " \t\n\r\0\x0B'\"");
    $bruto = ltrim($bruto, "\t'");

    if ($bruto === '') {
        return null;
    }

    $valor = preg_replace('/[^\d,.+-]/', '', $bruto);
    $valor = str_replace('+', '', $valor);

    if ($valor === '' || $valor === '-' || $valor === '.') {
        return null;
    }

    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    $valor = ltrim($valor, '-');

    if (!is_numeric($valor)) {
        return null;
    }

    $numero = abs((float) $valor);

    return $numero > 0 ? $numero : null;
}

function parseDataImportacao($data)
{
    $data = trim((string) $data);
    $data = ltrim($data, "\t'");

    if ($data === '') {
        return null;
    }

    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $data, $partes)) {
        [$ano, $mes, $dia] = array_map('intval', explode('-', $partes[1]));

        return checkdate($mes, $dia, $ano) ? $partes[1] : null;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $data, $partes)) {
        $dia = (int) $partes[1];
        $mes = (int) $partes[2];
        $ano = (int) $partes[3];

        if (!checkdate($mes, $dia, $ano)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
    }

    if (is_numeric($data)) {
        $serial = (int) $data;

        if ($serial >= 25569) {
            $timestamp = ($serial - 25569) * 86400;
            $convertida = gmdate('Y-m-d', $timestamp);

            return parseDataImportacao($convertida);
        }
    }

    return null;
}

function linhaImportacaoVazia(array $campos)
{
    foreach ($campos as $campo) {
        if (trim((string) $campo) !== '') {
            return false;
        }
    }

    return true;
}

function mapearColunasImportacao(array $cabecalho)
{
    $mapa = [];
    $aliases = [
        'categoria' => 'categoria',
        'descricao' => 'descricao',
        'tipo' => 'tipo',
        'valor' => 'valor',
        'data' => 'data',
        'date' => 'data',
        'title' => 'descricao',
        'titulo' => 'descricao',
        'amount' => 'valor',
        'montante' => 'valor',
    ];

    foreach ($cabecalho as $indice => $coluna) {
        $normalizado = normalizarCabecalhoImportacao($coluna);

        if (isset($aliases[$normalizado])) {
            $mapa[$aliases[$normalizado]] = $indice;
        }
    }

    return $mapa;
}

function detectarDelimitadorCsvImportacao($conteudo)
{
    $primeiraLinha = strtok($conteudo, "\n");

    if ($primeiraLinha === false) {
        return ',';
    }

    $cabecalho = strtolower($primeiraLinha);

    if (strpos($cabecalho, 'date,title,amount') !== false) {
        return ',';
    }

    if (strpos($cabecalho, 'categoria;') !== false || substr_count($primeiraLinha, ';') >= 4) {
        return ';';
    }

    if (substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',')) {
        return ';';
    }

    return ',';
}

function lerCsvImportacao($caminho)
{
    $conteudo = file_get_contents($caminho);

    if ($conteudo === false) {
        return null;
    }

    if (substr($conteudo, 0, 3) === "\xEF\xBB\xBF") {
        $conteudo = substr($conteudo, 3);
    }

    $delimitador = detectarDelimitadorCsvImportacao($conteudo);
    $handle = fopen('php://temp', 'r+');

    if ($handle === false) {
        return null;
    }

    fwrite($handle, $conteudo);
    rewind($handle);

    $linhas = [];

    while (($linha = fgetcsv($handle, 0, $delimitador)) !== false) {
        $linhas[] = $linha;
    }

    fclose($handle);

    return $linhas;
}

function valorColunaImportacao(array $linha, array $mapa, $campo, $indicePadrao)
{
    if (isset($mapa[$campo])) {
        return $linha[$mapa[$campo]] ?? '';
    }

    return $linha[$indicePadrao] ?? '';
}

function categoriaValidaImportacao($categoria, $tipo, array $categoriasPorTipo)
{
    $categoriasValidas = $categoriasPorTipo[$tipo] ?? [];

    if ($categoria === '' || !in_array($categoria, $categoriasValidas, true)) {
        return 'Outros';
    }

    return $categoria;
}

function nomeImportacao($categoria, $descricao)
{
    $descricao = trim((string) $descricao);

    if ($descricao !== '') {
        return $descricao;
    }

    return trim((string) $categoria);
}

function movimentacaoJaExiste(PDO $conn, $nome, $valor, int $usuarioId)
{
    $stmt = $conn->prepare("
        SELECT id
        FROM movimentacoes
        WHERE usuario_id = :usuario_id
          AND valor = :valor
          AND (
            (descricao IS NOT NULL AND descricao <> '' AND descricao = :nome)
            OR
            ((descricao IS NULL OR descricao = '') AND categoria = :nome)
          )
        LIMIT 1
    ");
    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':nome' => $nome,
        ':valor' => $valor,
    ]);

    return (bool) $stmt->fetchColumn();
}

$linhas = lerCsvImportacao($arquivo['tmp_name']);

if ($linhas === null || count($linhas) === 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível ler o arquivo CSV."
    ]);
    exit;
}

$cabecalho = array_shift($linhas);
$mapa = mapearColunasImportacao($cabecalho);
$formatoExtrato = isset($mapa['data'], $mapa['descricao'], $mapa['valor']);

if (!isset($mapa['valor']) || !isset($mapa['data'])) {
    $mapa = [
        'categoria' => 0,
        'descricao' => 1,
        'tipo' => 2,
        'valor' => 3,
        'data' => 4,
    ];
    array_unshift($linhas, $cabecalho);
    $formatoExtrato = false;
}

$hoje = date('Y-m-d');
$importados = 0;
$importadosReceita = 0;
$importadosDespesa = 0;
$duplicados = 0;
$ignorados = 0;
$erros = [];
$nomesValoresVistos = [];

$sqlMovimentacao = "
    INSERT INTO movimentacoes (usuario_id, categoria, descricao, tipo, valor, data, criado_em)
    VALUES (:usuario_id, :categoria, :descricao, :tipo, :valor, :data, NOW())
";
$stmtMovimentacao = $conn->prepare($sqlMovimentacao);

$sqlEspelhoReceita = "
    INSERT INTO receitas (usuario_id, categoria, valor, data)
    VALUES (:usuario_id, :categoria, :valor, :data)
";
$sqlEspelhoDespesa = "
    INSERT INTO despesas (usuario_id, categoria, valor, data)
    VALUES (:usuario_id, :categoria, :valor, :data)
";
$stmtEspelhoReceita = $conn->prepare($sqlEspelhoReceita);
$stmtEspelhoDespesa = $conn->prepare($sqlEspelhoDespesa);

$conn->beginTransaction();

try {
    foreach ($linhas as $indice => $linha) {
        $numeroLinha = $indice + 2;

        if (!is_array($linha) || linhaImportacaoVazia($linha)) {
            continue;
        }

        $categoria = trim((string) valorColunaImportacao($linha, $mapa, 'categoria', 0));
        $descricao = trim((string) valorColunaImportacao($linha, $mapa, 'descricao', 1));
        $tipoColuna = trim((string) valorColunaImportacao($linha, $mapa, 'tipo', 2));
        $valorBruto = valorColunaImportacao($linha, $mapa, 'valor', 3);
        $dataBruta = valorColunaImportacao($linha, $mapa, 'data', 4);

        if ($formatoExtrato && $descricao !== '') {
            $categoria = 'Outros';
        }

        if (movimentacaoIgnorada($categoria, $descricao)) {
            $ignorados++;
            continue;
        }

        $tipo = resolverTipoImportacao($tipoColuna, $valorBruto, $tipoPagina);

        $valor = parseValorImportacao($valorBruto);

        if ($valor === null) {
            $erros[] = "Linha {$numeroLinha}: valor inválido.";
            continue;
        }

        $data = parseDataImportacao($dataBruta);

        if ($data === null) {
            $erros[] = "Linha {$numeroLinha}: data inválida.";
            continue;
        }

        if ($data > $hoje) {
            $erros[] = "Linha {$numeroLinha}: data não pode ser futura.";
            continue;
        }

        $categoria = categoriaValidaImportacao($categoria, $tipo, $categoriasPorTipo);

        if (strlen($descricao) > 255) {
            $descricao = substr($descricao, 0, 255);
        }

        $nome = nomeImportacao($categoria, $descricao);
        $chaveDuplicata = mb_strtolower($nome, 'UTF-8') . '|' . number_format($valor, 2, '.', '');

        if (isset($nomesValoresVistos[$chaveDuplicata]) || movimentacaoJaExiste($conn, $nome, $valor, $usuarioId)) {
            $duplicados++;
            continue;
        }

        $nomesValoresVistos[$chaveDuplicata] = true;

        $stmtMovimentacao->execute([
            ':usuario_id' => $usuarioId,
            ':categoria' => $categoria,
            ':descricao' => $descricao !== '' ? $descricao : null,
            ':tipo' => $tipo,
            ':valor' => $valor,
            ':data' => $data,
        ]);

        if ($tipo === 'Receita') {
            $stmtEspelhoReceita->execute([
                ':usuario_id' => $usuarioId,
                ':categoria' => $categoria,
                ':valor' => $valor,
                ':data' => $data,
            ]);
            $importadosReceita++;
        } else {
            $stmtEspelhoDespesa->execute([
                ':usuario_id' => $usuarioId,
                ':categoria' => $categoria,
                ':valor' => $valor,
                ':data' => $data,
            ]);
            $importadosDespesa++;
        }

        $importados++;
    }

    if ($importados === 0) {
        $conn->rollBack();

        if ($ignorados > 0 && $duplicados === 0 && count($erros) === 0) {
            $mensagemErro = $ignorados === 1
                ? "Nenhuma movimentação importada. 1 linha ignorada (ex.: pagamento recebido)."
                : "Nenhuma movimentação importada. {$ignorados} linhas ignoradas (ex.: pagamento recebido).";
        } elseif ($duplicados > 0) {
            $mensagemErro = "Nenhuma movimentação nova. {$duplicados} já existiam (mesmo nome e valor).";
        } else {
            $mensagemErro = "Nenhuma movimentação importada.";
        }

        echo json_encode([
            "status" => "erro",
            "mensagem" => $mensagemErro,
            "importados" => 0,
            "duplicados" => $duplicados,
            "ignorados" => $ignorados,
            "erros" => $erros
        ]);
        exit;
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollBack();

    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao importar: " . $e->getMessage()
    ]);
    exit;
}

$resposta = [
    "status" => "sucesso",
    "mensagem" => $importados === 1
        ? "1 movimentação importada com sucesso."
        : "{$importados} movimentações importadas com sucesso.",
    "importados" => $importados,
    "importadosReceita" => $importadosReceita,
    "importadosDespesa" => $importadosDespesa,
    "duplicados" => $duplicados,
    "ignorados" => $ignorados,
    "erros" => $erros,
    "aviso" => null
];

if ($ignorados > 0) {
    $resposta["mensagem"] .= $ignorados === 1
        ? " 1 linha ignorada (ex.: pagamento recebido)."
        : " {$ignorados} linhas ignoradas (ex.: pagamento recebido).";
}

if ($duplicados > 0) {
    $resposta["mensagem"] .= $duplicados === 1
        ? " 1 registro ignorado por já existir (mesmo nome e valor)."
        : " {$duplicados} registros ignorados por já existirem (mesmo nome e valor).";
}

if ($tipoPagina === 'Receita' && $importadosDespesa > 0) {
    if ($importadosReceita === 0) {
        $resposta["aviso"] = "O documento importado só tinha despesas. Nenhuma receita foi encontrada (só entram em receita valores com sinal +). Confira a página de Despesas.";
    } elseif ($importadosReceita === 1) {
        $resposta["aviso"] = "O documento tem só 1 receita e o resto são despesas ({$importadosDespesa}). As despesas foram salvas na página de Despesas.";
    } else {
        $resposta["aviso"] = "O documento tem só {$importadosReceita} receitas e o resto são despesas ({$importadosDespesa}). As despesas foram salvas na página de Despesas.";
    }
}

if (count($erros) > 0) {
    $resposta["mensagem"] .= " Algumas linhas foram ignoradas.";
}

echo json_encode($resposta);
