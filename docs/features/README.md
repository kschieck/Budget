# Feature Documentation

This directory contains per-feature documentation for the budget app.

## Structure

Each feature gets its own file named after the feature area. Update the relevant file when making significant changes to a feature.

## Features

| Feature | File | Description |
|---------|------|-------------|
| Transactions | (see data-flow.md) | Add, edit, delete, browse by month |
| Goals | (see data-flow.md) | Savings targets with contribution tracking |
| Charts | — | Canvas-based monthly drawdown chart |
| Filters | — | Per-user transaction visibility toggles |
| New Month Tool | — | Duplicate first-of-month transactions from previous month |
| Auth | — | Cookie / token / username+password auth flow |

## Adding Feature Docs

When adding a new feature or significantly changing an existing one:

1. Create `docs/features/<feature-name>.md`
2. Include: what the feature does, key files involved, data model changes, API changes, and any non-obvious design decisions
3. Link it from this README table above
