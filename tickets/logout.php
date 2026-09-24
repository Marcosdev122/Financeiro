<?php

require_once __DIR__ . "/../config/tickets_auth.php";
tickets_logout();
header("Location: /Financeiro/tickets/login.php");
exit;
