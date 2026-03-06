---
paths:
  - "client/src/**/*.js"
  - "server/**/*.php"
---

# Code Style

## JavaScript (enforced by Prettier)

- 4-space indentation, spaces not tabs
- Max line length: 80 characters
- Double quotes for JSX attributes and strings (Prettier default)
- `let` and `const` only — never `var`
- No trailing semicolons are NOT enforced — Prettier handles this

## PHP

- 4-space indentation, spaces not tabs
- Opening `<?php` on the first line, closing `?>` on the last line
- Single quotes for PHP string literals (e.g., `"success"` keys in `json_encode` use double quotes as PHP convention, but variable-free strings use single quotes)
- PascalCase for none — functions are camelCase (e.g., `addTransaction`, `loadGoals`)
- No inline HTML — PHP files are pure logic, output only via `echo json_encode(...)`

## Both

- No debug statements: no `console.log`, `debugger`, `var_dump`, `print_r` — `console.error(e)` in `.catch()` handlers is allowed for error logging
- Remove dead code rather than commenting it out
- No TODO comments in committed code — create a task instead
