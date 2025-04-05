<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    echo json_encode(["success" => false]);
    exit(1);
}

try {
    echo json_encode(["success" => true, "amount" => loadAmount()]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>