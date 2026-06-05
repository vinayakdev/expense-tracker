# Expense Tracker

A production-ready personal finance tracker built with Laravel 13, Livewire 4, and Flux UI.

## Stack

- **Backend:** PHP 8.4, Laravel 13, Fortify (auth)
- **Frontend:** Livewire 4, Flux UI 2, Tailwind CSS 4
- **Testing:** Pest 4
- **Local dev:** Laravel Herd

## Data Model

| Model | Key Fields |
|---|---|
| `User` | name, email, password, reporting_currency (`CHAR(3)`, default `INR`) |
| `Account` | user_id, name, currency (`CHAR(3)`), balance (`DECIMAL(19,4)`) |
| `Category` | user_id, name, type (`expense`\|`income`), color, icon |
| `Transaction` | account_id, category_id, type, amount (`DECIMAL(19,4)`), base_amount (`DECIMAL(19,4)`), exchange_rate (`DECIMAL(19,6)`), description, transacted_at, is_template, recurrence |
| `Budget` | user_id, category_id, month, amount (`DECIMAL(19,4)`) |

**Key relationships:**
- A `User` has many `Accounts`, each with its own base currency (e.g. "HDFC – INR", "Wise – USD")
- A `Transaction` belongs to an `Account` — currency is inherited from the account, not stored per row
- `amount` is always in the account's currency; `base_amount` is pre-converted to the user's `reporting_currency` at entry time using the rate stored in `exchange_rate`
- Reports and dashboards always aggregate `base_amount` — no runtime conversion needed

## Feature Roadmap

| Phase | Features | Status |
|---|---|---|
| **1 — Foundation** | Auth, Categories CRUD, Transactions CRUD, list with filters (date, type, category) | 🔲 Planned |
| **2 — Dashboard** | Net balance, monthly totals, income vs expense summary, recent transactions | 🔲 Planned |
| **3 — Budgets** | Monthly budget per category, over-budget indicators | 🔲 Planned |
| **4 — Reports** | Yearly/monthly breakdown charts, CSV export | 🔲 On request |
| **5 — Advanced** | Live exchange rate API, recurring transactions automation | 🔲 On request |

## Account Setup & Constraints

### Registration Flow

During registration, after entering name/email/password, the user is shown a currency selection step using **radio buttons** (not a dropdown) — one click, no typing. The available currencies are pulled from `config/exchange_rates.php`.

A default account is auto-created on registration:

| Field | Default | Editable? |
|---|---|---|
| Account name | `Account 1` | Yes — **once only** |
| Base currency | User's selection | **Never** — immutable after creation |

### Rules

- **Name** — can be renamed one time after creation. After that the rename option is hidden. This prevents casual changes that could cause confusion in historical reports.
- **Currency** — locked permanently. If the user needs a different currency, they create a new account. Changing currency on an existing account would invalidate all stored `base_amount` and `exchange_rate` values.
- **Deletion** — an account can be deleted. Deleting an account permanently removes all transactions linked to it. The user must confirm before deletion. A user must always retain at least one account (the last account cannot be deleted).
- **Multiple accounts** — a user can create additional accounts at any time from their settings, each with its own name and currency, following the same rules.

### Data model addition

| Field | On | Purpose |
|---|---|---|
| `name_edited_at` (nullable timestamp) | `accounts` | Tracks whether the one-time rename has been used |

## Currency Formatting & Representation

Every amount in the app is a `Money` value object — never a raw float or string. This ensures formatting is consistent everywhere: transaction rows, summary cards, reports, exports.

### Architecture

```
Transaction::amount (DECIMAL in DB)
        │
        ▼
  MoneyCast (Eloquent cast)
        │  reads currency from account->currency
        ▼
  Money value object  ──→  Money::format()
        │                       │
        │               CurrencyFormatterService
        │                  (symbol, decimals, grouping)
        ▼
  ₹1,00,000.00  /  $1,000.00  /  ¥100,000
```

### `Money` Value Object (`App\ValueObjects\Money`)

Holds the raw amount and currency code. Exposes:
- `format()` — locale-aware display string (e.g. `₹1,00,000.00`)
- `raw()` — the original `DECIMAL(19,4)` value for math/storage
- `currency()` — the ISO 4217 code
- `convert(to)` — returns a new `Money` in the target currency via `ExchangeRateService`

### `MoneyCast` (`App\Casts\MoneyCast`)

Applied to `amount` and `base_amount` on the `Transaction` model:

```php
protected function casts(): array
{
    return [
        'amount'      => MoneyCast::class . ':account.currency',
        'base_amount' => MoneyCast::class . ':user.reporting_currency',
    ];
}
```

The cast resolves the currency from the related model at access time. Getting `$transaction->amount` always returns a `Money` object. Setting it accepts either a `Money` object or a raw numeric — both are stored as `DECIMAL(19,4)`.

### `CurrencyFormatterService` (`App\Services\CurrencyFormatterService`)

Reads formatting rules from config and applies them. Rules stored per currency:

```php
// config/currencies.php
return [
    'INR' => ['symbol' => '₹', 'decimals' => 2, 'grouping' => 'indian',  'position' => 'prefix'],
    'USD' => ['symbol' => '$', 'decimals' => 2, 'grouping' => 'western', 'position' => 'prefix'],
    'EUR' => ['symbol' => '€', 'decimals' => 2, 'grouping' => 'western', 'position' => 'prefix'],
    'JPY' => ['symbol' => '¥', 'decimals' => 0, 'grouping' => 'western', 'position' => 'prefix'],
    'KWD' => ['symbol' => 'KD','decimals' => 3, 'grouping' => 'western', 'position' => 'prefix'],
];
```

Indian grouping: `1,00,00,000` (thousands then lakhs/crores).
Western grouping: `1,000,000` (thousands throughout).

**Decimal suppression rule:** if the decimal portion is exactly zero, it is omitted from display.

| Raw value | Formatted |
|---|---|
| `1000.00` | `₹1,000` |
| `1000.50` | `₹1,000.50` |
| `1000.10` | `₹1,000.10` |

This applies to all currencies. JPY already has 0 decimal places and is unaffected.

### `ExchangeRateService` (`App\Services\ExchangeRateService`)

Reads rates from `config/exchange_rates.php`. Exposes `convert(amount, from, to)`. All rates are stored relative to INR. Converting USD → EUR goes USD → INR → EUR internally. Swapping to a live API later only touches this class.

> Both config files (`currencies.php` and `exchange_rates.php`) are the single source of truth. Adding a new currency means one entry in each — nothing else changes.

## Multi-Currency & Exchange Rates

### Account-Based Currency Separation

Each account has exactly one currency. This cleanly separates foreign currency transactions from base currency ones — no guessing which currency a transaction is in.

```
User (reporting_currency: INR)
 ├── Account: "HDFC Savings"   → INR
 ├── Account: "Wise"           → USD
 └── Account: "Revolut"        → EUR
```

When a user logs a transaction on their USD account, the form shows the exchange rate in use and stores:
- `amount` — what they actually spent in USD
- `exchange_rate` — the rate at time of entry
- `base_amount` — amount × rate, in INR (or whatever the reporting currency is)

### Hard-Coded Exchange Rate Service

Rates live in `config/exchange_rates.php` as a flat array relative to INR:

```php
// config/exchange_rates.php
return [
    'USD' => 83.50,
    'EUR' => 90.20,
    'GBP' => 105.80,
    'JPY' => 0.55,
    // ...
];
```

A dedicated `App\Services\ExchangeRateService` reads this config and exposes a single `convert(amount, from, to)` method. All conversion logic is isolated here — swapping in a live API later only requires changing this one class, nothing else in the app.

> **Why hard-coded?** Live rate APIs add external dependencies and failure points. Hard-coded rates are updated manually when needed, keeping the app self-contained and predictable for a personal finance tool.

## Smart Category UX

### Recency-Weighted Ranking

When a user opens the transaction form, categories are sorted by a live score computed from their transaction history — no stored counters needed.

| Window | Weight |
|---|---|
| Last 30 days | 3× |
| Last 31–60 days | 2× |
| Older than 60 days | 1× |

`score = (uses in last 30d × 3) + (uses in 31–60d × 2) + (older uses × 1)`

The most recently habitual category always floats to the top, automatically adjusting as habits change.

### Recurring Income & Quick Re-entry

Two mechanisms reduce friction for repeated transactions:

1. **Recurring templates** — any transaction (typically income) can be flagged as recurring (weekly / monthly / yearly). The app prompts the user to log it when due, but never auto-posts.
2. **Quick re-entry** — the transaction form surfaces the last 3 transactions of the same type as one-tap templates. Tapping one pre-fills all fields; the user only confirms or adjusts the date.

**Updated data model fields for this:**

| Field | On | Purpose |
|---|---|---|
| `is_template` (bool) | `transactions` | Marks a transaction as a recurring template |
| `recurrence` (nullable string) | `transactions` | Frequency: `weekly`, `monthly`, `yearly` |

Templates are just transactions with no `transacted_at` — cloned on use, no separate table needed.

## Seed Data

- Demo user: `demo@example.com` / `password`
- 5 years of realistic transactions: **Jan 2021 → Jun 2026**
- Covers both income (salary, freelance) and expenses (food, housing, transport, health, entertainment, shopping)
- Amounts vary seasonally to simulate real spending patterns

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

## Guidelines

- Every change is covered by a Pest feature test
- Livewire components stay thin — business logic lives in models/services
- All PHP formatted with Pint before commit
- No new base folders or dependencies without approval
