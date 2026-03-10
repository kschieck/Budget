<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case "GET":
        $result = loadRecurringTransactions($_SESSION["budget_auth"]);
        $recurring = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recurring[] = $row;
            }
        }
        echo json_encode(["success" => true, "recurring" => $recurring]);
        break;

    case "POST":
        $_POST = json_decode(file_get_contents("php://input"), true);
        if ($_POST === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $amount = intval(floatval($_POST["amount"]) * 100);
        if ($amount === 0) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $description = trim($_POST["description"]);
        if (strlen($description) === 0) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $endMonthRaw = isset($_POST["end_month"]) ? trim($_POST["end_month"]) : "";
        if (strlen($endMonthRaw) > 0) {
            if (!preg_match('/^\d{4}-\d{2}$/', $endMonthRaw)) {
                echo json_encode(["success" => false]);
                exit(1);
            }
            $endMonth = $endMonthRaw;
        } else {
            $endMonth = null;
        }

        // start_month is always next calendar month (UTC-4)
        $startMonth = date("Y-m", strtotime("-4 hours +1 month"));

        $id = addRecurring($_SESSION["budget_auth"], $amount, $description, $startMonth, $endMonth);
        echo json_encode(["success" => $id > 0]);
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
            echo json_encode(["success" => false]);
            exit(1);
        }

        $description = trim($data["description"]);
        if (strlen($description) === 0) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $endMonthRaw = isset($data["end_month"]) ? trim($data["end_month"]) : "";
        if (strlen($endMonthRaw) > 0) {
            if (!preg_match('/^\d{4}-\d{2}$/', $endMonthRaw)) {
                echo json_encode(["success" => false]);
                exit(1);
            }
            $endMonth = $endMonthRaw;
        } else {
            $endMonth = null;
        }

        $result = editRecurring($_SESSION["budget_auth"], $id, $amount, $description, $endMonth);
        echo json_encode(["success" => (bool)$result]);
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

        $result = disableRecurring($_SESSION["budget_auth"], $id);
        echo json_encode(["success" => (bool)$result]);
        break;

    default:
        echo json_encode(["success" => false]);
}

?>
