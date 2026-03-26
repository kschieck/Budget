---
description: Reviews staged and unstaged git changes for bugs, convention violations, and edge cases. Invoke as /review-changes before committing.
user-invocable: true
context: fork
allowed-tools: Bash, Read, Grep, Glob
---

# Review Changes

Run these commands to inspect the current changeset:

```bash
git diff           # unstaged changes
git diff --cached  # staged changes
git status         # overview
```

Then review each changed file against the checklist below.

## Checklist

### Money Handling
- [ ] All monetary values handled as integers in cents — no float arithmetic on money values
- [ ] User input converted with `intval(floatval($val) * 100)` on the PHP side
- [ ] Amounts divided by 100 only at display time in JS (`toDollars(cents / 100)`)

### API Correctness
- [ ] Client checks `result.success` before acting on any API response
- [ ] All new fetch calls are in `API.js`, not in component files
- [ ] New PHP endpoints return `{ "success": bool, ...data }`

### Database
- [ ] Any operation touching the `amount` table uses a MySQL transaction (commit/rollback pattern)
- [ ] All SQL is in `dao.php` — no SQL strings in endpoint files
- [ ] All queries use prepared statements with bound parameters (no string interpolation of user input)

### PHP Conventions
- [ ] Every endpoint checks `$_SESSION["budget_auth"]` before doing anything
- [ ] JSON body parsed with `json_decode(file_get_contents("php://input"), true)`
- [ ] All input cast: `intval()` for IDs, `intval(floatval($val) * 100)` for money, `trim()` for strings
- [ ] Errors go to `error_log()` — no `echo` of debug output, no `var_dump`, no `print_r`

### JS Conventions
- [ ] No `console.log`, `debugger`, or commented-out debug code
- [ ] No direct `fetch()` calls in React components

### Security
- [ ] No raw user input interpolated into SQL (all bound via prepared statements)
- [ ] No sensitive data (credentials, tokens) echoed in responses or logged
- [ ] Auth check not bypassed or weakened in any endpoint

### Known Tech Debt Interactions
Flag (don't necessarily fix) if the change interacts with:
- [ ] `activeDialog` JSX-in-state pattern in `BudgetApp` (`App.js`)
- [ ] Prop drilling in `BudgetApp` (new features should use React context)
- [ ] Inverted filter logic (`filters` Set of hidden users)
- [ ] UTC-4 hardcoded in SQL queries

## Output Format

Organize findings as:

**Critical** — bugs, broken logic, security vulnerabilities
**Warning** — convention violations, missing checks, potential edge cases
**Suggestion** — tech debt opportunities, improvements within scope of the change
