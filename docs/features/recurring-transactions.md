# Recurring Transactions

## Overview

Recurring transactions are templates that are automatically materialized as real transactions on the first load of each calendar month. They are managed through a "next month" view, accessible by navigating forward from the current month.

## Key Files

- `client/src/RecurringTransactions.js` — `RecurringTransactionsSection` (default export), `AddEditRecurringDialog` (named export), `RecurringTransactionRow` (internal)
- `server/recurring.php` — CRUD endpoint for recurring transaction templates
- `server/transaction.php` — GET handler for `past=0` triggers materialization if not yet done for the month
- `server/dao.php` — `loadRecurringTransactions`, `addRecurring`, `editRecurring`, `disableRecurring`, `hasProcessedRecurring`, `processRecurringForMonth`

## Data Model

### `recurring_transactions`

| Column | Type | Notes |
|---|---|---|
| `id` | int | Auto-increment primary key |
| `user` | varchar(32) | Owner username |
| `amount` | int | Spending amount in cents (positive = spending, negative = income) |
| `description` | varchar(64) | Template description |
| `active` | tinyint(1) | Soft-delete flag (1 = active) |
| `start_month` | varchar(7) | First month to materialize (`YYYY-MM`). Always set server-side to next calendar month. |
| `end_month` | varchar(7) | Last month to materialize (`YYYY-MM`), nullable |

### `recurring_processed`

Tracks which user-month combinations have had recurring transactions materialized. Has a `UNIQUE KEY (user, month)` to prevent duplicate processing under concurrent requests.

| Column | Type | Notes |
|---|---|---|
| `id` | int | Auto-increment primary key |
| `user` | varchar(32) | Username |
| `month` | varchar(7) | Month string (`YYYY-MM`) |

## API: `recurring.php`

All methods require an active session (`$_SESSION["budget_auth"]`).

### GET

Returns all active recurring templates for the authenticated user.

```
GET recurring.php
→ { "success": true, "recurring": [{ "id", "amount", "description", "start_month", "end_month" }] }
```

### POST — Create

Creates a new recurring template. `start_month` is always set server-side to next calendar month (UTC-4) and cannot be supplied by the client.

```json
{ "amount": 1500, "description": "gym membership", "end_month": "2025-12" }
```

`end_month` is optional. If supplied it must match `/^\d{4}-\d{2}$/`.

### PUT — Edit

Updates amount, description, and/or end_month on an existing template. Only affects future materializations — already-created transactions are untouched.

```json
{ "id": 3, "amount": 1500, "description": "gym membership", "end_month": null }
```

### DELETE — Soft Delete

Sets `active = 0`. The template will no longer be selected in future materializations.

```json
{ "id": 3 }
```

## Materialization

Recurring transactions are materialized as real rows in the `transactions` table. This happens inside a MySQL transaction for atomicity and uses `INSERT IGNORE` on `recurring_processed` to handle race conditions.

**Trigger:** The first `GET transaction.php?past=0` request each month (per user) triggers materialization.

**Selection criteria:** A template is included if:
- `active = 1`
- `start_month <= currentMonth`
- `end_month IS NULL OR end_month >= currentMonth`

**Materialized transaction description:** `substr("monthly: " + description, 0, 64)`

**Amount effect:** The sum of all materialized amounts is deducted from the `amount` table atomically in the same MySQL transaction.

**Skipped months are not backfilled.** If the app is not opened during a given month, that month's recurring transactions are never created.

## UI: Next Month View

`RecurringTransactionsSection` is shown when `monthOffset === -1` (the user has navigated one step forward from the current month). The "next" navigation button is hidden at this point.

In this view:
- The drawdown chart, tools toolbar, and filters section are all hidden
- Transaction loading is skipped (the section manages its own data via `API.loadRecurringTransactions`)
- Users can add, edit, or soft-delete recurring templates

### Row Interaction

Clicking a `RecurringTransactionRow` description toggles edit (✎) and delete (✕) action buttons. Clicking the ✎ button opens the edit dialog; clicking ✕ deletes the template. Clicking the description again hides the buttons.

## Client API Functions (`API.js`)

```js
loadRecurringTransactions()
  → GET recurring.php
  → { success, recurring: [...] }

saveRecurringTransaction(id, amount, description, endMonth)
  // id === -1 → POST (create), otherwise → PUT (update)
  → { success }

deleteRecurringTransaction(id)
  → DELETE recurring.php { id }
  → { success }
```

## Constraints

- `start_month` is always computed server-side as next calendar month (UTC-4). The client cannot set it.
- `end_month` must match `YYYY-MM` format if provided; an empty string is treated as null (no end date).
- Amount must be non-zero; description must be non-empty — both validated server-side.
- Editing a template has no effect on transactions that have already been materialized.
- Deleting a template (soft delete) has no effect on transactions that have already been materialized.
