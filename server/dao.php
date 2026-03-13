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

function loadTransactionsStartEndDate($startDateString, $endDateString) {
    $sql = "SELECT id, DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, amount, `description`, `active`, `user`, `goal_id`
            FROM `transactions`
            WHERE DATE_SUB(date_added, INTERVAL 4 HOUR) > ? AND DATE_SUB(date_added, INTERVAL 4 HOUR) < ? ORDER BY id DESC";
    return select($sql, "ss", [$startDateString, $endDateString]);
}

function loadGoalById($id) {
    $sql = "SELECT `id`, `amount` FROM `goals` WHERE `id` = ? AND `active` = 1 LIMIT 1";
    return select($sql, "i", [$id]);
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
    $dateAdded = date('Y-m-d H:i:s', strtotime('-4 hours'));
    $user = substr($user, 0, 32);
    $description = substr($description, 0, 64);

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        $createTxStmt = prepStatement($conn,
            "INSERT INTO `transactions` (user, amount, `description`) VALUES (?,?,?)",
            "sis", [$user, $amount, $description]);

        if (!$createTxStmt->execute()) {
            error_log("Failed to execute transaction");
            error_log("MySQL Execution Error: " . $createTxStmt->error);
            throw new mysqli_sql_exception("Execution failed for transaction.");
        }

        $transactionId = $conn->insert_id;

        $updateAmountStmt = prepStatement($conn,
            "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$amount]);

        if (!$updateAmountStmt->execute()) {
            error_log("Failed to execute update statement");
            error_log("MySQL Execution Error: " . $updateAmountStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to add transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return [
        'id' => $transactionId,
        'user' => $user,
        'amount' => $amount,
        'description' => $description,
        'date_added' => $dateAdded,
        'goal_id' => null,
        'active' => 1,
    ];
}

function editTransaction($user, $transactionId, $amount, $description) {
    // Load transaction, find amount delta to adjust amount table and update the amount

    $loadResult = select("SELECT `amount`, `goal_id` FROM `transactions` WHERE `user` = ? AND `id` = ? AND `active` = 1", "si", [$user, $transactionId]);
    if (!($transaction = $loadResult->fetch_assoc())) {
        return false;
    }

    $oldAmount = $transaction["amount"];
    $deltaAmount = $amount - $oldAmount;
    $goalId = $transaction["goal_id"];

    if ($goalId !== null) {
        $goalResult = select("SELECT `name` FROM `goals` WHERE id = ? LIMIT 1", "i", [$goalId]);
        $goalRow = $goalResult->fetch_assoc();
        if (!$goalRow) {
            return false;
        }
        $verb = $amount > 0 ? "contribution" : "subtraction";
        $description = "Goal $verb: " . $goalRow["name"];
    }
    $description = substr($description, 0, 64);

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        if ($deltaAmount !== 0) {
            $updateAmountStmt = prepStatement($conn,
                "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$deltaAmount]);

            if (!$updateAmountStmt->execute()) {
                error_log("Failed to update amount");
                error_log("MySQL Execution Error: " . $updateAmountStmt->error);
                throw new mysqli_sql_exception("Execution failed for transaction.");
            }

            if ($goalId !== null) {
                $updateGoalStmt = prepStatement($conn,
                    "UPDATE `goals` SET amount = amount + ? WHERE id = ? AND `active` = 1", "ii", [$deltaAmount, $goalId]);
                if (!$updateGoalStmt->execute()) {
                    error_log("Failed to execute update goal");
                    error_log("MySQL Execution Error: " . $updateGoalStmt->error);
                    throw new mysqli_sql_exception("Update statement execution failed.");
                } else if ($updateGoalStmt->affected_rows === 0) {
                    // If the goal cannot be updated, the transaction cannot be edited or deleted
                    $conn->rollback();
                    $conn->close();
                    return false;
                }
            }
        }

        $updateTxStmt = prepStatement($conn,
            "UPDATE `transactions` SET `amount` = ?, `description` = ? WHERE id = ?", "isi", [$amount, $description, $transactionId]);

        if (!$updateTxStmt->execute()) {
            error_log("Failed to execute update transaction");
            error_log("MySQL Execution Error: " . $updateTxStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        }
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to edit transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return [
        'id' => $transactionId,
        'amount' => $amount,
        'description' => $description,
        'goal_id' => $goalId,
    ];
}

function disableTransaction($user, $transactionId) {
    // Load transaction, get amount and reverse it while setting inactive

    $loadResult = select("SELECT `amount`, `goal_id` FROM `transactions` WHERE `user` = ? AND `id` = ? AND `active` = 1", "si", [$user, $transactionId]);
    if (!($transaction = $loadResult->fetch_assoc())) {
        return false;
    }

    $amount = $transaction["amount"];
    $goalId = $transaction["goal_id"];

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        $updateAmountStmt = prepStatement($conn,
            "UPDATE amount SET amount = amount + ? LIMIT 1", "i", [$amount]);

        $updateTxStmt = prepStatement($conn,
            "UPDATE `transactions` SET `active` = 0 WHERE id = ?", "i", [$transactionId]);

        if (!$updateAmountStmt->execute()) {
            error_log("Failed to execute update transaction");
            error_log("MySQL Execution Error: " . $updateAmountStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        }
        if (!$updateTxStmt->execute()) {
            error_log("Failed to execute update transaction");
            error_log("MySQL Execution Error: " . $updateTxStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        }

        if ($goalId !== null) {
            $updateGoalStmt = prepStatement($conn,
                "UPDATE `goals` SET amount = amount - ? WHERE id = ? AND `active` = 1", "ii", [$amount, $goalId]);
            if (!$updateGoalStmt->execute()) {
                error_log("Failed to execute update goal");
                error_log("MySQL Execution Error: " . $updateGoalStmt->error);
                throw new mysqli_sql_exception("Update statement execution failed.");
            } else if ($updateGoalStmt->affected_rows === 0) {
                // If the goal cannot be updated, the transaction cannot be edited or deleted
                $conn->rollback();
                $conn->close();
                return false;
            }
        }

        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to disable transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return false;
}

function addGoal($user, $name, $total, $amount) {
    $user = substr($user, 0, 32);
    $name = substr($name, 0, 64);
    $id = insert("INSERT INTO `goals` (user, `name`, `total`, `amount`) VALUES (?,?,?,?)", "ssii", [$user, $name, $total, $amount]);
    if (!$id) {
        return false;
    }
    return [
        'id' => $id,
        'user' => $user,
        'name' => $name,
        'total' => $total,
        'amount' => $amount,
    ];
}

function addGoalTransaction($user, $goalId, $amount) {
    $goalResult = select("SELECT `name`, `amount` FROM `goals` WHERE id = ? LIMIT 1", "i", [$goalId]);
    $goalRow = $goalResult->fetch_assoc();
    if (!$goalRow) {
        return false;
    }
    $verb = $amount > 0 ? "contribution" : "subtraction";
    $description = substr("Goal $verb: " . $goalRow["name"], 0, 64);
    $newGoalAmount = $goalRow["amount"] + $amount;
    $dateAdded = date('Y-m-d H:i:s', strtotime('-4 hours'));
    $user = substr($user, 0, 32);

    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        $createTxStmt = prepStatement($conn,
            "INSERT INTO `transactions` (user, amount, `description`, `goal_id`) VALUES (?,?,?,?)",
            "sisi", [$user, $amount, $description, $goalId]);

        $updateAmountStmt = prepStatement($conn,
            "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$amount]);

        $updateGoalStmt = prepStatement($conn,
            "UPDATE `goals` SET amount = amount + ? WHERE id = ? AND `active` = 1", "ii", [$amount, $goalId]);

        if (!$createTxStmt->execute()) {
            error_log("Failed to execute create tx");
            error_log("MySQL Execution Error: " . $createTxStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        }
        $transactionId = $conn->insert_id;
        if (!$updateAmountStmt->execute()) {
            error_log("Failed to execute update amount");
            error_log("MySQL Execution Error: " . $updateAmountStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        }
        if (!$updateGoalStmt->execute()) {
            error_log("Failed to execute update goal");
            error_log("MySQL Execution Error: " . $updateGoalStmt->error);
            throw new mysqli_sql_exception("Update statement execution failed.");
        } else if ($updateGoalStmt->affected_rows === 0) {
            // If the goal cannot be updated, the transaction cannot be added
            $conn->rollback();
            $conn->close();
            return false;
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to add goal transaction: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();

    return [
        'transaction' => [
            'id' => $transactionId,
            'user' => $user,
            'amount' => $amount,
            'description' => $description,
            'date_added' => $dateAdded,
            'goal_id' => $goalId,
            'active' => 1,
        ],
        'goalAmount' => $newGoalAmount,
    ];
}

function setGoalTotal($user, $goalId, $total) {
    $result = query("UPDATE `goals` SET `total` = ? WHERE id = ? LIMIT 1", "ii", [$total, $goalId]);
    if (!$result) {
        return false;
    }
    return ['id' => $goalId, 'total' => $total];
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

function loadRecurringTransactions() {
    $sql = "SELECT id, amount, description, start_month, end_month FROM `recurring_transactions`
            WHERE active = 1 ORDER BY id DESC";
    return select($sql, "", []);
}

function addRecurring($user, $amount, $description, $startMonth, $endMonth) {
    $user = substr($user, 0, 32);
    $description = substr($description, 0, 64);
    if ($endMonth === null) {
        $id = insert(
            "INSERT INTO `recurring_transactions` (user, amount, description, start_month) VALUES (?,?,?,?)",
            "siss", [$user, $amount, $description, $startMonth]
        );
    } else {
        $id = insert(
            "INSERT INTO `recurring_transactions` (user, amount, description, start_month, end_month) VALUES (?,?,?,?,?)",
            "sisss", [$user, $amount, $description, $startMonth, $endMonth]
        );
    }
    if (!$id) {
        return false;
    }
    return [
        'id' => $id,
        'amount' => $amount,
        'description' => $description,
        'start_month' => $startMonth,
        'end_month' => $endMonth,
    ];
}

function editRecurring($user, $id, $amount, $description, $startMonth, $endMonth) {
    $description = substr($description, 0, 64);
    if ($endMonth === null) {
        $result = query(
            "UPDATE `recurring_transactions` SET amount = ?, description = ?, start_month = ?, end_month = NULL WHERE id = ? AND user = ? AND active = 1",
            "issis", [$amount, $description, $startMonth, $id, $user]
        );
    } else {
        $result = query(
            "UPDATE `recurring_transactions` SET amount = ?, description = ?, start_month = ?, end_month = ? WHERE id = ? AND user = ? AND active = 1",
            "isssis", [$amount, $description, $startMonth, $endMonth, $id, $user]
        );
    }
    if (!$result) {
        return false;
    }
    return [
        'id' => $id,
        'amount' => $amount,
        'description' => $description,
        'start_month' => $startMonth,
        'end_month' => $endMonth,
    ];
}

function disableRecurring($user, $id) {
    return query(
        "UPDATE `recurring_transactions` SET active = 0 WHERE id = ? AND user = ?",
        "is", [$id, $user]
    );
}

function hasProcessedRecurring($month) {
    $result = select(
        "SELECT id FROM `recurring_processed` WHERE month = ? LIMIT 1",
        "s", [$month]
    );
    return $result && $result->num_rows > 0;
}

function processRecurringForMonth($month) {
    $conn = getConnection();
    $conn->begin_transaction();
    try {
        $conn->autocommit(false);

        // INSERT IGNORE: if the row already exists (race condition), affected_rows will be 0
        $markStmt = prepStatement($conn,
            "INSERT IGNORE INTO `recurring_processed` (month) VALUES (?)",
            "s", [$month]);

        if (!$markStmt->execute()) {
            error_log("Failed to mark recurring month processed");
            throw new mysqli_sql_exception("Failed to mark month processed.");
        }

        // If another request already processed this month, exit cleanly
        if ($conn->affected_rows === 0) {
            $conn->commit();
            $conn->close();
            return true;
        }

        // Fetch ALL users' due recurring transactions inside the transaction to avoid TOCTOU issues
        // end_month is exclusive: a recurring with end_month = current month does not fire
        $selectStmt = prepStatement($conn,
            "SELECT user, amount, description FROM `recurring_transactions`
             WHERE active = 1 AND start_month <= ? AND (end_month IS NULL OR end_month > ?)",
            "ss", [$month, $month]);

        if (!$selectStmt->execute()) {
            error_log("Failed to fetch due recurring transactions");
            error_log("MySQL Execution Error: " . $selectStmt->error);
            throw new mysqli_sql_exception("Failed to fetch recurring transactions.");
        }

        $recurringResult = $selectStmt->get_result();
        $amountSum = 0;

        while ($recurring = $recurringResult->fetch_assoc()) {
            $description = substr("monthly: " . $recurring["description"], 0, 64);
            $createStmt = prepStatement($conn,
                "INSERT INTO `transactions` (user, amount, description) VALUES (?, ?, ?)",
                "sis", [substr($recurring["user"], 0, 32), $recurring["amount"], $description]);

            if (!$createStmt->execute()) {
                error_log("Failed to create recurring transaction for month $month");
                error_log("MySQL Execution Error: " . $createStmt->error);
                throw new mysqli_sql_exception("Failed to create recurring transaction.");
            }
            $amountSum += $recurring["amount"];
        }

        if ($amountSum !== 0) {
            $updateAmountStmt = prepStatement($conn,
                "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$amountSum]);

            if (!$updateAmountStmt->execute()) {
                error_log("Failed to update amount for recurring transactions");
                error_log("MySQL Execution Error: " . $updateAmountStmt->error);
                throw new mysqli_sql_exception("Update amount failed for recurring transactions.");
            }
        }

        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        error_log("Failed to process recurring transactions: " . $exception->getMessage());
        $conn->rollback();
        throw $exception;
    }

    $conn->close();
    return true;
}

?>