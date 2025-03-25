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

function positive($val)
{
    return $val > 0;
}

$transactionIds = isset($_POST["transactionIds"])? $_POST["transactionIds"] : [];
$transactionIds = array_map("intval", $transactionIds);
$transactionIds = array_filter($transactionIds, function ($val) { return $val > 0; });
$transactionIds = array_unique($transactionIds, SORT_NUMERIC);

if (array_keys($transactionIds) !== range(0, count($transactionIds) - 1))
{
    echo json_encode(["success" => false]);
    exit(1);
}

if (!is_array($transactionIds))
{
    echo json_encode(["success" => false]);
    exit(1);
}

if (!count($transactionIds))
{
    echo json_encode(["success" => false]);
    exit(1);
}

try {
    $transactionsToDuplicate = loadTransactionsList($transactionIds);

    if (count($transactionsToDuplicate) != count($transactionIds))
    {
        echo json_encode(["success" => false]);
        exit(1);
    }

    $result = addTransactions($_SESSION["budget_auth"], $transactionsToDuplicate);
    echo json_encode(["success" => $result]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}

?>