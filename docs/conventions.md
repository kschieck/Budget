# Conventions

For full detail, see the rule files in `.claude/rules/`. This document summarizes the key conventions.

## Money

**Always integers in cents.** Never floats. This is the most important invariant in the codebase.

- DB stores cents: `INT` columns
- API transmits cents: `{ "amount": 1234 }` = $12.34
- React state holds cents: `transaction.amount = 1234`
- Convert to dollars only at display time: `toDollars(amount / 100)`
- Convert user input (dollars) to cents in PHP: `intval(floatval($input) * 100)`

## PHP Endpoints

1. `require_once(__DIR__."/dao.php");`
2. `session_start();` + session check → exit on failure
3. `switch ($_SERVER['REQUEST_METHOD'])`
4. Parse input, cast and validate all fields
5. Call a `dao.php` function
6. `echo json_encode(["success" => bool, ...]);`

Input parsing by method:
- `GET`: use `$_GET["param"]`
- `POST`: `$_POST = json_decode(file_get_contents("php://input"), true);`
- `PUT`/`DELETE`: `$data = json_decode(file_get_contents("php://input"), true);`

## React Components

- Default export: the section component (`TransactionsSection`, `GoalsSection`)
- Named exports: dialogs (`AddEditTransactionDialog`, `AddEditGoalDialog`)
- Internal components (not exported): `TransactionRow`, `GoalRow`
- All in the same file as their section

## API Functions (`API.js`)

One function per API action. Returns a plain promise. Caller checks `result.success`:

```js
export function saveTransaction(id, amount, description) {
  // id === -1 → POST (create), otherwise → PUT (update)
  return fetch("./transaction.php", { ... }).then(r => r.json());
}
```

## Error Handling

- PHP: `error_log("message")` for server errors, `["success" => false]` in response
- JS catch: `console.error(e); alert("User-facing message");`
- Never propagate PHP error details to the client response body

## File Naming

- PHP: lowercase with hyphens (`transaction-duplicate.php`)
- React: PascalCase for component files (`Transactions.js`, `Goals.js`)
- CSS: single file (`styles.css`)

## CSS Classes

Use existing classes from `styles.css` before adding new ones:
- `small_cell` — narrow table cell
- `small_button` — compact button (delete x)
- `space_right` — margin-right spacing
- `center_spaced` — centered with space-between layout
- `no_bottom_space` — removes bottom margin
- `goal_progress` — progress bar container
- `form_title` — dialog heading

## Soft Delete

Transactions are never hard-deleted. To delete: `UPDATE transactions SET active = 0 WHERE id = ?` and reverse the amount effect on the `amount` table (atomically).
