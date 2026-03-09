# Frontend Styles

## Overview

The app uses a single stylesheet at `client/src/styles.css`. The design is mobile-first with a fixed maximum content width of 480 px. The typeface is Nunito (400, 600, 700), loaded from Google Fonts in `client/public/index.html`.

## Design Tokens

All visual values are defined as CSS custom properties on `:root`. Always use these tokens rather than hard-coded values.

### Color

| Token | Value | Purpose |
|---|---|---|
| `--color-bg` | `#f9f5f0` | Page background (warm cream) |
| `--color-surface` | `#ffffff` | Card and dialog background |
| `--color-surface-alt` | `#fdf9f5` | Subtle alternate surface (table headers, input backgrounds) |
| `--color-border` | `#e8ddd3` | Standard divider |
| `--color-border-light` | `#f0e8e0` | Lighter row divider |
| `--color-text` | `#2c2420` | Primary text |
| `--color-text-secondary` | `#7a6860` | Muted labels, subtext |
| `--color-text-disabled` | `#b8a89e` | Disabled inputs, placeholder |
| `--color-accent` | `#c17f52` | Primary accent (terracotta) |
| `--color-accent-dark` | `#a5673e` | Hover state for accent |
| `--color-accent-light` | `#f2e4d6` | Accent tint (icon button resting state) |
| `--color-positive` | `#5c8f6a` | Income / positive amounts |
| `--color-positive-light` | `#dff0e5` | Positive tint |
| `--color-negative` | `#c25e4e` | Spending / negative amounts |
| `--color-negative-light` | `#fbe8e5` | Negative tint (delete button resting state) |
| `--color-progress-bg` | `#eedfd2` | Goal progress bar track |
| `--color-progress-fill` | `#c17f52` | Goal progress bar fill |

### Spacing

| Token | Value |
|---|---|
| `--space-xs` | 4 px |
| `--space-sm` | 8 px |
| `--space-md` | 16 px |
| `--space-lg` | 24 px |
| `--space-xl` | 32 px |

### Shape

| Token | Value |
|---|---|
| `--radius-sm` | 6 px |
| `--radius-md` | 12 px |
| `--radius-lg` | 18 px |
| `--radius-full` | 9999 px (pill / circle) |

### Shadow

| Token | Usage |
|---|---|
| `--shadow-card` | Section cards |
| `--shadow-dialog` | Modal dialogs |

### Typography

| Token | Value |
|---|---|
| `--font-family` | `'Nunito', system-ui, -apple-system, sans-serif` |
| `--font-size-sm` | 0.8125 rem |
| `--font-size-base` | 1 rem |
| `--font-size-lg` | 1.125 rem |
| `--font-size-xl` | 1.5 rem |
| `--font-size-2xl` | 2 rem |

## Layout

`#root` is constrained to `max-width: 480px` and centered with `margin: 0 auto`. Content stacks vertically; each section (month selector, transactions, goals, filters, chart) is styled as a card with `--shadow-card` and `--radius-lg`.

## Button System

| Class | Size | Shape | Use |
|---|---|---|---|
| `btn-icon` | 36 × 36 px | Circle (`--radius-full`) | Nav arrows (‹ ›), add (+), contribute (+) |
| `btn-icon-sm` | 26 × 26 px | Circle (`--radius-full`) | Delete (✕) |
| `btn-primary` | auto | Pill | Dialog save action |
| `btn-ghost` | auto | Pill | Dialog cancel action |

Resting / hover behavior:
- `btn-icon`: rests on `--color-accent-light`, fills with `--color-accent` on hover.
- `btn-icon-sm`: rests on `--color-negative-light`, fills with `--color-negative` on hover.
- All buttons have a subtle `scale(0.96)` on `:active`.

## Section Cards

Sections are built from two adjacent elements that share a card shadow:

- **Header** (`#tx_title`, `#goal_title`, or a generic `h1`): top-rounded card with `border-bottom`.
- **Body** (the `<table>` immediately following): bottom-rounded card, no top radius.

This creates a single visual card per section despite the header and body being separate DOM elements.

## Dialogs

`<dialog>` elements are styled as centered floating cards (`max-width: 400px`, `--shadow-dialog`, `--radius-lg`). Inputs inside dialogs have `1.5px` borders that highlight with `--color-accent` on focus. The `::backdrop` is a semi-transparent warm dark overlay with `backdrop-filter: blur(2px)`.

## DrawdownChart Colors

The canvas chart in `Charts.js` uses the following hard-coded colors (not CSS tokens, since canvas cannot read CSS variables):

| Element | Color |
|---|---|
| Zero-line stroke | `#c4afa5` |
| Chart line stroke | `#7a6860` |
| Positive fill (above zero) | `#c8e6d0` |
| Negative fill (below zero) | `#f5c5be` |
| Amount label text | `#2c2420` |
| Amount label font | `600 14px 'Nunito', sans-serif` |

These colors are aligned with the warm palette of the CSS token system.

## Google Fonts

Nunito is loaded in `client/public/index.html` via two `<link rel="preconnect">` tags and a stylesheet link:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
```

The font is referenced in `--font-family` and also hard-coded in the canvas chart font string, so both must be kept in sync if the typeface ever changes.
