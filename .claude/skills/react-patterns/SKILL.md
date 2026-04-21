---
description: React component, state, API, and dialog patterns for the budget app. Loaded automatically when building or modifying React features.
user-invocable: false
---

# React Patterns

## Component Structure

- Functional components only, PascalCase names
- Internal sub-components (not exported): `TransactionRow`, `GoalRow`
- Named exports for dialogs: `export function AddEditTransactionDialog`
- Default export for section components: `export default function TransactionsSection`
- Keep components in the same file as their parent section (e.g., `GoalRow` lives in `Goals.js`)

## State Management

- `BudgetApp` in `App.js` owns all shared state: `transactions`, `goals`, `amountTotal`, `filters`, `monthOffset`
- Local UI state (toggle visibility, form inputs) lives in the component that needs it
- For new features, prefer React context over prop drilling — do not add more props to `BudgetApp`'s existing handler chain
- After any mutation, reload affected data: call `loadTransactions()`, `loadGoals()`, or `loadAmountTotal()` on success

## Dialogs

Use state flags and conditional rendering for dialogs:

```jsx
// Correct pattern
const [showAddTx, setShowAddTx] = useState(false);

// In render:
{showAddTx && (
  <AddEditTransactionDialog
    onCancel={() => setShowAddTx(false)}
    onSave={handleSave}
  />
)}
```

Use native `<dialog>` element with `showModal()` via `useEffect`:

```jsx
const dialogRef = useRef(null);
useEffect(() => {
  if (dialogRef.current && !dialogRef.current.open) {
    dialogRef.current.showModal();
  }
}, []);
return <dialog ref={dialogRef}>...</dialog>;
```

## API Calls

All `fetch()` calls live in `API.js`. Components import and call API functions:

```js
// In API.js — return a plain promise
export function saveTransaction(id, amount, description) {
  return fetch("./transaction.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ amount, description }),
  }).then((response) => response.json());
}

// In component — check result.success, alert on failure
API.saveTransaction(id, amount, description)
  .then((result) => {
    if (result.success) {
      loadTransactions();
    }
  })
  .catch((e) => {
    console.error(e);
    alert("Failed to save transaction");
  });
```

Never call `fetch()` directly in a component. Never add new endpoint files without a corresponding function in `API.js`.

## Amounts and Display

Amounts are **integers in cents** in state and API responses. Only convert at display time:

```js
toDollars(transaction.amount / 100)       // "$12.34"
toDollarsNoCents(goal.total / 100)        // "$1,200"
```

Do NOT divide by 100 inside utility functions — the caller always divides.

## Filters

Filter state is a Set of **visible** usernames. `filters.has(username)` means "show this user's transactions":

```js
transactions.filter((tx) => filters.size === 0 || filters.has(tx.user))
```

The existing implementation is inverted (tech debt) — fix it when touching filter logic.

## What to Avoid

- `var` — use `let` or `const`
- `console.log` or `debugger` in committed code
- Inline styles for layout when a CSS class exists in `styles.css`
- Storing computed values in state when they can be derived from existing state
