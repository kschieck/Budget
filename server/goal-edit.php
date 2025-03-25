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

$id = $_POST["goalId"];
$amount = intval(floatval($_POST["amount"]) * 100); // convert to cents.

try {
    $success = setGoalTotal($_SESSION["budget_auth"], $id, $amount);
    echo json_encode(["success" => $success]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>