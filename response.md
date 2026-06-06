# Interview Presentation Notes — Expense Tracker

---

## 1. Stack & Setup

| Layer       | Choice                             |
| ----------- | ---------------------------------- |
| Framework   | Laravel 13 + PHP 8.4               |
| App Panel   | Filament v5                        |
| Reactive UI | Livewire v4                        |
| Auth        | Laravel built-in (`Auth::attempt`) |
| Styling     | Tailwind v4 + Flux UI              |

---

## 2. User Management

**Registration flow:**

- `AuthPage` Livewire component (`/`) handles both login and register on one page
- `register()` validates name, email (unique), password (confirmed + `Password::defaults()`) → creates `User` → logs in → redirects
- `login()` uses `Auth::attempt()` with rate-limiter awareness, regenerates session

**Post-registration — Account setup:**

- `UserObserver::created()` fires on every new user → seeds **34 default categories** (26 expense + 8 income) from `CategorySeeder::definitions()`
- `EnsureUserHasAccount` middleware: if user has no `Account`, redirects to `/setup`
- `CreateAccount` Livewire component collects account name + currency (3-char ISO code) → creates the `Account` → redirects to dashboard

**Tenant model:**

- `User` implements Filament's `HasTenants` / `HasDefaultTenant` — the `Account` is the tenant
- All Filament routes are scoped under `/{tenant}/...`, meaning data is isolated per account

---

## 3. Database Schema

```
users            id, name, email, password, reporting_currency, soft_deletes
accounts         id (UUID), user_id, name, currency, soft_deletes
categories       id, user_id, name, type (expense|income), color, icon, soft_deletes
transactions     id, account_id (UUID FK), category_id (nullable), type, amount (decimal 19,4),
                 description, transacted_at (date), recurrence (weekly|monthly|yearly), soft_deletes
budgets          id, user_id, account_id, category_id, amount (decimal 19,4), soft_deletes
```

**Design decisions worth mentioning:**

- `Account` uses UUID primary key (`HasUuids`) — avoids sequential ID enumeration in URLs
- `amount` stored as `decimal(19,4)` — avoids float precision issues
- `category_id` is nullable in transactions — allows uncategorized entries
- All models use **SoftDeletes**
- Indexes added on `transactions` for `account_id`, `transacted_at`, `type` — report queries are efficient
- Categories are **per-user**, not global — users can customize their own

---

## 4. Expense (Transaction) Management

**CRUD via Filament Resource:**

- `TransactionResource` → Create / Edit / List pages
- Edit opens in a **slideOver panel** (no page navigation)
- Table: sorted by date desc, grouped by date, filterable by type and category, searchable

**Form fields:**

- `type` — ToggleButtons (Expense / Income), live-reactive, resets category on change
- `category_id` — Select with custom HTML rendering (emoji icon + color dot), grouped into **"Top Picks"** (scored by recent usage in last 30/60 days) and full list
- `amount` — numeric, prefixed with account currency symbol
- `transacted_at` — DatePicker, defaults to today
- `description` — optional text

**Smart category ranking** (interview talking point):

```php
SUM(CASE WHEN transacted_at >= 30 days ago THEN 3
         WHEN transacted_at >= 60 days ago THEN 2
         ELSE 1 END) as score
```

Top 5 scoring categories appear as "Top Picks" — makes the form faster for repeat users.

**Quick-add route:**

- `GET /accounts/{account}/transactions/create` → `CreateTransaction` Livewire component (standalone page outside Filament panel)

---

## 5. Reporting

**MonthlyOverviewWidget** (Dashboard):

- Shows total expenses, total income, net balance for selected month
- `getAverageDailyExpense()` = total expenses ÷ days in month
- `getExpensesByCategory()` → grouped with % breakdown and color bars
- `getTopExpense()` → single highest transaction
- `getRecentTransactions()` → last 3 entries with "X more" count
- Month navigation (previous/next) via Livewire actions, period synced to URL (`#[Url]` attribute)

**MonthlyTransactionSummary widget** (on Transaction list page):

- Shows a 6-month window of expense bars with relative % heights
- Paginated in 6-month chunks (older/newer navigation)
- Clicking a month selects it and dispatches `transaction-month-selected` event to other components
- `selectedMonthInsights()`: count, highest single expense, top category for that month
- DB-driver-aware `monthExpression()` — works on MySQL, PostgreSQL, SQLite, SQL Server

**Total expenses per category** — covered both in `getExpensesByCategory()` (current month) and via the Category filter on the transactions table

---

## 6. Beyond Requirements

| Feature                     | Detail                                                                                                         |
| --------------------------- | -------------------------------------------------------------------------------------------------------------- |
| **Budget management**       | `Budget` model per category/account; `budget-meter` component shown on transaction form when adding an expense |
| **Soft deletes everywhere** | Data recovery is possible                                                                                      |
| **UUID accounts**           | Secure, non-enumerable tenant IDs in URLs                                                                      |
| **Observer pattern**        | Auto-seeds categories on user creation — zero manual setup                                                     |
| **Middleware guard**        | Forces account setup before app access                                                                         |

---
