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

Use existing classes from `styles.css` before adding new ones. The stylesheet uses CSS custom properties (design tokens) defined in `:root` — always prefer tokens over hard-coded values when writing inline styles.

Button classes:
- `btn-icon` — 36 × 36 px circular icon button (nav arrows, add +). Background: `--color-accent-light`, hover fills with `--color-accent`.
- `btn-icon-sm` — 26 × 26 px circular icon button for destructive actions (✕ delete). Background: `--color-negative-light`, hover fills with `--color-negative`.
- `btn-primary` — full pill-shaped primary action (dialog Save).
- `btn-ghost` — transparent pill-shaped secondary action (dialog Cancel).

Layout utilities:
- `center_spaced` — `display: flex; justify-content: space-between; align-items: center`
- `no_bottom_space` — removes bottom margin
- `space_right` — `margin-right: var(--space-sm)`
- `hidden` — `display: none !important`

Component-specific:
- `small_cell` — table cell with truncation (overflow ellipsis)
- `soft_underline` — `border-bottom: 1px solid var(--color-border)`
- `goal_progress` — progress bar container (full-radius, `--color-progress-bg` background)
- `form_title` — dialog heading
- `short_input` — narrow number input (New Month Tool)
- `small_button` — legacy fallback; prefer `btn-icon-sm` for new delete buttons

## Soft Delete

Transactions are never hard-deleted. To delete: `UPDATE transactions SET active = 0 WHERE id = ?` and reverse the amount effect on the `amount` table (atomically).
