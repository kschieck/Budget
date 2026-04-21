<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

switch($_SERVER['REQUEST_METHOD']) {
    case "POST":
        $_POST = json_decode(file_get_contents("php://input"), true);

        if (isset($_POST["goalId"])) {

            $goalId = intval($_POST["goalId"]);
            $amount = intval(floatval($_POST["amount"]) * 100); // convert to cents.
            if ($amount === 0) {
                echo json_encode(["success" => false, "message" => "Contribution amount cannot be zero."]);
                exit(1);
            }
            try {
                $result = addGoalTransaction($_SESSION["budget_auth"], $goalId, $amount);
                if (!$result) {
                    echo json_encode(["success" => false]);
                } else {
                    echo json_encode(["success" => true, "transaction" => $result["transaction"], "goalAmount" => $result["goalAmount"]]);
                }
            } catch (Error $e) {
                echo json_encode(["success" => false]);
            }

        } else {

            $amount = intval(floatval($_POST["amount"]) * 100); // convert to cents.
            if ($amount === 0) {
                echo json_encode(["success" => false, "message" => "Amount cannot be zero."]);
                exit(1);
            }

            $description = trim($_POST["description"]);
            if (strlen($description) == 0) {
                echo json_encode(["success" => false, "message" => "Description cannot be empty."]);
                exit(1);
            }

            try {
                $tx = addTransaction($_SESSION["budget_auth"], $amount, $description);
                echo json_encode(["success" => true, "transaction" => $tx]);
            } catch (Error $e) {
                echo json_encode(["success" => false]);
            }

        }

        break;
    case "DELETE":
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }
        $id = intval($data["id"]);

        try {
            $result = disableTransaction($_SESSION["budget_auth"], $id);
            echo json_encode(["success" => $result]);
        } catch (Error $e) {
            echo json_encode(["success" => false]);
        }
        break;
    case "PUT":
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }
    
        $id = intval($data["transactionId"]);
        $amount = intval(floatval($data["amount"]) * 100); // convert to cents.
        if ($amount === 0) {
            echo json_encode(["success" => false, "message" => "Amount cannot be zero."]);
            exit(1);
        }

        $description = trim($data["description"]);
        if (strlen($description) == 0) {
            echo json_encode(["success" => false, "message" => "Description cannot be empty."]);
            exit(1);
        }
    
        try {
            $tx = editTransaction($_SESSION["budget_auth"], $id, $amount, $description);
            if (!$tx) {
                echo json_encode(["success" => false]);
            } else {
                echo json_encode(["success" => true, "transaction" => $tx]);
            }
        } catch (Error $e) {
            echo json_encode(["success" => false]);
        }
        break;
    case "GET":
        // Server is 4 hours off local time, user specific code
        $dateOffset = "-4 hours";
        $monthAdjust = isset($_GET["past"]) ? intval($_GET["past"]) : 0;

        if ($monthAdjust > 0) {
            $dateOffset .= " -$monthAdjust months";
        }

        // On current-month loads, materialize any due recurring and upcoming transactions
        if ($monthAdjust === 0) {
            $currentMonth = date("Y-m", strtotime("-4 hours"));
            if (!hasProcessedRecurring($currentMonth)) {
                try {
                    processRecurringForMonth($currentMonth);
                } catch (Exception $e) {
                    error_log("Failed to process recurring transactions: " . $e->getMessage());
                }
            }
            try {
                processUpcomingForMonth($currentMonth);
            } catch (Exception $e) {
                error_log("Failed to process upcoming transactions: " . $e->getMessage());
            }
        }

        function loadTransactionsArray($dateOffset) {
            $transactions = [];
            $transactionsItr = loadTransactionsStartEndDate(
                date('Y-m-01 00:00:00', strtotime($dateOffset)),
                date('Y-m-t 23:59:59', strtotime($dateOffset)));
            while ($tx = $transactionsItr->fetch_assoc()) {
                $transactions[] = $tx;
            }
            return $transactions;
        }

        try {
            $transactions = loadTransactionsArray($dateOffset);
            echo json_encode(["success" => true, "transactions" => $transactions]);
        } catch (Error $e) {
            echo json_encode(["success" => false]);
        }
        break;
    default:
        echo json_encode(["success" => false]);
}

?>