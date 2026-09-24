<?php

function textoMovimentacaoParaValidacao($categoria, $descricao = '')
{
    $descricao = trim((string) $descricao);

    if ($descricao !== '') {
        return $descricao;
    }

    return trim((string) $categoria);
}

function termosMovimentacaoIgnorados()
{
    return [
        'pagamento recebido',
        'pagamento da fatura',
        'pagamento de fatura',
        'pagamento fatura',
    ];
}

function movimentacaoIgnorada($categoria, $descricao = '')
{
    $texto = mb_strtolower(textoMovimentacaoParaValidacao($categoria, $descricao), 'UTF-8');
    $texto = preg_replace('/\s+/', ' ', $texto);

    if ($texto === '') {
        return false;
    }

    foreach (termosMovimentacaoIgnorados() as $termo) {
        if (strpos($texto, $termo) !== false) {
            return true;
        }
    }

    return false;
}
