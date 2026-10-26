<?php 

$conn = new PDO(
    "mysql:host=localhost;dbname=gestao_financeira",
    "root",
    "Fg25107418#"
);

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

