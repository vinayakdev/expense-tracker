# Database Structure

## Tables & Columns

```
database/
│
├── users
│   ├── id                      BIGINT UNSIGNED  PK
│   ├── name                    VARCHAR(255)
│   ├── email                   VARCHAR(255)     UNIQUE
│   ├── password                VARCHAR(255)
│   ├── reporting_currency      CHAR(3)          DEFAULT 'INR'
│   ├── email_verified_at       TIMESTAMP        NULLABLE
│   ├── remember_token          VARCHAR(100)     NULLABLE
│   ├── created_at              TIMESTAMP
│   └── updated_at              TIMESTAMP
│
├── accounts
│   ├── id                      BIGINT UNSIGNED  PK
│   ├── user_id                 BIGINT UNSIGNED  FK → users.id  CASCADE DELETE
│   ├── name                    VARCHAR(255)     DEFAULT 'Account 1'
│   ├── currency                CHAR(3)          IMMUTABLE after creation
│   ├── balance                 DECIMAL(19,4)    DEFAULT 0
│   ├── name_edited_at          TIMESTAMP        NULLABLE  (null = rename still available)
│   ├── created_at              TIMESTAMP
│   └── updated_at              TIMESTAMP
│
├── categories
│   ├── id                      BIGINT UNSIGNED  PK
│   ├── user_id                 BIGINT UNSIGNED  FK → users.id  CASCADE DELETE
│   ├── name                    VARCHAR(255)
│   ├── type                    ENUM(expense, income)
│   ├── color                   VARCHAR(7)       DEFAULT '#6366f1'
│   ├── icon                    VARCHAR(255)     NULLABLE
│   ├── created_at              TIMESTAMP
│   └── updated_at              TIMESTAMP
│
├── transactions
│   ├── id                      BIGINT UNSIGNED  PK
│   ├── account_id              BIGINT UNSIGNED  FK → accounts.id  CASCADE DELETE
│   ├── category_id             BIGINT UNSIGNED  FK → categories.id  CASCADE DELETE
│   ├── type                    ENUM(expense, income)
│   ├── amount                  DECIMAL(19,4)    in account's currency  [MoneyCast]
│   ├── base_amount             DECIMAL(19,4)    in user's reporting_currency  [MoneyCast]
│   ├── exchange_rate           DECIMAL(19,6)    rate at time of entry (1.0 if same currency)
│   ├── description             VARCHAR(255)     NULLABLE
│   ├── transacted_at           DATE             NULLABLE (null = template row)
│   ├── is_template             BOOLEAN          DEFAULT false
│   ├── recurrence              ENUM(weekly, monthly, yearly)  NULLABLE
│   ├── created_at              TIMESTAMP
│   └── updated_at              TIMESTAMP
│
└── budgets
    ├── id                      BIGINT UNSIGNED  PK
    ├── user_id                 BIGINT UNSIGNED  FK → users.id  CASCADE DELETE
    ├── category_id             BIGINT UNSIGNED  FK → categories.id  CASCADE DELETE
    ├── month                   DATE             first day of the month (e.g. 2026-06-01)
    ├── amount                  DECIMAL(19,4)    in user's reporting_currency
    ├── created_at              TIMESTAMP
    └── updated_at              TIMESTAMP
```

---

## Relationships

```
User
├── hasMany → Account          (one user, many currency accounts)
├── hasMany → Category         (categories are per user)
└── hasMany → Budget           (budgets are per user)

Account
├── belongsTo → User
└── hasMany → Transaction      (all transactions live under an account)

Category
├── belongsTo → User
├── hasMany → Transaction
└── hasMany → Budget

Transaction
├── belongsTo → Account        (currency context comes from here)
└── belongsTo → Category

Budget
├── belongsTo → User
└── belongsTo → Category
```

---

## Notes

- `transactions.amount` is always in the currency of its parent `account`
- `transactions.base_amount` is always in `users.reporting_currency` — pre-converted at entry time
- `transactions.exchange_rate` records the rate used so historical records stay accurate if config rates change
- `transactions` with `is_template = true` and `transacted_at = null` are recurring templates — never shown in transaction history, only used for quick re-entry
- `accounts.name_edited_at` being `null` means the rename has not been used yet; once set, the rename option is hidden
- Deleting a `User` cascades to `Accounts → Transactions` and `Categories → Transactions`
- A user must always retain at least one account (enforced at application level)
- `budgets.amount` is stored in reporting currency to keep budget comparisons simple
