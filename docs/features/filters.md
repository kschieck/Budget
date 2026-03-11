# Filters

Per-user checkboxes that control which transactions appear in the transaction list.

## What It Does

`FiltersSection` renders one checkbox per unique username found in the current month's transactions. Unchecking a user hides their transactions from `TransactionsSection`. The chart (`DrawdownChart`) is always unaffected by filters and shows all transactions.

## Key Files

- `client/src/App.js` — owns `filters` state and `filteredTransactions` derived value
- `client/src/Filters.js` — `FiltersSection` component and checkbox rendering

## State Shape

```
filters: Set<string> | null
```

- `null` — not yet initialized (treated as "show all" in derived value)
- `Set<string>` — set of usernames that are currently visible (checked)

`filteredTransactions` is derived in `BudgetApp`:

```js
filters === null ? transactions : transactions.filter(t => filters.has(t.user))
```

## Initialization and Reset

A `useEffect` watching `[filters, transactions]` initializes `filters` whenever it is `null` and `transactions` has loaded. Initialization sets `filters` to a `Set` containing every distinct username from `transactions`.

`filters` is reset to `null` when `monthOffset` changes (month navigation). This ensures all users start checked when the new month's transactions load.

Filter state is preserved across add, edit, and delete operations within the same month because those operations call `loadTransactions()` (which updates `transactions`) without touching `filters`.

## Checkbox Behavior

Each checkbox in `FiltersSection` maps to a username. The `checked` prop reflects whether that username is in the `filters` Set:

```js
checked={filters ? filters.has(name) : true}
```

When a checkbox is toggled, `changeFilterState(name, checked)` in `BudgetApp` adds the username to `filters` (show) or removes it (hide).

## Design Notes

- The chart intentionally ignores filters so the spending curve always reflects real totals.
- Using `null` as the uninitialized sentinel (rather than an empty Set) lets the derived value fall back to showing all transactions before the effect fires, avoiding a flash of an empty list on load.
