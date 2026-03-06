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

- Do NOT store JSX in `activeDialog` state (that is tech debt)
- Use a boolean state flag per dialog: `const [showAddTx, setShowAddTx] = useState(false)`
- Render dialogs conditionally: `{showAddTx && <AddEditTransactionDialog ... />}`
- Use native `<dialog>` with `ref.current.showModal()` in a `useEffect([], [])`

## Data Loading

- After any mutation, reload affected data by calling the appropriate load function
- Load functions (`loadTransactions`, `loadGoals`, `loadAmountTotal`) are defined in `BudgetApp` and passed as callbacks — call them on successful API response

## Amounts

- State and API always use integers in cents
- Divide by 100 only at the call site before passing to display utilities: `toDollars(amount / 100)`
- Never store dollars in state — always cents

## Filters

- Filter state must be a Set of **visible** usernames
- `filters.size === 0` means "show all users" (no filter active)
- `filters.has(username)` means "show this user's transactions"
- The current inverted implementation (hidden Set) is tech debt — fix when touching filter code
