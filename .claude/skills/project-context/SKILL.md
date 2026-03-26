---
description: Project context and codebase research. Invoke as /project-context [topic] to explore a specific area. Loads automatically as background context.
user-invocable: true
context: fork
agent: Explore
---

# Project Context

A personal budgeting web app for tracking spending and savings goals. Multiple users share a single budget. The total available balance decreases as transactions are recorded.

## Domain Concepts

- **Amount**: The current total budget balance — a single row in the `amount` table, stored in cents. The source of truth for "how much money is left."
- **Transaction**: A spend or earn record. Positive amount = spending (decrements balance). Negative = income (increments balance). Soft-deleted via `active = 0`.
- **Goal**: A savings target with `total` (target amount) and `amount` (contributed so far). Contributing to a goal creates a transaction and increments the goal's `amount`.
- **Month view**: Users browse past months via `monthOffset` (0 = current month). Data loads fresh on each month change.
- **New Month Tool**: Duplicates "first of month" setup transactions from the previous month into the current month.
- **User**: Transactions have a `user` field (username string). Multiple users can record transactions; the Filters section shows/hides per-user transactions.

## Key Architectural Decisions

- All monetary values are integers in cents everywhere — no floats in the data path
- The `amount` table stays in sync with `transactions` via MySQL transactions (atomic: insert transaction + update amount together)
- Soft delete only — transactions set `active = 0`, never hard deleted
- Auth is session-based (`$_SESSION["budget_auth"]`), with HMAC-signed remember-me cookies and DB-stored tokens
- No client-side routing — single page with conditional rendering based on state
- No ORM or query builder — raw SQL with prepared statements in `dao.php`
- No test framework configured

## File Map

| File | Purpose |
|------|---------|
| `client/src/App.js` | Auth flow + `BudgetApp` (all shared state) |
| `client/src/API.js` | Every fetch call to the PHP backend |
| `client/src/Transactions.js` | Transaction list + add/edit dialogs |
| `client/src/Goals.js` | Goals list + contribute/add/edit dialogs |
| `client/src/Charts.js` | `DrawdownChart` — canvas spending chart |
| `client/src/Filters.js` | Per-user filter toggles |
| `client/src/NewMonthTool.js` | Month duplication UI |
| `client/src/Utils.js` | `toDollars`, `toDollarsNoCents`, `getCookieValue` |
| `server/dao.php` | All MySQL queries (prepared statements) |
| `server/transaction.php` | Transaction CRUD endpoint |
| `server/goal.php` | Goal CRUD endpoint |
| `server/auth.php` | Auth endpoint |
| `server/amount.php` | Balance read endpoint |
| `server/config.php` | DB creds + user list (not committed) |
| `scripts/database.sql` | Schema |

## Known Tech Debt

- Deep prop drilling in `BudgetApp` — handlers passed through multiple levels
- Filter Set contains *hidden* usernames (inverted logic)
- UTC-4 timezone hardcoded in SQL queries

---

When invoked as `/project-context $ARGUMENTS`, explore the codebase to answer questions about **$ARGUMENTS**. Search in:
- `client/src/` for React component behavior and state
- `server/` for API endpoint logic
- `server/dao.php` for database queries
- `scripts/database.sql` for the schema

Return structured findings with file:line references.
