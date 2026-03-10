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

        $startMonthRaw = isset($_POST["start_month"]) ? trim($_POST["start_month"]) : "";
        if (!preg_match('/^\d{4}-\d{2}$/', $startMonthRaw)) {
            echo json_encode(["success" => false]);
            exit(1);
        }
        $currentMonth = date("Y-m", strtotime("-4 hours"));
        if ($startMonthRaw < $currentMonth) {
            echo json_encode(["success" => false]);
            exit(1);
        }
        $startMonth = $startMonthRaw;

        $endMonthRaw = isset($_POST["end_month"]) ? trim($_POST["end_month"]) : "";
        if (strlen($endMonthRaw) > 0) {
            if (!preg_match('/^\d{4}-\d{2}$/', $endMonthRaw) || $endMonthRaw <= $startMonth) {
                echo json_encode(["success" => false]);
                exit(1);
            }
            $endMonth = $endMonthRaw;
        } else {
            $endMonth = null;
        }

        $id = addRecurring($_SESSION["budget_auth"], $amount, $description, $startMonth, $endMonth);
        if (!($id > 0)) {
            echo json_encode(["success" => false]);
            break;
        }

        // If start_month is the current month and that month has already been processed,
        // create the transaction immediately so it appears without waiting for next month's load.
        if ($startMonth === $currentMonth && hasProcessedRecurring($currentMonth)) {
            $txDescription = substr("monthly: " . $description, 0, 64);
            try {
                addTransaction($_SESSION["budget_auth"], $amount, $txDescription);
            } catch (Exception $e) {
                error_log("Failed to create immediate recurring transaction: " . $e->getMessage());
            }
        }

        echo json_encode(["success" => true]);
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

        $startMonthRaw = isset($data["start_month"]) ? trim($data["start_month"]) : "";
        if (!preg_match('/^\d{4}-\d{2}$/', $startMonthRaw)) {
            echo json_encode(["success" => false]);
            exit(1);
        }
        $startMonth = $startMonthRaw;

        $endMonthRaw = isset($data["end_month"]) ? trim($data["end_month"]) : "";
        if (strlen($endMonthRaw) > 0) {
            if (!preg_match('/^\d{4}-\d{2}$/', $endMonthRaw) || $endMonthRaw <= $startMonth) {
                echo json_encode(["success" => false]);
                exit(1);
            }
            $endMonth = $endMonthRaw;
        } else {
            $endMonth = null;
        }

        $result = editRecurring($_SESSION["budget_auth"], $id, $amount, $description, $startMonth, $endMonth);
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
