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

$id = $_POST["transactionId"];

$amount = intval(floatval($_POST["amount"]) * 100); // convert to cents.
$description = trim($_POST["description"]);

if ($amount === 0) {
    echo json_encode(["success" => false]);
    exit(1);
}

if (strlen($description) == 0) {
    echo json_encode(["success" => false]);
    exit(1);
}

try {
    $result = editTransaction($_SESSION["budget_auth"], $id, $amount, $description);
    echo json_encode(["success" => $result]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>