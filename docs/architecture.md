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
    ├── MonthSelector
    ├── DrawdownChart
    ├── TransactionsSection
    │   └── TransactionRow (×N)
    │   [AddEditTransactionDialog] (modal)
    ├── GoalsSection
    │   └── GoalRow (×N)
    │   └── GoalTotalRow
    │   [AddEditGoalDialog] (modal)
    │   [AddGoalTransactionDialog] (modal)
    ├── FiltersSection
    └── [NewMonthToolDialog] (modal)
```

State ownership:
- `App`: auth state (`loggingIn`, `authSuccess`)
- `BudgetApp`: all domain state (`transactions`, `goals`, `amountTotal`, `monthOffset`, `filters`, `activeDialog`)
- Components: local UI state only (`showDelete`, form input values)

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

- `addTransactions()` — INSERT rows + UPDATE amount atomically
- `editTransaction()` — UPDATE transaction + UPDATE amount delta atomically
- `disableTransaction()` — SET active=0 + reverse amount atomically
- `addGoalTransaction()` — INSERT transaction + UPDATE amount + UPDATE goal atomically

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
