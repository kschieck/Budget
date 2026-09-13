<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case "GET":
        $result = loadCategories();
        $categories = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        echo json_encode(["success" => true, "categories" => $categories]);
        break;

    case "POST":
        $_POST = json_decode(file_get_contents("php://input"), true);
        if ($_POST === null) {
            echo json_encode(["success" => false]);
            exit(1);
        }

        $name = trim($_POST["name"]);
        if (strlen($name) === 0) {
            echo json_encode(["success" => false, "message" => "Name cannot be empty."]);
            exit(1);
        }

        $color = isset($_POST["color"]) ? trim($_POST["color"]) : "";
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            echo json_encode(["success" => false, "message" => "Invalid color."]);
            exit(1);
        }

        $category = addCategory($name, $color);
        if (!$category) {
            echo json_encode(["success" => false]);
        } else {
            echo json_encode(["success" => true, "category" => $category]);
        }
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

        $name = trim($data["name"]);
        if (strlen($name) === 0) {
            echo json_encode(["success" => false, "message" => "Name cannot be empty."]);
            exit(1);
        }

        $color = isset($data["color"]) ? trim($data["color"]) : "";
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            echo json_encode(["success" => false, "message" => "Invalid color."]);
            exit(1);
        }

        $category = editCategory($id, $name, $color);
        if (!$category) {
            echo json_encode(["success" => false]);
        } else {
            echo json_encode(["success" => true, "category" => $category]);
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

        $result = disableCategory($id);
        echo json_encode(["success" => (bool)$result]);
        break;

    default:
        echo json_encode(["success" => false]);
}

?>
