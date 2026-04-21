---
paths:
  - "client/src/**/*.js"
---

# React Conventions

## Components

- Functional components only — no class components
- One default export per file (the section component); named exports for dialogs and sub-components
- Keep internal components (e.g., `TransactionRow`) in the same file as their parent section

## State

- Shared state lives in `BudgetApp` — don't lift state higher than `App.js`
- Local UI state (toggle, form inputs) stays in the component that needs it
- For new features requiring shared state, use React context — do not extend the prop chain in `BudgetApp`
- Never store derived values in state: compute from existing state instead

## Dialogs

- Use a boolean state flag per dialog: `const [showAddTx, setShowAddTx] = useState(false)`
- Render dialogs conditionally: `{showAddTx && <AddEditTransactionDialog ... />}`
- Use native `<dialog>` with `ref.current.showModal()` in a `useEffect([], [])`

## Amounts

- State and API always use integers in cents
- Divide by 100 only at the call site before passing to display utilities: `toDollars(amount / 100)`
- Never store dollars in state — always cents

## Filters

- Filter state (`filters`) is a Set of **visible** usernames, or `null` when not yet initialized
- `null` means "loading / not yet initialized" — treat as show all
- `filters.has(username)` means "show this user's transactions"
- An empty Set means all users are hidden — show no transactions
- Apply filters: `filters === null ? transactions : transactions.filter(t => filters.has(t.user))`
- `filters` is reset to `null` on month change and initialized to all current users once transactions load
