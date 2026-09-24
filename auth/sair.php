<?php

require_once __DIR__ . "/../config/auth.php";

auth_logout();
header("Location: /Financeiro/auth/login.php");
exit;
