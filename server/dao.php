<?php
session_start();
initDemoSession();

class ArrayDatabaseResult {
    private $index = 0;
    private $data = [];

    public function __construct($data) {
        $this->data = $data;
    }

    public function fetch_assoc() {
        if ($this->index >= count($this->data)) {
            return false;
        }
        $nextData = $this->data[$this->index];
        $this->index++;
        return $nextData;
    }
}

function initDemoSession() {
    if (isset($_SESSION["budget"])) {
        return;
    }

    $demoData = json_decode(file_get_contents(__DIR__ . "/demo_data.json"), true);

    // Find the latest transaction date_added
    $maxTs = 0;
    foreach ($demoData["transactions"] as $t) {
        $ts = strtotime($t["date_added"]);
        if ($ts > $maxTs) $maxTs = $ts;
    }

    // Calculate month difference between latest transaction month and current month
    $maxDate = new DateTime("@$maxTs");
    $now = new DateTime();
    $monthDiff = ($now->format('Y') - $maxDate->format('Y')) * 12 + ($now->format('n') - $maxDate->format('n'));

    // Shift all transaction dates forward so the latest month aligns with today's month
    $filteredTransactions = [];
    error_log("filtering transactions...");
    foreach ($demoData["transactions"] as &$t) {
        $dt = new DateTime($t["date_added"]);
        $dt->modify("+$monthDiff months");

        if ($dt < $now) {
            $t["date_added"] = $dt->format('Y-m-d H:i:s');
            $filteredTransactions[] = $t;
        }
    }
    unset($t);

    $_SESSION["budget"] = [
        "amount"       => $demoData["amount"],
        "goals"        => $demoData["goals"],
        "transactions" => $filteredTransactions,
        "recurring"    => $demoData["recurring"]
    ];
    
}

function getNewGoalId() {
    $ids = array_map(function($goal) { return $goal["id"]; }, $_SESSION["budget"]["goals"]);
    return empty($ids) ? 1 : max($ids) + 1;
}

function getNewTransactionId() {
    $ids = array_map(function($t) { return $t["id"]; }, $_SESSION["budget"]["transactions"]);
    return empty($ids) ? 1 : max($ids) + 1;
}

function loadAmount() {
    return $_SESSION["budget"]["amount"];
}

function loadTransactionsStartEndDate($startDateString, $endDateString) {
    return new ArrayDatabaseResult(array_values(array_filter(
        $_SESSION["budget"]["transactions"],
        function($transaction) use ($startDateString, $endDateString) {
            $transactionTime = strtotime($transaction["date_added"]);
            return $transactionTime > strtotime($startDateString) && $transactionTime < strtotime($endDateString);
        }
    )));
}

function loadGoalById($id) {
    return new ArrayDatabaseResult(array_values(array_filter(
        $_SESSION["budget"]["goals"],
        function($goal) use ($id) {
            return $goal["id"] == $id;
        }
    )));
}

function loadGoals($limit) {
    return new ArrayDatabaseResult($_SESSION["budget"]["goals"]);
}

function addTransaction($user, $amount, $description) {
    return addTransactions($user, [array("amount" => $amount, "description" => $description)]);
}

function addTransactions($user, $transactions) {
    $amountSum = 0;
    foreach ($transactions as $tx) {
        $amount      = $tx["amount"];
        $description = $tx["description"];
        $newId       = getNewTransactionId();
        $_SESSION["budget"]["transactions"][] = [
            "id"          => $newId,
            "date_added"  => date('Y-m-d H:i:s'),
            "amount"      => $amount,
            "description" => substr($description, 0, 64),
            "active"      => 1,
            "user"        => substr($user, 0, 32),
            "goal_id"     => null
        ];
        $amountSum += $amount;
    }
    $_SESSION["budget"]["amount"] -= $amountSum;
    return true;
}

function editTransaction($user, $transactionId, $amount, $description) {
    $txIndex = null;
    foreach ($_SESSION["budget"]["transactions"] as $i => $t) {
        if ($t["user"] === $user && $t["id"] == $transactionId && $t["active"] == 1) {
            $txIndex = $i;
            break;
        }
    }
    if ($txIndex === null) return false;

    $oldAmount   = $_SESSION["budget"]["transactions"][$txIndex]["amount"];
    $deltaAmount = $amount - $oldAmount;
    $goalId      = $_SESSION["budget"]["transactions"][$txIndex]["goal_id"];

    if ($goalId !== null) {
        $goalName = null;
        foreach ($_SESSION["budget"]["goals"] as $g) {
            if ($g["id"] == $goalId) {
                $goalName = $g["name"];
                break;
            }
        }
        if ($goalName === null) return false;
        $verb        = $amount > 0 ? "contribution" : "subtraction";
        $description = "Goal $verb: " . $goalName;
    }

    if ($deltaAmount !== 0) {
        $_SESSION["budget"]["amount"] -= $deltaAmount;
        if ($goalId !== null) {
            foreach ($_SESSION["budget"]["goals"] as &$g) {
                if ($g["id"] == $goalId) {
                    $g["amount"] += $deltaAmount;
                    break;
                }
            }
            unset($g);
        }
    }

    $_SESSION["budget"]["transactions"][$txIndex]["amount"]      = $amount;
    $_SESSION["budget"]["transactions"][$txIndex]["description"] = substr($description, 0, 64);

    return true;
}

function disableTransaction($user, $transactionId) {
    $txIndex = null;
    foreach ($_SESSION["budget"]["transactions"] as $i => $t) {
        if ($t["user"] === $user && $t["id"] == $transactionId && $t["active"] == 1) {
            $txIndex = $i;
            break;
        }
    }
    if ($txIndex === null) return false;

    $amount = $_SESSION["budget"]["transactions"][$txIndex]["amount"];
    $goalId = $_SESSION["budget"]["transactions"][$txIndex]["goal_id"];

    $_SESSION["budget"]["amount"] += $amount;
    $_SESSION["budget"]["transactions"][$txIndex]["active"] = 0;

    if ($goalId !== null) {
        foreach ($_SESSION["budget"]["goals"] as &$g) {
            if ($g["id"] == $goalId) {
                $g["amount"] -= $amount;
                break;
            }
        }
        unset($g);
    }

    return true;
}

function addGoal($user, $name, $total, $amount) {
    $newGoalId = getNewGoalId();
    $_SESSION["budget"]["goals"][] = [
        "id"         => $newGoalId,
        "date_added" => date('Y-m-d H:i:s'),
        "total"      => $total,
        "amount"     => $amount,
        "name"       => $name
    ];
    return $newGoalId;
}

function addGoalTransaction($user, $goalId, $amount) {
    $goalName = null;
    foreach ($_SESSION["budget"]["goals"] as $g) {
        if ($g["id"] == $goalId) {
            $goalName = $g["name"];
            break;
        }
    }
    if ($goalName === null) return false;

    $verb        = $amount > 0 ? "contribution" : "subtraction";
    $description = "Goal $verb: " . $goalName;

    $newId = getNewTransactionId();
    $_SESSION["budget"]["transactions"][] = [
        "id"          => $newId,
        "date_added"  => date('Y-m-d H:i:s'),
        "amount"      => $amount,
        "description" => substr($description, 0, 64),
        "active"      => 1,
        "user"        => substr($user, 0, 32),
        "goal_id"     => $goalId
    ];

    $_SESSION["budget"]["amount"] -= $amount;

    foreach ($_SESSION["budget"]["goals"] as &$g) {
        if ($g["id"] == $goalId) {
            $g["amount"] += $amount;
            break;
        }
    }
    unset($g);

    return true;
}

function setGoalTotal($user, $goalId, $total) {
    foreach ($_SESSION["budget"]["goals"] as &$g) {
        if ($g["id"] == $goalId) {
            $g["total"] = $total;
            return true;
        }
    }
    return false;
}

function disableGoal($user, $goalId) {
    $_SESSION["budget"]["goals"] = array_values(array_filter(
        $_SESSION["budget"]["goals"],
        function($g) use ($goalId) { return $g["id"] != $goalId; }
    ));
    return true;
}

function setUserToken($user, $token, $daysValid) {
    return true;
}

function getUserTokens($user) {
    return [];
}

function getMonthlyTotals($startDate) {
    $groups  = [];
    $startTs = strtotime($startDate);

    foreach ($_SESSION["budget"]["transactions"] as $t) {
        if (!$t["active"]) continue;
        if ($t["goal_id"] !== null) continue;

        // Apply UTC-4 offset to match the SQL DATE_SUB(date_added, INTERVAL 4 HOUR) pattern
        $ts = strtotime($t["date_added"]) - 4 * 3600;
        if ($ts <= $startTs) continue;

        $yearMonth = date('Ym', $ts);
        if (!isset($groups[$yearMonth])) {
            $groups[$yearMonth] = ["total" => 0, "spent" => 0, "earned" => 0, "year_month" => $yearMonth];
        }
        $groups[$yearMonth]["total"]  += $t["amount"];
        $groups[$yearMonth]["spent"]  += max(0, $t["amount"]);
        $groups[$yearMonth]["earned"] += min(0, $t["amount"]);
    }

    return new ArrayDatabaseResult(array_values($groups));
}

function getDailyTotals($startDate) {
    $groups        = [];
    $startTs       = strtotime($startDate);
    $startDateOnly = date('Y-m-d', $startTs);

    foreach ($_SESSION["budget"]["transactions"] as $t) {
        if (!$t["active"]) continue;

        // Apply UTC-4 offset to match the SQL DATE_SUB(date_added, INTERVAL 4 HOUR) pattern
        $ts = strtotime($t["date_added"]) - 4 * 3600;
        if ($ts <= $startTs) continue;

        $dateStr = date('Y-m-d', $ts);
        if (!isset($groups[$dateStr])) {
            $datediff            = (int)((strtotime($dateStr) - strtotime($startDateOnly)) / 86400);
            $groups[$dateStr]    = ["total" => 0, "date" => $dateStr, "datediff" => $datediff];
        }
        $groups[$dateStr]["total"] += $t["amount"];
    }

    return new ArrayDatabaseResult(array_values($groups));
}

function loadRecurringTransactions() {
    return new ArrayDatabaseResult($_SESSION["budget"]["recurring"]);
}

function addRecurring($user, $amount, $description, $startMonth, $endMonth) {
    $ids   = array_map(function($r) { return $r["id"]; }, $_SESSION["budget"]["recurring"]);
    $newId = empty($ids) ? 1 : max($ids) + 1;
    $_SESSION["budget"]["recurring"][] = [
        "id"          => $newId,
        "amount"      => $amount,
        "description" => substr($description, 0, 64),
        "start_month" => $startMonth,
        "end_month"   => $endMonth
    ];
    return $newId;
}

function editRecurring($user, $id, $amount, $description, $startMonth, $endMonth) {
    foreach ($_SESSION["budget"]["recurring"] as &$r) {
        if ($r["id"] == $id) {
            $r["amount"]      = $amount;
            $r["description"] = substr($description, 0, 64);
            $r["start_month"] = $startMonth;
            $r["end_month"]   = $endMonth;
            unset($r);
            return true;
        }
    }
    unset($r);
    return false;
}

function disableRecurring($user, $id) {
    $_SESSION["budget"]["recurring"] = array_values(array_filter(
        $_SESSION["budget"]["recurring"],
        function($r) use ($id) { return $r["id"] != $id; }
    ));
    return true;
}

function hasProcessedRecurring($month) {
    return true;
}

function processRecurringForMonth($month) {
    return true;
}

?>
