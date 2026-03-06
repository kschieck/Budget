# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A personal budgeting web app with a React frontend and a PHP backend, deployed together as a zip artifact to a shared hosting environment.

## Commands

### Development (client)
```bash
cd client && npm start        # Dev server with proxy to production backend
cd client && npm run build    # Production build
cd client && npm test         # Run tests
```

### Build & Deploy
```bash
npm run build          # Full build: React build + PHP copy → deployment.zip
npm run build-no-zip   # Build without zipping (outputs to build/public/)
npm run prettier       # Format client JS files
```

The `npm run build` (gulp default) pipeline: cleans → builds React → copies to `build/public/` → copies PHP server files → zips → cleans build folder.

## Architecture

### Client (`client/`)
React 19 SPA (Create React App). Key files:
- `src/App.js` — Root component. Handles auth flow (cookie → localStorage token → login form), then renders `BudgetApp` which owns all state (transactions, goals, monthOffset, filters, activeDialog).
- `src/API.js` — All fetch calls to PHP endpoints. No library, plain fetch returning JSON promises.
- `src/Transactions.js`, `src/Goals.js`, `src/Charts.js`, `src/Filters.js`, `src/NewMonthTool.js` — Feature sections rendered by `BudgetApp`.
- `src/Utils.js` — Shared utilities (currency formatting, cookie parsing).

The client proxies API calls to `https://stratfordtreasurehunt.com/budget` in development (configured in `client/package.json`).

### Server (`server/`)
Plain PHP files, each acting as a REST endpoint:
- `auth.php` — Auth via username/password (POST) or remember-me cookie/token. Uses PHP sessions + HMAC-signed cookies + DB-stored tokens.
- `transaction.php` — CRUD for transactions (GET, POST, PUT, DELETE).
- `transaction-duplicate.php` — Bulk-duplicate transactions from the previous month.
- `amount.php` — Returns the current budget balance.
- `goal.php` — CRUD for savings goals.
- `dao.php` — All MySQL access via prepared statements (`mysqli`). Key data operations use transactions to keep the `amount` table in sync with `transactions`.
- `config.php` — DB credentials, secret key, and user list (copy from `config.php.template`).

### Data model
All monetary values are stored and transmitted as **integers in cents**.

- `amount` table: single row holding the current budget balance.
- `transactions`: each spend/earn record. Adding a transaction decrements `amount`; deleting reverses it. Soft-deleted via `active = 0`.
- `goals`: savings goals with a `total` (target) and `amount` (contributed so far).
- `user_tokens`: remember-me tokens with expiry.

All date queries apply a `DATE_SUB(date_added, INTERVAL 4 HOUR)` offset (UTC-4 timezone adjustment).

### Auth flow
1. On page load, client checks for a `rememberme` cookie → calls `auth.php` (POST, no body).
2. Falls back to a token in `localStorage` → POSTs `{ rememberme: token }`.
3. Falls back to the login form → POSTs `{ username, password }`.
4. On success, a signed cookie is set server-side and the base64 token is returned for localStorage storage.

### Deployment
The build outputs to `build/public/` with PHP and React build artifacts co-located. The React app's `homepage: "./"` ensures relative asset paths work when served from a subdirectory.

## Configuration

Copy `server/config.php.template` to `server/config.php` and fill in:
- MySQL credentials (`$servername`, `$username`, `$password`, `$dbname`)
- `$secretkey` — used for HMAC signing of remember-me cookies
- `$authusers` — associative array of `username => crypt()-hashed password`

Database schema is in `scripts/database.sql`.
