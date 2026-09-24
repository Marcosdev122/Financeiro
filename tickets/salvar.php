<?php

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../config/auth.php";

header("Content-Type: application/json; charset=utf-8");

$usuarioId = auth_exigir_api();
$usuario = auth_usuario();

$motivo = trim($_POST["motivo"] ?? "");
$detalhe = trim($_POST["detalhe"] ?? "");
$pagina = trim($_POST["pagina"] ?? "");

$usuarioNome = trim((string) ($usuario["nome"] ?? ""));
$usuarioEmail = trim((string) ($usuario["email"] ?? ""));
$usuarioTelefone = trim((string) ($usuario["telefone"] ?? ""));

if ($motivo === "") {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Selecione um motivo."
    ]);
    exit;
}

if (strlen($motivo) > 120) {
    $motivo = substr($motivo, 0, 120);
}

if (strlen($detalhe) > 300) {
    $detalhe = substr($detalhe, 0, 300);
}

if (strlen($pagina) > 500) {
    $pagina = substr($pagina, 0, 500);
}

if (strlen($usuarioNome) > 120) {
    $usuarioNome = substr($usuarioNome, 0, 120);
}

if (strlen($usuarioEmail) > 180) {
    $usuarioEmail = substr($usuarioEmail, 0, 180);
}

if (strlen($usuarioTelefone) > 20) {
    $usuarioTelefone = substr($usuarioTelefone, 0, 20);
}

if ($usuarioTelefone === "") {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Cadastre um WhatsApp na sua conta para abrir ticket."
    ]);
    exit;
}
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            usuario_nome VARCHAR(120) NULL,
            usuario_email VARCHAR(180) NULL,
            protocolo VARCHAR(20) NOT NULL,
            motivo VARCHAR(120) NOT NULL,
            detalhe VARCHAR(300) NULL,
            pagina VARCHAR(500) NULL,
            status ENUM('Aberto', 'Em atendimento', 'Resolvido') NOT NULL DEFAULT 'Aberto',
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL DEFAULT NULL,
            UNIQUE KEY uk_tickets_protocolo (protocolo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Garante colunas em tabelas antigas
    $cols = $conn->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array("usuario_id", $cols, true)) {
        $conn->exec("ALTER TABLE tickets ADD COLUMN usuario_id INT NULL AFTER id");
    }
    if (!in_array("usuario_nome", $cols, true)) {
        $conn->exec("ALTER TABLE tickets ADD COLUMN usuario_nome VARCHAR(120) NULL AFTER usuario_id");
    }
    if (!in_array("usuario_email", $cols, true)) {
        $conn->exec("ALTER TABLE tickets ADD COLUMN usuario_email VARCHAR(180) NULL AFTER usuario_nome");
    }
    if (!in_array("usuario_telefone", $cols, true)) {
        $conn->exec("ALTER TABLE tickets ADD COLUMN usuario_telefone VARCHAR(20) NULL AFTER usuario_email");
    }
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO tickets (
            protocolo,
            usuario_id,
            usuario_nome,
            usuario_email,
            usuario_telefone,
            motivo,
            detalhe,
            pagina,
            status,
            criado_em
        )
        VALUES (
            :protocolo,
            :usuario_id,
            :usuario_nome,
            :usuario_email,
            :usuario_telefone,
            :motivo,
            :detalhe,
            :pagina,
            'Aberto',
            NOW()
        )
    ");

    $protocoloTemp = "TMP-" . uniqid();
    $stmt->execute([
        ":protocolo" => $protocoloTemp,
        ":usuario_id" => $usuarioId,
        ":usuario_nome" => $usuarioNome !== "" ? $usuarioNome : null,
        ":usuario_email" => $usuarioEmail !== "" ? $usuarioEmail : null,
        ":usuario_telefone" => $usuarioTelefone !== "" ? $usuarioTelefone : null,
        ":motivo" => $motivo,
        ":detalhe" => $detalhe !== "" ? $detalhe : null,
        ":pagina" => $pagina !== "" ? $pagina : null
    ]);

    $id = (int) $conn->lastInsertId();
    $protocolo = "TK-" . str_pad((string) $id, 5, "0", STR_PAD_LEFT);

    $upd = $conn->prepare("UPDATE tickets SET protocolo = :protocolo WHERE id = :id");
    $upd->execute([
        ":protocolo" => $protocolo,
        ":id" => $id
    ]);

    $conn->commit();

    echo json_encode([
        "status" => "sucesso",
        "mensagem" => "Ticket criado com sucesso.",
        "dados" => [
            "id" => $id,
            "protocolo" => $protocolo,
            "motivo" => $motivo,
            "detalhe" => $detalhe,
            "status" => "Aberto",
            "usuario_nome" => $usuarioNome,
            "usuario_email" => $usuarioEmail,
            "usuario_telefone" => $usuarioTelefone
        ]
    ]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível criar o ticket."
    ]);
}
