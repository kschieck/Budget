---
paths:
  - "server/dao.php"
  - "server/**/*.php"
---

# Database Conventions

## Access Pattern

All SQL lives in `dao.php`. The four core helpers are:

- `select($sql, $types, $params)` — returns a `mysqli_result` or `null`
- `insert($sql, $types, $params)` — returns the insert ID or `0`
- `query($sql, $types, $params)` — returns `true` or `false` (for UPDATE/DELETE with no return value)
- `getConnection()` — returns a raw `mysqli` for multi-statement transactions

Use the appropriate helper. Only reach for `getConnection()` when you need atomic multi-step operations.

## Multi-Step Operations (amount table)

Any operation that modifies both `transactions` and `amount` must be atomic. Follow this pattern from `addTransaction()`:

```php
$conn = getConnection();
$conn->begin_transaction();
try {
    $conn->autocommit(false);

    $stmt1 = prepStatement($conn, "INSERT INTO ...", "...", [...]);
    if (!$stmt1->execute()) throw new mysqli_sql_exception("...");

    $stmt2 = prepStatement($conn, "UPDATE amount SET amount = amount - ? LIMIT 1", "i", [$amount]);
    if (!$stmt2->execute()) throw new mysqli_sql_exception("...");

    $conn->commit();
} catch (mysqli_sql_exception $e) {
    error_log("...: " . $e->getMessage());
    $conn->rollback();
    throw $e;
}
$conn->close();
```

Never update the `amount` table outside of a transaction that also updates `transactions`.

## Data Types

- All monetary values: `INT` (cents) — never `DECIMAL` or `FLOAT`
- Timestamps: `DATETIME` (stored in UTC, queried with `DATE_SUB(..., INTERVAL 4 HOUR)` for UTC-4 offset)
- String fields: `VARCHAR` with a max length enforced in PHP via `substr($val, 0, N)` before insert

## Soft Delete

Transactions are soft-deleted: `UPDATE transactions SET active = 0 WHERE id = ?`. Deleting a transaction reverses its effect on `amount` (add back the amount). Never hard-delete transaction records.

## Timezone

All date queries apply `DATE_SUB(date_added, INTERVAL 4 HOUR)` to adjust from UTC to UTC-4. This offset is hardcoded (tech debt — should be a config value). Do not add new hardcoded offsets; if adding date logic, use the same `DATE_SUB` pattern and note it as tech debt.

## Prepared Statement Types

- `i` — integer
- `s` — string
- `d` — double (avoid for money — use integer cents)

Type string length must match `$params` array length exactly.
