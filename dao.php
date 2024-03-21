<?php

require_once(__DIR__."/config.php");

function query($sql, $types, $params) {
    global $servername;
    global $username;
    global $password;
    global $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        return false;
    }
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute() === false) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        return false;
    }
    
    $conn->close();

    return true;
}

function insert($sql, $types, $params) {
    global $servername;
    global $username;
    global $password;
    global $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        return 0;
    }
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute() === false) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        return 0;
    }

    $insertId = $conn->insert_id;
    
    $conn->close();

    return $insertId;
}

function select($sql, $types, $params) {
    global $servername;
    global $username;
    global $password;
    global $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        return null;
    }
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute() === false) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        return null;
    }
    $result = $stmt->get_result();
    
    $conn->close();

    return $result;
}

function getConnection() {
    global $servername;
    global $username;
    global $password;
    global $dbname;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function prepStatement($conn, $sql, $types, $params) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error: " . $sql . "\n" . $conn->error);
        $conn->close();
        die("Connection failed: " . $conn->connect_error);
    }
    $stmt->bind_param($types, ...$params);
    return $stmt;
}

function loadAmount() {
    $sql = "SELECT amount FROM amount LIMIT 1";
    $result = select($sql, "", []);
    if($row = $result->fetch_assoc()) {
        return $row["amount"];
    }
    return 0;
}

function loadTransactionsDate($dateString) {
    $sql = "SELECT id, DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, amount, `description`, `active`
            FROM `transactions`
            WHERE DATE_SUB(date_added, INTERVAL 4 HOUR) > ? ORDER BY id DESC";
    return select($sql, "s", [$dateString]);
}

function loadTransactionsStartEndDate($startDateString, $endDateString) {
    $sql = "SELECT id, DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, amount, `description`, `active`
            FROM `transactions`
            WHERE DATE_SUB(date_added, INTERVAL 4 HOUR) > ? AND DATE_SUB(date_added, INTERVAL 4 HOUR) < ? ORDER BY id DESC";
    return select($sql, "ss", [$startDateString, $endDateString]);
}

function loadGoals($limit) {
    $limit = intval($limit);
    if ($limit <= 0) {
        return [];
    }
    $sql = "SELECT id, DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, total, amount, `name` FROM `goals` WHERE `active` = 1 ORDER BY id DESC LIMIT $limit";
    return select($sql, "", []);
}

function addTransaction($user, $amount, $description) {

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        $createTxStmt = prepStatement($conn, 
            "INSERT INTO `transactions` (user, amount, `description`) VALUES (?,?,?)", 
            "sis", [substr($user, 0, 32), $amount, substr($description, 0, 64)]);

        $updateAmountStmt = prepStatement($conn,
            "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$amount]);

        $createTxStmt->execute();
        $updateAmountStmt->execute();
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to add transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return true;
}

function disableTransaction($user, $transactionId) {
    // Load transaction, get amount and reverse it while setting inactive

    $loadResult = select("SELECT `amount` FROM `transactions` WHERE `user` = ? AND `id` = ? AND `active` = 1", "si", [$user, $transactionId]);
    if (!($transaction = $loadResult->fetch_assoc())) {
        return false;
    }

    $amount = $transaction["amount"];

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        $updateAmountStmt = prepStatement($conn,
            "UPDATE amount SET amount = amount + ? LIMIT 1", "i", [$amount]);

        $updateTxStmt = prepStatement($conn, 
            "UPDATE `transactions` SET `active` = 0 WHERE id = ?", "i", [$transactionId]);

        $updateAmountStmt->execute();
        $updateTxStmt->execute();
        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to add transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return false;
}

function addGoal($user, $name, $total, $amount) {
    return insert("INSERT INTO `goals` (user, `name`, `total`, `amount`) VALUES (?,?,?,?)", "ssii", [substr($user, 0, 32), substr($name, 0, 64), $total, $amount]);
}

function addGoalTransaction($user, $goalId, $amount) {
    $goalResult = select("SELECT `name` FROM `goals` WHERE id = ? LIMIT 1", "i", [$goalId]);
    $goalRow = $goalResult->fetch_assoc();
    if (!$goalRow) {
        return false;
    }
    $verb = $amount > 0? "contribution" : "subtraction";
    $description = "Goal $verb: " . $goalRow["name"];

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        $createTxStmt = prepStatement($conn, 
            "INSERT INTO `transactions` (user, amount, `description`) VALUES (?,?,?)", 
            "sis", [substr($user, 0, 32), $amount, substr($description, 0, 64)]);

        $updateAmountStmt = prepStatement($conn,
            "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$amount]);

        $updateGoalStmt = prepStatement($conn,
            "UPDATE `goals` SET amount = amount + ? WHERE id = ?", "ii", [$amount, $goalId]);

        $createTxStmt->execute();
        $updateAmountStmt->execute();
        $updateGoalStmt->execute();
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to add transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return true;
}

function setGoalTotal($user, $goalId, $total) {
    return query("UPDATE `goals` SET `total` = ? WHERE id = ? LIMIT 1", "ii", [$total, $goalId]);
}

function disableGoal($user, $goalId) {
    return query("UPDATE `goals` SET `active` = 0 WHERE id = ? LIMIT 1", "i", [$goalId]);
}

function setUserToken($user, $token, $daysValid) {
    $sql = "INSERT INTO user_tokens (`user`, `token`, `expires_at`) VALUES (?,?,DATE_ADD(NOW(), INTERVAL ? DAY))";
    return query($sql, "ssi", [$user, $token, $daysValid]);
}

function getUserTokens($user) {
    $sql = "SELECT `token` FROM `user_tokens` WHERE `user` = ? AND `expires_at` > NOW()";
    $result = select($sql, "s", [$user]);
    $tokens = [];
    while($row = $result->fetch_assoc()) {
        $tokens[] = $row["token"];
    }
    return $tokens;
}

function getMonthlyTotals($startDate) {

    // Get all active transactions that are not described as goal transactions (a bit of a hack)
    $sql = "SELECT SUM(amount) as total, SUM(GREATEST(0, amount)) as spent, SUM(LEAST(0, amount)) as earned,
            EXTRACT(YEAR_MONTH FROM (DATE_SUB(date_added, INTERVAL 4 HOUR))) as `year_month`
            FROM `transactions`
            WHERE DATE_SUB(date_added, INTERVAL 4 HOUR) > ? AND `active` = 1
            AND `description` NOT LIKE \"Goal Contribution: %\"
            AND `description` NOT LIKE \"Goal Subtraction: %\"
            GROUP BY `year_month`";

    return select($sql, "s", [$startDate]);
}

function getDailyTotals($startDate) {

    $sql = "SELECT SUM(amount) as total,
            DATE(DATE_SUB(date_added, INTERVAL 4 HOUR)) as `date`,
            DATEDIFF(DATE(DATE_SUB(date_added, INTERVAL 4 HOUR)), DATE(?)) as `datediff`
            FROM `transactions`
            WHERE DATE_SUB(date_added, INTERVAL 4 HOUR) > ? AND `active` = 1
            GROUP BY `date`";

    return select($sql, "ss", [$startDate, $startDate]);
}

?>