<?php

require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    echo json_encode(["success" => false]);
    exit(1);
}

try {
    // Load monthly totals for the last 4 full months (start of month 3 months ago)
    $startDate = date('Y-m-t', strtotime('-4 months'));
    $result = getMonthlyTotals($startDate);
    $months = [];
    while ($row = $result->fetch_assoc()) {
        $months[] = [
            "year_month" => intval($row["year_month"]),
            "spent"      => intval($row["spent"]),   // cents, positive (expenses)
            "earned"     => intval($row["earned"]),  // cents, negative (income)
        ];
    }
    echo json_encode(["success" => true, "months" => $months]);
} catch (\Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false]);
}

?>
