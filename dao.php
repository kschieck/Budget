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
    $returnResult = null;
    if($row = $result->fetch_assoc()) {
        return $row["amount"];
    }
    return 0;
}

function loadTransactions($limit) {
    $limit = intval($limit);
    if ($limit <= 0) {
        return [];
    }
    $sql = "SELECT DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, amount, `description` FROM `transactions` ORDER BY id DESC LIMIT $limit";
    return select($sql, "", []);
}

function loadTransactionsDate($dateString) {
    $sql = "SELECT DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, amount, `description`
            FROM `transactions`
            WHERE DATE_SUB(date_added, INTERVAL 4 HOUR) > ? ORDER BY id DESC";
    return select($sql, "s", [$dateString]);
}

function loadGoals($limit) {
    $limit = intval($limit);
    if ($limit <= 0) {
        return [];
    }
    $sql = "SELECT id, DATE_SUB(date_added, INTERVAL 4 HOUR) as date_added, total, amount, `name` FROM `goals` ORDER BY id DESC LIMIT $limit";
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

function addGoal($user, $name, $total, $amount) {
    return insert("INSERT INTO `goals` (user, `name`, `total`, `amount`) VALUES (?,?,?,?)", "ssii", [substr($user, 0, 32), substr($name, 0, 64), $total, $amount]);
}

function addGoalTransaction($user, $goalId, $amount) {
    $goalResult = select("SELECT `name` FROM `goals` WHERE id = ? LIMIT 1", "i", [$goalId]);
    $goalRow = $goalResult->fetch_assoc();
    if (!$goalRow) {
        return false;
    }
    $description = $goalRow["name"];

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

?>