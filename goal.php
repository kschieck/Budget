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

$name = $_POST["name"];
$total = intval(floatval($_POST["total"]) * 100); // convert to cents.

if ($total === 0) {
    echo json_encode(["success" => false]);
    exit(1);
}

try {
    addGoal($_SESSION["budget_auth"], $name, $total, 0);
    echo json_encode(["success" => true]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>