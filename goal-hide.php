<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    echo json_encode(["success" => false]);
    exit(1);
}

$_POST = json_decode(file_get_contents("php://input"), true);

$id = $_POST["id"];

try {
    $success = disableGoal($_SESSION["budget_auth"], $id);
    echo json_encode(["success" => $success]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>