<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case "GET":
        $result = loadUpcomingTransactions();
        $upcoming = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $upcoming[] = $row;
            }
        }
        echo json_encode(["success" => true, "upcoming" => $upcoming]);
        break;

    case "POST":
        $_POST = json_decode(file_get_contents("php://input"), true);
        if ($_POST === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $amount = intval(floatval($_POST["amount"]) * 100);
        if ($amount === 0) {
            echo json_encode(["success" => false, "message" => "Amount cannot be zero."]);
            exit(1);
        }

        $description = trim($_POST["description"]);
        if (strlen($description) === 0) {
            echo json_encode(["success" => false, "message" => "Description cannot be empty."]);
            exit(1);
        }

        $targetMonthRaw = isset($_POST["target_month"]) ? trim($_POST["target_month"]) : "";
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $targetMonthRaw)) {
            echo json_encode(["success" => false, "message" => "Invalid target month (use YYYY-MM)."]);
            exit(1);
        }
        $currentMonth = date("Y-m", strtotime("-4 hours"));
        if ($targetMonthRaw < $currentMonth) {
            echo json_encode(["success" => false, "message" => "Target month cannot be in the past."]);
            exit(1);
        }
        $targetMonth = $targetMonthRaw;

        $upcoming = addUpcoming($_SESSION["budget_auth"], $amount, $description, $targetMonth);
        if (!$upcoming) {
            echo json_encode(["success" => false]);
            break;
        }

        echo json_encode(["success" => true, "upcoming" => $upcoming]);
        break;

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

        $targetMonthRaw = isset($data["target_month"]) ? trim($data["target_month"]) : "";
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $targetMonthRaw)) {
            echo json_encode(["success" => false, "message" => "Invalid target month (use YYYY-MM)."]);
            exit(1);
        }
        $currentMonth = date("Y-m", strtotime("-4 hours"));
        if ($targetMonthRaw < $currentMonth) {
            echo json_encode(["success" => false, "message" => "Target month cannot be in the past."]);
            exit(1);
        }
        $targetMonth = $targetMonthRaw;

        $upcoming = editUpcoming($_SESSION["budget_auth"], $id, $amount, $description, $targetMonth);
        if (!$upcoming) {
            echo json_encode(["success" => false]);
        } else {
            echo json_encode(["success" => true, "upcoming" => $upcoming]);
        }
        break;

    case "DELETE":
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

        $result = disableUpcoming($_SESSION["budget_auth"], $id);
        echo json_encode(["success" => (bool)$result]);
        break;

    default:
        echo json_encode(["success" => false]);
}

?>
