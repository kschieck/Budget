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
  ├── transactions → TransactionsSection → TransactionRow
  ├── goals → GoalsSection → GoalRow
  ├── amountTotal → MonthSelector (displayed as balance)
  ├── filters → FiltersSection
  └── monthOffset → MonthSelector (controls which month loads)
```

## User Action → API → State Update

### Add Transaction
```
User fills form → onSave(id=-1, amount, description)
  → API.saveTransaction(-1, amount, description)
  → POST transaction.php { amount (dollars), description }
  → PHP: intval(floatval(amount) * 100) → cents
  → dao.addTransaction() → INSERT transaction + UPDATE amount (atomic)
  → { success: true }
  → loadAmountTotal() + loadTransactions()
```

### Edit Transaction
```
User edits form → onSave(id, amount, description)
  → API.saveTransaction(id, amount, description)
  → PUT transaction.php { transactionId, amount, description }
  → dao.editTransaction() → calculate delta, UPDATE both (atomic)
  → { success: true }
  → loadAmountTotal() + loadTransactions()
```

### Delete Transaction
```
User clicks x → startDeleteTransaction(id)
  → API.deleteTransaction(id)
  → DELETE transaction.php { id }
  → dao.disableTransaction() → SET active=0 + reverse amount (atomic)
  → { success: true }
  → loadAmountTotal() + optimistic: filter transaction from state
```

### Goal Contribution
```
User enters amount → onSave(goalId, amount)
  → API.saveGoalTransaction(goalId, amount)
  → POST transaction.php { goalId, amount }
  → dao.addGoalTransaction() → INSERT transaction (with goal_id) + UPDATE amount + UPDATE goal (atomic)
  → { success: true }
  → loadAmountTotal() + loadTransactions() + loadGoals()
```

### Edit Goal Transaction
```
User edits amount on a goal-linked transaction → onSave(id, amount, description)
  → API.saveTransaction(id, amount, description)
  → PUT transaction.php { transactionId, amount, description }
  → dao.editTransaction() → load old amount + goal_id
      → delta = newAmount - oldAmount
      → UPDATE transactions + UPDATE amount (-delta) + UPDATE goals (+delta) (atomic)
      → description is rewritten to "Goal contribution/subtraction: <name>" (not user-editable)
  → { success: true }
  → loadAmountTotal() + loadTransactions() + loadGoals()
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

// Any mutation (POST/PUT/DELETE)
{ "success": true }
// or
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

## Month Navigation

`monthOffset` (integer, 0 = current month) drives which transactions load:

```
monthOffset changes → useEffect fires → setTransactions([]) → API.reloadTransactions(offset)
  → GET transaction.php?past=N
  → PHP: date('Y-m-01', strtotime("-4 hours -N months")) to date('Y-m-t', ...)
  → returns transactions for that calendar month
```

When `monthOffset > 0`: transactions are read-only, goals are hidden, chart is visible.
