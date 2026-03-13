# Goals

## Overview

Goals are savings targets. Each goal has a `name`, a `total` (target amount in cents), and an `amount` (contributed so far in cents). Contributions create a linked transaction in the transactions table.

## Key Files

- `client/src/Goals.js` — GoalsSection, GoalRow, GoalTotalRow, AddEditGoalDialog, AddGoalTransactionDialog
- `server/goal.php` — CRUD for goals
- `server/transaction.php` — Goal contributions go through `POST transaction.php { goalId, amount }`
- `server/dao.php` — `addGoalTransaction`, `editTransaction`, `disableTransaction` (all goal-aware)

## Goal Transactions

When a contribution is made, a transaction is created with:
- `goal_id` set to the goal's ID
- `description` auto-set to `"Goal contribution: <name>"` or `"Goal subtraction: <name>"`

### Editing a Goal Transaction

Editing a transaction that has a `goal_id` (via `PUT transaction.php`) adjusts the linked goal atomically:

1. Load old `amount` from the transaction
2. Compute `delta = newAmount - oldAmount`
3. In a single MySQL transaction:
   - Update `transactions.amount` and `description`
   - Adjust `amount.amount` by `-delta`
   - Adjust `goals.amount` by `+delta`

The description is not user-editable for goal transactions — it is always rewritten to the standard "Goal contribution/subtraction: name" format.

### Deleting a Goal Transaction

`disableTransaction` in `dao.php` is goal-aware: if the transaction has a `goal_id`, deleting it also decrements `goals.amount` by the transaction's amount, keeping the goal balance consistent.

## Total Row

`GoalTotalRow` is rendered when there are 2 or more goals. It shows the sum of all `goal.amount` values over the sum of all `goal.total` values, computed in `GoalsSection`:

```js
goals.forEach((goal) => {
    goalAmountSum += goal.amount;
    goalTotalSum += goal.total;
});
// ...
<GoalTotalRow amount={goalAmountSum} total={goalTotalSum} />
```

## Constraints

- A goal cannot be deleted while it has a non-zero `amount` — the server returns `{ success: false, message: "Remove all contributions before deleting this goal." }`
- Goal names cannot be changed after creation (the name input is disabled in the edit dialog)
- Goal `total` (the target) can be updated independently via `PUT goal.php { goalId, amount }`
- Transactions linked to an inactive (soft-deleted) goal are **readonly in the UI** — edit and delete buttons are hidden. `TransactionsSection` receives the `goals` list and marks a `TransactionRow` readonly when its `goal_id` is non-null and does not match any entry in `goals` (inactive goals are not returned by `GET goal.php`). This mirrors the server-side protection that already blocks edits/deletes on such transactions.
