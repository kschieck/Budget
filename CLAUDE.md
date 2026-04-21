# CLAUDE.md

Personal budgeting web app — React 19 SPA + plain PHP REST backend, deployed as a zip to shared hosting.

## Permissions

- You have permission to read and write files without asking for confirmation
- You have permission to execute bash commands without asking for confirmation

## Commands

```bash
# Dev
cd client && npm start           # Dev server (proxies API to production backend)

# Build & Deploy
npm run build                    # Full build → deployment.zip
npm run build-no-zip             # Build without zipping → build/public/

# Format
npm run prettier                 # Format all JS files (run from repo root)
```

## Architecture

```
client/src/
  App.js              Auth flow + BudgetApp root (owns all shared state)
  API.js              All fetch calls — plain fetch, no library
  Transactions.js     Transaction list + add/edit dialogs
  Goals.js            Goals list + dialogs
  Charts.js           DrawdownChart (canvas-based spending chart)
  Filters.js          User filter toggles
  NewMonthTool.js     Duplicate previous month's transactions
  Utils.js            toDollars, toDollarsNoCents, getCookieValue

server/
  auth.php            Auth: cookie / token / username+password
  transaction.php     CRUD for transactions
  goal.php            CRUD for goals
  amount.php          Current budget balance (read-only)
  transaction-duplicate.php  Bulk-duplicate previous month transactions
  dao.php             All MySQL access — prepared statements via mysqli
  config.php          DB credentials + user list (not committed)

scripts/
  database.sql        Schema
```

## Data Model

All monetary values are **integers in cents** everywhere — DB, API, and React state. Convert to dollars only at display time.

- `amount`: single row, current budget balance. Kept in sync with `transactions` via MySQL transactions.
- `transactions`: spend/earn records. Positive = spending (decrements balance). Negative = income. Soft-deleted via `active = 0`.
- `goals`: savings targets. `total` = target, `amount` = contributed so far. Contributions create a transaction.
- `user_tokens`: remember-me tokens with expiry.

All date queries apply `DATE_SUB(date_added, INTERVAL 4 HOUR)` (UTC-4 — see Known Tech Debt).

## Auth Flow

1. Check `rememberme` cookie → POST `auth.php` (no body)
2. Fallback: `localStorage` token → POST `{ rememberme: token }`
3. Fallback: login form → POST `{ username, password }`
4. Success: server sets signed HMAC cookie, returns base64 token for localStorage

All PHP endpoints check `$_SESSION["budget_auth"]` (username) and exit if unset.

## Conventions

### Client (JS)
- All `fetch()` calls live in `API.js` — never fetch directly in components
- API functions return plain promises; always check `result.success` before acting
- Amounts stay as integers in cents until display: `toDollars(cents / 100)`
- User-facing errors: `alert("message")` in `.catch()` handlers
- No `console.log` or `debugger` in committed code

### Server (PHP)
- All SQL lives in `dao.php` — endpoint files call dao functions, never write SQL themselves
- Every endpoint: check session first, parse input, call dao, `echo json_encode(...)`, done
- Parse JSON body: `json_decode(file_get_contents("php://input"), true)` for POST/PUT/DELETE
- Cast all input: `intval()` for IDs, `intval(floatval($val) * 100)` for money, `trim()` for strings
- Server errors: `error_log()` only — never echo debug output
- Any operation that touches the `amount` table must use a MySQL transaction (see `dao.php` patterns)

## Never Do

- **Never add npm or composer dependencies without asking** — minimal deps is intentional
- **Never write SQL in endpoint files** — add a function in `dao.php` instead
- **Never store amounts as floats** — always integers in cents
- **Never skip the session auth check** in a PHP endpoint
- **Never echo debug output** from PHP — use `error_log()`
- **Never leave `console.log`, `debugger`, `var_dump`, or `print_r`** in committed code

## Known Tech Debt

Fix when touching related code — do not work around these issues:

- **Prop drilling** — `BudgetApp` passes many handlers deep through props. Use React context for new features; refactor existing when touching.
- **UTC-4 hardcoded** — Timezone offset is hardcoded in SQL queries. Should be a config value in `config.php`.

## Subagents

- **code-reviewer** — Reviews code for bugs, security issues, and convention violations. Use before finalizing any feature.
- **document-updater** — Updates files in `docs/` based on a git diff. Use after completing a feature to keep docs in sync. Scoped strictly to `docs/` — will not touch source files.

## Skills

- **react-patterns** — Loaded automatically when building React features. Component structure, state, API, and dialog patterns.
- **project-context** — Invoke as `/project-context [topic]` to research a specific area of the codebase. Also loads automatically for background context.
- **review-changes** — Invoke as `/review-changes` to audit staged/unstaged git changes before committing.

## Planning Rules

- Before starting any non-trivial task, state which subagents or skills will be used and why.
- Push back when an approach seems wrong — suggest alternatives rather than blindly implementing.
- When you discover an undocumented pattern, suggest adding it to CLAUDE.md.

## Compaction Priorities

When compacting, always preserve:
- The list of files modified in this session
- Any commands currently being run or tested
- The current implementation plan and decisions made
- Any constraints established during this session (e.g., "don't touch auth.php")
