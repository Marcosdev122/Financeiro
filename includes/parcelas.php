<?php

function extrairParcelaDaDescricao($texto)
{
    $texto = trim((string) $texto);

    if ($texto === '') {
        return null;
    }

    if (!preg_match('/\b(\d{1,2})\s*\/\s*(\d{1,2})\b/', $texto, $achado)) {
        return null;
    }

    $atual = (int) $achado[1];
    $total = (int) $achado[2];

    if ($atual < 1 || $total < 2 || $atual > $total || $total > 48) {
        return null;
    }

    return [
        "atual" => $atual,
        "total" => $total,
    ];
}

function nomeCompraParcelada($descricao)
{
    $nome = trim((string) $descricao);
    $nome = preg_replace('/\bparcela\b/iu', '', $nome);
    $nome = preg_replace('/\b\d{1,2}\s*\/\s*\d{1,2}\b/', '', $nome);
    $nome = preg_replace('/\s+/', ' ', $nome);

    return trim($nome, " \t-–—|");
}

function mesesEntreDatas($dataInicial, $dataReferencia = null)
{
    $inicio = new DateTime(substr((string) $dataInicial, 0, 10));
    $fim = new DateTime($dataReferencia ? substr((string) $dataReferencia, 0, 10) : date("Y-m-d"));

    $meses = ((int) $fim->format("Y") - (int) $inicio->format("Y")) * 12;
    $meses += (int) $fim->format("n") - (int) $inicio->format("n");

    return max(0, $meses);
}

function calcularParcelaAtual($parcelaInicial, $parcelaTotal, $dataPrimeira, $hoje = null)
{
    $atual = (int) $parcelaInicial + mesesEntreDatas($dataPrimeira, $hoje);

    return min((int) $parcelaTotal, $atual);
}

function parcelaEstaQuitada($parcelaInicial, $parcelaTotal, $dataPrimeira, $hoje = null)
{
    $atual = (int) $parcelaInicial + mesesEntreDatas($dataPrimeira, $hoje);

    return $atual > (int) $parcelaTotal;
}

function registrarCompraParcelada(
    PDO $conn,
    $usuarioId,
    $descricao,
    $categoria,
    $valor,
    $data,
    $parcelaInicial,
    $parcelaTotal
) {
    $nome = nomeCompraParcelada($descricao);

    if ($nome === '') {
        $nome = trim((string) $descricao);
    }

    $busca = $conn->prepare("
        SELECT id
        FROM compras_parceladas
        WHERE usuario_id = :usuario_id
          AND descricao = :descricao
          AND valor_parcela = :valor
          AND parcela_total = :total
        LIMIT 1
    ");
    $busca->execute([
        ":usuario_id" => $usuarioId,
        ":descricao" => $nome,
        ":valor" => $valor,
        ":total" => $parcelaTotal,
    ]);

    $id = $busca->fetchColumn();

    if ($id) {
        return (int) $id;
    }

    $insert = $conn->prepare("
        INSERT INTO compras_parceladas
            (usuario_id, descricao, categoria, valor_parcela, parcela_inicial, parcela_total, data_primeira)
        VALUES
            (:usuario_id, :descricao, :categoria, :valor, :inicial, :total, :data)
    ");
    $insert->execute([
        ":usuario_id" => $usuarioId,
        ":descricao" => $nome,
        ":categoria" => $categoria,
        ":valor" => $valor,
        ":inicial" => $parcelaInicial,
        ":total" => $parcelaTotal,
        ":data" => $data,
    ]);

    return (int) $conn->lastInsertId();
}

function listarComprasParceladas(PDO $conn, $usuarioId)
{
    $stmt = $conn->prepare("
        SELECT
            id,
            descricao,
            categoria,
            valor_parcela,
            parcela_inicial,
            parcela_total,
            data_primeira
        FROM compras_parceladas
        WHERE usuario_id = :usuario_id
        ORDER BY data_primeira DESC, id DESC
    ");
    $stmt->execute([":usuario_id" => $usuarioId]);

    $hoje = date("Y-m-d");
    $lista = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $quitada = parcelaEstaQuitada(
            $item["parcela_inicial"],
            $item["parcela_total"],
            $item["data_primeira"],
            $hoje
        );
        $atual = calcularParcelaAtual(
            $item["parcela_inicial"],
            $item["parcela_total"],
            $item["data_primeira"],
            $hoje
        );
        $faltam = $quitada ? 0 : ((int) $item["parcela_total"] - $atual);

        $lista[] = [
            "id" => (int) $item["id"],
            "descricao" => $item["descricao"],
            "categoria" => $item["categoria"],
            "valor_parcela" => (float) $item["valor_parcela"],
            "parcela_atual" => $atual,
            "parcela_total" => (int) $item["parcela_total"],
            "faltam" => $faltam,
            "data_primeira" => $item["data_primeira"],
            "quitada" => $quitada,
        ];
    }

    return $lista;
}