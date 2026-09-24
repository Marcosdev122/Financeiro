<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

$usuarioId = auth_exigir_api();

ini_set('display_errors', '0');
error_reporting(0);
$tipo = $_GET['tipo'] ?? '';
if (!in_array($tipo, ['Receita', 'Despesa'], true)) {
    http_response_code(400);
    exit('Tipo inválido.');
}

$inicio = $_GET['inicio'] ?? date('Y-01-01');
$fimInclusivo = $_GET['fim'] ?? date('Y-m-d');

$fimExclusivo = date('Y-m-d', strtotime($fimInclusivo . ' +1 day'));

$sql = "
    SELECT categoria, descricao, tipo, valor, data
    FROM movimentacoes
    WHERE tipo = :tipo
      AND usuario_id = :usuario_id
      AND data >= :inicio
      AND data < :fim
    ORDER BY data DESC, id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(":tipo", $tipo);
$stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
$stmt->bindValue(":inicio", $inicio);
$stmt->bindValue(":fim", $fimExclusivo);
$stmt->execute();

$nomeArquivo = ($tipo === 'Receita' ? 'receitas' : 'despesas')
. '_' . $inicio . '_' . $fimInclusivo . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$saida = fopen('php://output', 'w');
fwrite($saida, "\xEF\xBB\xBF");
fputcsv($saida, ['Categoria', 'Descricao', 'Tipo', 'Valor', 'Data'], ';');
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($registros as $row) {
    $dataFormatada = '';
    if (!empty($row['data'])) {
        $dataFormatada = "\t" . date('d/m/Y', strtotime($row['data']));
    }

    $ehReceita = ($row['tipo'] ?? '') === 'Receita';
    $sinalValor = $ehReceita ? '+' : '-';
    $valorFormatado = $sinalValor . number_format(
        (float) ($row['valor'] ?? 0),
        2,
        ',',
        ''
    );

    fputcsv($saida, [
        $row['categoria'] ?? '',
        $row['descricao'] ?? '',
        $row['tipo'] ?? '',
        $valorFormatado,
        $dataFormatada,
    ], ';');
}
fclose($saida);
exit;
