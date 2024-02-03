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

$amount = intval(floatval($_POST["amount"]) * 100); // convert to cents.
$description = $_POST["description"];

if ($amount === 0) {
    echo json_encode(["success" => false]);
    exit(1);
}

if (strlen($description) == 0) {
    echo json_encode(["success" => false]);
    exit(1);
}

try {
    addTransaction($_SESSION["budget_auth"], $amount, $description);
    echo json_encode(["success" => true]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>