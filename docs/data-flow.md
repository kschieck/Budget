# Data Flow

## State → UI

All domain state lives in `BudgetApp` (`App.js`). On mount, three effects load initial data:

```
useEffect → loadAmountTotal() → API.loadAmount() → GET amount.php → setAmountTotal(cents)
useEffect → loadGoals()       → API.loadGoals()   → GET goal.php   → setGoals([...])
useEffect(monthOffset) → loadTransactions() → API.reloadTransactions(offset) → GET transaction.php?past=N → setTransactions([...])
```

State flows down as props:
```
BudgetApp
  ├── filteredTransactions → TransactionsSection → TransactionRow  (current/past months only)
  ├── transactions (unfiltered) → DrawdownChart                    (current/past months only)
  ├── goals → GoalsSection → GoalRow                              (current month only)
  ├── goals → TransactionsSection → TransactionRow (readonly guard for inactive-goal transactions)
  ├── amountTotal → MonthSelector (displayed as balance)
  ├── filters → FiltersSection                                     (current/past months only)
  ├── monthOffset → MonthSelector (controls which month loads)
  └── (next month: RecurringTransactionsSection self-loads, no transactions/filters)
```

`filteredTransactions` is a derived value computed in `BudgetApp`:
- `filters === null` (not yet initialized): equals `transactions` — all shown
- `filters` is a non-empty `Set`: equals `transactions.filter(t => filters.has(t.user))` — only matching users shown
- `filters` is an empty `Set` (all unchecked): equals `[]` — nothing shown

The chart always receives the full unfiltered `transactions` regardless of filter state. The "spent" total in the header also uses unfiltered `transactions`.

## User Action → API → State Update

### Add Transaction
```
User fills form → onSave(id=-1, amount, description)
  → API.saveTransaction(-1, amount, description)
  → POST transaction.php { amount (dollars), description }
  → PHP: intval(floatval(amount) * 100) → cents
  → dao.addTransaction() → INSERT transaction + UPDATE amount (atomic)
  → { success: true, transaction: { id, user, amount, description, date_added, goal_id, active } }
  → setTransactions(prev => [transaction, ...prev])
  → setAmountTotal(prev => prev - transaction.amount)
```

### Edit Transaction
```
User edits form → onSave(id, amount, description)
  → capture prevTransaction from state before closing dialog
  → API.saveTransaction(id, amount, description)
  → PUT transaction.php { transactionId, amount, description }
  → dao.editTransaction() → calculate delta, UPDATE both (atomic)
  → { success: true, transaction: { id, amount, description, goal_id } }
  → setTransactions: merge { amount, description } into matching transaction
  → setAmountTotal(prev => prev + prevTransaction.amount - transaction.amount)
```

### Delete Transaction
```
User clicks x → startDeleteTransaction(id)
  → capture transaction from state before API call
  → API.deleteTransaction(id)
  → DELETE transaction.php { id }
  → dao.disableTransaction() → SET active=0 + reverse amount (atomic)
  → { success: true }
  → setTransactions: filter out deleted transaction
  → setAmountTotal(prev => prev + transaction.amount)
  → if transaction.goal_id: setGoals: subtract transaction.amount from goal.amount
```

### Goal Contribution
```
User enters amount → onSave(goalId, amount)
  → capture goalId before closing dialog
  → API.saveGoalTransaction(goalId, amount)
  → POST transaction.php { goalId, amount }
  → dao.addGoalTransaction() → INSERT transaction (with goal_id) + UPDATE amount + UPDATE goal (atomic)
  → { success: true, transaction: { id, user, amount, description, date_added, goal_id, active }, goalAmount: int }
  → setTransactions(prev => [transaction, ...prev])
  → setAmountTotal(prev => prev - transaction.amount)
  → setGoals: set goal.amount = goalAmount
```

### Edit Goal Transaction
```
User edits amount on a goal-linked transaction → onSave(id, amount, description)
  → capture prevTransaction from state before closing dialog
  → API.saveTransaction(id, amount, description)
  → PUT transaction.php { transactionId, amount, description }
  → dao.editTransaction() → load old amount + goal_id
      → delta = newAmount - oldAmount
      → UPDATE transactions + UPDATE amount (-delta) + UPDATE goals (+delta) (atomic)
      → description is rewritten to "Goal contribution/subtraction: <name>" (not user-editable)
  → { success: true, transaction: { id, amount, description, goal_id } }
  → setTransactions: merge { amount, description } into matching transaction
  → setAmountTotal(prev => prev + prevTransaction.amount - transaction.amount)
  → setGoals: adjust goal.amount by (transaction.amount - prevTransaction.amount)
```

## API Response Shape

All endpoints return JSON. Success shape varies by endpoint:

```json
// GET transaction.php
{ "success": true, "transactions": [{ "id", "date_added", "amount", "description", "active", "user" }] }

// GET goal.php
{ "success": true, "goals": [{ "id", "date_added", "total", "amount", "name" }] }

// GET amount.php
{ "success": true, "amount": 123456 }

// GET recurring.php
{ "success": true, "recurring": [{ "id", "amount", "description", "start_month", "end_month" }] }

// POST transaction.php (regular transaction)
{ "success": true, "transaction": { "id", "user", "amount", "description", "date_added", "goal_id", "active" } }

// PUT transaction.php
{ "success": true, "transaction": { "id", "amount", "description", "goal_id" } }

// DELETE transaction.php
{ "success": true }

// POST transaction.php (goal contribution)
{ "success": true, "transaction": { "id", "user", "amount", "description", "date_added", "goal_id", "active" }, "goalAmount": 12300 }

// POST goal.php
{ "success": true, "goal": { "id", "user", "name", "total", "amount" } }

// PUT goal.php
{ "success": true, "goal": { "id", "total" } }

// DELETE goal.php
{ "success": true }
// or on validation failure:
{ "success": false, "message": "Remove all contributions before deleting this goal." }

// POST recurring.php
{ "success": true, "recurring": { "id", "amount", "description", "start_month", "end_month" } }

// PUT recurring.php
{ "success": true, "recurring": { "id", "amount", "description", "start_month", "end_month" } }

// DELETE recurring.php
{ "success": true }

// Any failure
{ "success": false }
```

All amounts in API responses are **integers in cents**.

## Auth Flow

```
App mounts
  → check document.cookie for "rememberme"
      → yes: API.AuthWithCookie() → POST auth.php (no body)
             server validates HMAC cookie → session set → { success: true }
             → setAuthSuccess(true) → render BudgetApp

  → no cookie: check localStorage["rememberme"]
      → yes: API.AuthWithToken(token) → POST auth.php { rememberme: token }
             server validates token in user_tokens table → session set
             → setAuthSuccess(true) → render BudgetApp

  → no token: render LoginForm
      → user submits → API.AuthWithUserPass(user, pass) → POST auth.php { username, password }
             server: crypt() verify, set cookie, INSERT user_tokens
             → { success: true, token, expire }
             → store token in localStorage → setAuthSuccess(true) → render BudgetApp
```

## Transaction Row Interaction (Revealing Edit/Delete)

`TransactionRow` uses a device-aware pattern to reveal its edit and delete buttons:

- **Hover-capable devices** (pointer with hover, e.g. desktop): buttons appear when the mouse enters the row (`onMouseEnter`) and disappear when it leaves (`onMouseLeave`). The row click handler does nothing on these devices.
- **Touch devices** (no hover): buttons are toggled by tapping the description cell. The `onMouseEnter`/`onMouseLeave` handlers are still present but exit immediately when `window.matchMedia("(hover: hover)")` does not match.

The capability check is performed at event time via `window.matchMedia("(hover: hover)").matches`. Readonly rows (past-month view or inactive-goal transactions) guard against `handleMouseEnter` early so the actions never appear.

`GoalRow` in `Goals.js` follows the same pattern without a readonly guard (goal rows are never readonly).

## Month Navigation

`monthOffset` (integer) drives which view and data loads:

| monthOffset | View |
|---|---|
| 0 | Current month — transactions editable, goals visible |
| 1, 2, … | Past month — transactions read-only, goals hidden, chart visible |
| -1 | Next month — RecurringTransactionsSection shown, chart/tools/filters hidden |

```
monthOffset changes → useEffect fires
  if monthOffset === -1: skip transaction load (RecurringTransactionsSection self-loads)
  otherwise: setTransactions([]) → API.reloadTransactions(offset)
    → GET transaction.php?past=N
    → PHP: date('Y-m-01', strtotime("-4 hours -N months")) to date('Y-m-t', ...)
    → returns transactions for that calendar month
```

The "next" navigation button is hidden when `monthOffset === -1` (cannot navigate past next month).

### Recurring Materialization on Current-Month Load

When `GET transaction.php?past=0` (current-month load) is handled by PHP, it checks whether recurring transactions have been materialized for the current month (UTC-4) before returning results:

```
GET transaction.php?past=0
  → PHP: $currentMonth = date("Y-m", strtotime("-4 hours"))
  → hasProcessedRecurring(user, currentMonth)?
      → no: processRecurringForMonth(user, currentMonth)
              → INSERT IGNORE INTO recurring_processed (user, month)
              → if affected_rows === 0: another request already processed — skip
              → SELECT due recurring_transactions (start_month <= month AND end_month IS NULL OR >= month)
              → INSERT transactions (description = substr("monthly: " + desc, 0, 64))
              → UPDATE amount (atomic)
      → yes: skip
  → return transactions for current month (including newly materialized ones)
```

Skipped months are not backfilled — if the app is not opened during a month, that month's recurring transactions are never materialized.