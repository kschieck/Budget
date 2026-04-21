---
paths:
  - "server/**/*.php"
---

# PHP API Design

## Endpoint Structure

Every endpoint file follows this pattern:

```php
<?php
require_once(__DIR__."/dao.php");

session_start();
if (!isset($_SESSION["budget_auth"])) {
    echo json_encode(["success" => false]);
    exit(1);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case "GET":
        // ...
        break;
    case "POST":
        $_POST = json_decode(file_get_contents("php://input"), true);
        // ...
        break;
    // etc.
    default:
        echo json_encode(["success" => false]);
}
?>
```

## Input Handling

Always parse and cast — never trust raw input:

```php
// JSON body (POST/PUT/DELETE)
$data = json_decode(file_get_contents("php://input"), true);
if ($data === null) {
    echo json_encode(["success" => false]);
    exit(1);
}

// Casting
$id = intval($data["id"]);
$amount = intval(floatval($data["amount"]) * 100);  // dollars → cents
$description = trim($data["description"]);

// Validate before proceeding
if ($amount === 0 || strlen($description) === 0) {
    echo json_encode(["success" => false]);
    exit(1);
}
```

## Responses

- Always return JSON: `echo json_encode(["success" => bool, ...data])`
- On success with data: `["success" => true, "transactions" => $transactions]`
- On any failure: `["success" => false]`
- No error details in the response body — use `error_log()` for server-side logging
- Include error reason when available: `["success" => false, "reason" => "invalid amount"]`

## Database Access

- Never write SQL in endpoint files — call functions from `dao.php`
- If you need a new query, add a function to `dao.php`
- Wrap mutations that touch the `amount` table in a MySQL transaction (see `addTransaction()` in `dao.php` as the canonical pattern)

## Error Handling

```php
try {
    $result = someDao Function(...);
    echo json_encode(["success" => $result]);
} catch (Error $e) {
    echo json_encode(["success" => false]);
}
```

Catch `Error` (not `Exception`) for dao function failures — `dao.php` throws on DB errors.
