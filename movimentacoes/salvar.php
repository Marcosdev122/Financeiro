<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../includes/movimentacao_regras.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$categoriasPorTipo = require __DIR__ . "/../config/categorias.php";

$id = $_POST['id'] ?? null;
$categoria = trim($_POST['categoria'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$tipo = $_POST['tipo'] ?? '';
$valor = $_POST['valor'] ?? '';
$data = $_POST['data'] ?? '';

if (strlen($descricao) > 255) {
    $descricao = substr($descricao, 0, 255);
}

if ($tipo === '' || $valor === '' || $data === '') {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Preencha todos os campos."
    ]);
    exit;
}

if (!in_array($tipo, ['Receita', 'Despesa'], true)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Tipo inválido."
    ]);
    exit;
}

if (!is_numeric($valor) || $valor <= 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Informe um valor válido."
    ]);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Data inválida."
    ]);
    exit;
}

[$ano, $mes, $dia] = array_map("intval", explode("-", $data));

if (!checkdate($mes, $dia, $ano)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Data inválida."
    ]);
    exit;
}

$hoje = date("Y-m-d");

if ($data > $hoje) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "A data não pode ser futura. Use hoje ou uma data do passado."
    ]);
    exit;
}

$categoriasValidas = $categoriasPorTipo[$tipo] ?? [];

if ($categoria === '' || !in_array($categoria, $categoriasValidas, true)) {
    $categoria = 'Outros';
}

if (movimentacaoIgnorada($categoria, $descricao)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Este lançamento não é registrado (ex.: pagamento recebido)."
    ]);
    exit;
}

if ($id) {
    $sql = "
        UPDATE movimentacoes
        SET
            categoria = :categoria,
            descricao = :descricao,
            tipo = :tipo,
            valor = :valor,
            data = :data,
            atualizado_em = NOW()
        WHERE id = :id
          AND usuario_id = :usuario_id
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":categoria", $categoria);
    $stmt->bindValue(":descricao", $descricao !== '' ? $descricao : null);
    $stmt->bindValue(":tipo", $tipo);
    $stmt->bindValue(":valor", $valor);
    $stmt->bindValue(":data", $data);
    $stmt->bindValue(":id", $id);
    $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Movimentação não encontrada."
        ]);
        exit;
    }

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Movimentação atualizada com sucesso."
    ]);
} else {
    $sql = "
        INSERT INTO movimentacoes (usuario_id, categoria, descricao, tipo, valor, data, criado_em)
        VALUES (:usuario_id, :categoria, :descricao, :tipo, :valor, :data, NOW())
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":usuario_id", $usuarioId, PDO::PARAM_INT);
    $stmt->bindValue(":categoria", $categoria);
    $stmt->bindValue(":descricao", $descricao !== '' ? $descricao : null);
    $stmt->bindValue(":tipo", $tipo);
    $stmt->bindValue(":valor", $valor);
    $stmt->bindValue(":data", $data);
    $stmt->execute();

    $tabela = $tipo === "Receita" ? "receitas" : "despesas";
    $sqlEspelho = "
        INSERT INTO {$tabela} (usuario_id, categoria, valor, data)
        VALUES (:usuario_id, :categoria, :valor, :data)
    ";
    $stmtEspelho = $conn->prepare($sqlEspelho);
    $stmtEspelho->execute([
        ":usuario_id" => $usuarioId,
        ":categoria" => $categoria,
        ":valor" => $valor,
        ":data" => $data
    ]);

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Movimentação cadastrada com sucesso."
    ]);
}
