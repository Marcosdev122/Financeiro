<?php

function exigirTexto($valor, $campo, $min = 1, $max = 255)
{
    $v = trim((string) $valor);

    if ($v === "" || mb_strlen($v) < $min) {
        return "$campo é obrigatório.";
    }

    if (mb_strlen($v) > $max) {
        return "$campo muito longo (máx. $max).";
    }

    return null;
}

function exigirEmail($email)
{
    $email = trim((string) $email);

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
        return "Informe um e-mail válido.";
    }

    return null;
}

function exigirTelefoneBr($tel, &$normalizado = null)
{
    $tel = preg_replace('/\D+/', '', (string) $tel);

    if (strlen($tel) < 10 || strlen($tel) > 13) {
        return "Informe um WhatsApp válido com DDD.";
    }

    if (!str_starts_with($tel, "55")) {
        $tel = "55" . $tel;
    }

    if (strlen($tel) < 12 || strlen($tel) > 20) {
        return "Número de WhatsApp inválido.";
    }

    $normalizado = $tel;
    return null;
}

function exigirValorPositivo($valor)
{
    if (!is_numeric($valor) || (float) $valor <= 0) {
        return "Informe um valor válido.";
    }

    return null;
}

function exigirDataISO($data, $maxHoje = true)
{
    $data = trim((string) $data);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        return "Informe uma data válida.";
    }

    $partes = explode("-", $data);
    if (!checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
        return "Informe uma data válida.";
    }

    if ($maxHoje && $data > date("Y-m-d")) {
        return "A data não pode ser futura.";
    }

    return null;
}