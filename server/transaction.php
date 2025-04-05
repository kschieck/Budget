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
                echo json_encode(["success" => false]);
                exit(1);
            }
            try {
                addGoalTransaction($_SESSION["budget_auth"], $goalId, $amount);
                echo json_encode(["success" => true]);
            } catch (Error $e) {
                echo json_encode(["success" => false]);
            }

        } else {

            $amount = intval(floatval($_POST["amount"]) * 100); // convert to cents.
            if ($amount === 0) {
                echo json_encode(["success" => false]);
                exit(1);
            }
        
            $description = trim($_POST["description"]);
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
            echo json_encode(["success" => false]);
            exit(1);
        }
    
        $description = trim($data["description"]);
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
        break;
    case "GET":
        // Server is 4 hours off local time, user specific code
        $dateOffset = "-4 hours";

        // If past param is specified, show previous months
        if (isset($_GET["past"]) && $_GET["past"]) {
            $monthAdjust = intval($_GET["past"]);
            if ($monthAdjust > 0) {
                $dateOffset .= " -$monthAdjust months";
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