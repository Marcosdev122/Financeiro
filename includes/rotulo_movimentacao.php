<?php

function rotuloExibicaoMovimentacao($categoria, $descricao = '')
{
    $descricao = trim((string) $descricao);
    $categoria = trim((string) $categoria);

    if ($descricao !== '') {
        return $descricao;
    }

    if ($categoria !== '') {
        return $categoria;
    }

    return 'Outros';
}
