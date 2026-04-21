# Architecture

## Overview

Single-page React app served alongside PHP REST endpoints from a shared hosting environment. The React build and PHP files are co-located in the same directory after deployment.

## Deployment Model

```
Shared host (stratfordtreasurehunt.com/budget)
├── index.html          (React entrypoint)
├── static/             (React assets)
├── auth.php
├── transaction.php
├── goal.php
├── amount.php
├── transaction-duplicate.php
├── recurring.php
├── dao.php
└── config.php
```

The React app uses `homepage: "./"` so asset paths are relative, allowing it to be served from a subdirectory. In development, `client/package.json` proxies API calls to the production server.

## Build Pipeline

`npm run build` (gulp):
1. Clean `build/` directory
2. `cd client && npm run build` — React production build
3. Copy React build to `build/public/`
4. Copy `server/*.php` to `build/public/`
5. Zip `build/public/` → `deployment.zip`
6. Clean `build/` directory

## Client Architecture

```
App (auth state)
└── BudgetApp (all shared state)
    └── BudgetContext.Provider (monthOffset, filters, navigation handlers)
        ├── MonthSelector
        ├── DrawdownChart           (hidden on next-month view)
        ├── TransactionsSection     (shown when monthOffset >= 0)
        │   └── TransactionRow (×N)
        │   [AddEditTransactionDialog] (modal)
        ├── RecurringTransactionsSection  (shown when monthOffset === -1)
        │   └── RecurringTransactionRow (×N)
        │   [AddEditRecurringDialog] (modal)
        ├── GoalsSection            (current month only)
        │   └── GoalRow (×N)
        │   └── GoalTotalRow
        │   [AddEditGoalDialog] (modal)
        │   [AddGoalTransactionDialog] (modal)
        ├── FiltersSection          (hidden on next-month view)
        └── [NewMonthToolDialog] (modal, hidden on next-month view)
```

State ownership:
- `App`: auth state (`loggingIn`, `authSuccess`)
- `BudgetApp`: transaction/goal domain state (`transactions`, `goals`, `amountTotal`) and dialog flags (`showAddTransaction`, `editingTransactionId`, `showAddGoal`, `editingGoalId`, `contributingGoalId`)
- `useBudgetNavigation` hook (in `BudgetContext.js`): `monthOffset` and `filters` state, their effects, and the handlers `previousMonth`, `nextMonth`, `changeFilterState`
- Components: local UI state only (`showActions`, form input values)

`BudgetContext.js` exports:
- `BudgetContext` — the context object (default export), provides `{ monthOffset, filters, previousMonth, nextMonth, changeFilterState }`
- `useBudget()` — consumer hook; throws if called outside a `BudgetContext.Provider`
- `useBudgetNavigation(transactions, setTransactions)` — called by `BudgetApp` to produce the context value; owns `monthOffset`/`filters` state and the effects that reload transactions when the month changes or reset filters when transactions reload

Dialog rendering uses boolean/ID flags with conditional rendering in JSX — dialogs are never stored as live JSX in state.

`filters` is a `Set` of visible usernames, or `null` when not yet initialized. A `useEffect` watching `[filters, transactions]` initializes it to all current users whenever it is `null` and transactions have loaded. `TransactionsSection` receives a pre-filtered `filteredTransactions` derived value (computed in `BudgetApp`); `DrawdownChart` receives the raw `transactions`.

`TransactionsSection` reads `monthOffset` from context and computes `readonly = monthOffset !== 0` locally rather than receiving it as a prop.

## Server Architecture

Each PHP file handles one resource via HTTP method dispatch:

```
Request → endpoint.php → session check → method switch → dao.php → JSON response
```

All database access goes through `dao.php`, which provides:
- `select()` — parameterized SELECT, returns `mysqli_result`
- `insert()` — parameterized INSERT, returns insert ID
- `query()` — parameterized UPDATE/DELETE, returns bool
- `getConnection()` / `prepStatement()` — for multi-statement transactions

## Data Integrity

The `amount` table (single-row balance) must always stay in sync with the sum of active transactions. This is enforced at the DB layer using MySQL transactions in `dao.php`:

- `addTransaction()` — INSERT row + UPDATE amount atomically
- `editTransaction()` — UPDATE transaction + UPDATE amount delta atomically
- `disableTransaction()` — SET active=0 + reverse amount atomically
- `addGoalTransaction()` — INSERT transaction + UPDATE amount + UPDATE goal atomically
- `processRecurringForMonth()` — INSERT recurring_processed row (UNIQUE KEY prevents duplicates) + INSERT transactions + UPDATE amount atomically

## Auth Architecture

```
Client                          Server
------                          ------
Check rememberme cookie ──────► auth.php POST (no body)
  └─ if cookie exists               └─ validate HMAC cookie
                                        └─ success: session set

Check localStorage token ──────► auth.php POST { rememberme: token }
  └─ if token exists                └─ validate token vs user_tokens table
                                        └─ success: session set

Login form ────────────────────► auth.php POST { username, password }
                                    └─ crypt() verify vs config.php users
                                        └─ success: set cookie + return token
                                               client stores token in localStorage
```

Session: `$_SESSION["budget_auth"]` = username string. All endpoints check this first.
