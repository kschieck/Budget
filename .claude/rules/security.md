# Security

Always loaded. No exceptions.

## Authentication

- Every PHP endpoint must check `$_SESSION["budget_auth"]` before doing anything else
- If the session is not set: `echo json_encode(["success" => false]); exit(1);`
- Never skip, comment out, or bypass this check
- The `auth.php` endpoint is the only file that does not check the session (it establishes it)

## SQL Injection

- All database queries must use prepared statements with bound parameters
- Never interpolate user-controlled values into SQL strings
- All DB access goes through `dao.php` functions — the functions there enforce this
- The only exception is `LIMIT` with a pre-cast `intval()` value (see `loadGoals()` in `dao.php`)

## Input Validation

- Cast all user input before use: `intval()` for IDs, `intval(floatval($val) * 100)` for money, `trim()` for strings
- Reject zero amounts and empty strings early — return `["success" => false]`
- String fields are truncated at the DB layer (`substr($desc, 0, 64)`) — do not rely on this as validation
- Never echo back raw user input in PHP responses

## Secrets and Credentials

- `server/config.php` is not committed — contains DB credentials and `$secretkey`
- Never hardcode credentials, tokens, or secret keys in committed code
- The remember-me token is HMAC-signed using `$secretkey` — do not weaken this mechanism

## Client Side

- No sensitive data stored in localStorage beyond the opaque remember-me token
- The `rememberme` cookie is set `HttpOnly` and `Secure` by the server
- Never store passwords or session tokens client-side beyond what `auth.php` provides
