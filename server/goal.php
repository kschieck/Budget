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
        $name = trim($_POST["name"]);
        if (strlen($name) == 0) {
            echo json_encode(["success" => false]);
            exit(1);
        }

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
        break;
    case "DELETE":
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $id = intval($data["id"]);
        try {
            $success = disableGoal($_SESSION["budget_auth"], $id);
            echo json_encode(["success" => $success]);
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

        $id = intval($data["goalId"]);
        $amount = intval(floatval($data["amount"]) * 100); // convert to cents.
        
        try {
            $success = setGoalTotal($_SESSION["budget_auth"], $id, $amount);
            echo json_encode(["success" => $success]);
        } catch (Error $e) {
            echo json_encode(["success" => false]);
        }
        break;
    case "GET":
        function loadGoalsArray() {
            $goalsResult = loadGoals(100);
            $goals = [];
            while ($goal = $goalsResult->fetch_assoc()) {
                $goals[] = $goal;
            }
            return $goals;
        }
        
        try {
            $goals = loadGoalsArray();
            echo json_encode(["success" => true, "goals" => $goals]);
        } catch (Error $e) {
            echo json_encode(["success" => false]);
        }
        break;
    default:
        echo json_encode(["success" => false]);
}

?>