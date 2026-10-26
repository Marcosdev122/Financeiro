<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

$usuarioId = auth_exigir_api();
$tipo = $_GET['tipo'] ?? '';

if (!in_array($tipo, ['Receita', 'Despesa'], true)) {
    http_response_code(400);
    echo "<tr><td colspan='5'>Tipo inválido.</td></tr>";
    exit;
}

function dataValidaLista($data)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return false;
    }

    [$ano, $mes, $dia] = array_map("intval", explode("-", $data));

    return checkdate($mes, $dia, $ano);
}

$inicio = $_GET['inicio'] ?? date('Y-01-01');
$fimInclusivo = $_GET['fim'] ?? date('Y-m-d');

if (!dataValidaLista($inicio)) {
    $inicio = date('Y-01-01');
}

if (!dataValidaLista($fimInclusivo)) {
    $fimInclusivo = date('Y-m-d');
}

if ($inicio > $fimInclusivo) {
    $tmp = $inicio;
    $inicio = $fimInclusivo;
    $fimInclusivo = $tmp;
}

$fimExclusivo = date('Y-m-d', strtotime($fimInclusivo . ' +1 day'));

$sql = "
    SELECT
        id,
        categoria,
        descricao,
        tipo,
        valor,
        data,
        atualizado_em
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

$classeValor = $tipo === 'Receita' ? 'text-success' : 'text-danger';
$prefixoValor = $tipo === 'Receita' ? '+ R$ ' : '- R$ ';

while ($movimentacao = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $valorFormatado = number_format(
        $movimentacao['valor'],
        2,
        ',',
        '.'
    );

    $dataFormatada = date(
        'd/m/Y',
        strtotime($movimentacao['data'])
    );

    $atualizadoEm = $movimentacao['atualizado_em'] ?? '';
    $atualizadoFormatado = '-';

    if ($atualizadoEm !== '') {
        $atualizadoFormatado = date('d/m/Y H:i', strtotime($atualizadoEm));
    }

    $descricao = trim((string) ($movimentacao['descricao'] ?? ''));
    $categoriaExibida = trim((string) $movimentacao['categoria']);

    if ($categoriaExibida === '') {
        $categoriaExibida = 'Outros';
    }

    $categoriaExibida = htmlspecialchars($categoriaExibida, ENT_QUOTES, 'UTF-8');

    if ($descricao !== '') {
        $descricaoHtml = htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8');
        $conteudoCategoria = "
            <div class=\"celula-categoria\">
                <span class=\"celula-categoria-nome\">{$categoriaExibida}</span>
                <span class=\"celula-categoria-descricao\">{$descricaoHtml}</span>
            </div>";
    } else {
        $conteudoCategoria = $categoriaExibida;
    }

    $celulaCategoria = "
        <td class=\"col-categoria\" data-label=\"Categoria\">
            {$conteudoCategoria}
        </td>
    ";

    echo "
        <tr data-tipo='{$movimentacao['tipo']}'>
            {$celulaCategoria}
            <td class='{$classeValor} valor-tabela' data-label='Valor'>
                {$prefixoValor}{$valorFormatado}
            </td>
            <td class='col-data' data-label='Data'>{$dataFormatada}</td>
            <td class='text-muted col-atualizado' data-label='Atualizado'>{$atualizadoFormatado}</td>
            <td class='col-acoes' data-label='Ações'>
                <div class='lista-acoes-botoes'>
                    <button
                        class='btn btn-acao-lista btn-acao-editar btnEditarMovimentacao'
                        data-id='{$movimentacao['id']}'
                        title='Editar'>
                        <i class='bi bi-pencil'></i>
                    </button>
                    <button
                        class='btn btn-acao-lista btn-acao-excluir btnExcluirMovimentacao'
                        data-id='{$movimentacao['id']}'
                        title='Excluir'>
                        <i class='bi bi-trash'></i>
                    </button>
                </div>
            </td>
        </tr>
    ";
}
