<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

switch ($_SERVER['REQUEST_METHOD']) {

    case "PUT":
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $id = intval($data["id"]);
        if ($id <= 0) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $amount = intval(floatval($data["amount"]) * 100);
        if ($amount === 0) {
            echo json_encode(["success" => false, "message" => "Amount cannot be zero."]);
            exit(1);
        }

        $description = trim($data["description"]);
        if (strlen($description) === 0) {
            echo json_encode(["success" => false, "message" => "Description cannot be empty."]);
            exit(1);
        }

        try {
            $success = paidUpcoming($_SESSION["budget_auth"], $id, $amount, $description);
            echo json_encode(["success" => (bool)$success]);
        } catch (\Exception $e) {
            error_log("paid-upcoming.php error: " . $e->getMessage());
            echo json_encode(["success" => false]);
        }
        break;

    default:
        echo json_encode(["success" => false]);
}

?>
