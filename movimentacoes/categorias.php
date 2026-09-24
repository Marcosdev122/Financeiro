<?php

header("Content-Type: application/json");

$categorias = require __DIR__ . "/../config/categorias.php";

echo json_encode([
    "status" => "sucesso",
    "dados" => $categorias
]);
