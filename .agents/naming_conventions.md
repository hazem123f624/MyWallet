# Laravel Naming Conventions

> Based on **PSR-1**, **PSR-4**, and the **Laravel community standard**.
> Read this before creating any new file in this project.

---

## General Rules

| Rule | Standard |
|---|---|
| PHP class name must match its filename exactly | PSR-1 |
| Namespace must map 1-to-1 to the directory path | PSR-4 |
| All class names use **PascalCase** (StudlyCaps) | PSR-1 |
| All method and variable names use **camelCase** | PSR-1 |
| Never abbreviate unless universally understood (`id`, `url`, `dto`) | Laravel community |

---

## Classes and Files

### Controllers

**Pattern:** `{Noun}Controller` — singular noun, no plural
**Lookup / read-only variants:** `{Noun}LookupController`

```
WalletController.php          ✅
TransferController.php        ✅
WalletLookupController.php    ✅

WalletsController.php         ❌  (plural)
ManageTransferController.php  ❌  (verb prefix)
wallet_controller.php         ❌  (snake_case)
```

---

### Models

**Pattern:** Singular PascalCase noun — no suffix

```
Wallet.php              ✅
SubWallet.php           ✅
Transfer.php            ✅
Beneficiary.php         ✅
BankAccount.php         ✅

Wallets.php             ❌  (plural)
WalletModel.php         ❌  (redundant suffix)
```

---

### Form Requests

**Pattern:** `{Verb}{Noun}Request` or `{Noun}{Verb}Request`
Laravel's `make:request` defaults to `{Verb}{Noun}Request` (e.g., `StoreWalletRequest`), but grouping by noun (e.g., `WalletStoreRequest`) is also popular for IDE sorting.

```
StoreWalletRequest.php          ✅
UpdateWalletRequest.php         ✅
ConfirmTransferRequest.php      ✅

WalletRequest.php               ❌  (too vague)
TransferFormRequest.php         ❌  (redundant Form suffix)
```

---

### API Resources

**Pattern:** `{Noun}Resource`

```
WalletResource.php              ✅
SubWalletResource.php           ✅
TransferResource.php            ✅
BeneficiaryResource.php         ✅

WalletResourceCollection.php    ❌  (use Resource::collection() instead)
```

---

### Repositories

**Pattern:** `{Noun}Repository`

```
WalletRepository.php          ✅
TransferRepository.php        ✅
BankAccountRepository.php     ✅

WalletsRepository.php         ❌  (plural)
TransferRepo.php              ❌  (abbreviation)
```

---

### Interfaces (Contracts)

**Pattern:** `{Noun}Interface` or `{Noun}Contract`
Both the PSR/Symfony `Interface` suffix and the Laravel `Contract` terminology are fully acceptable.

```
TransferInterface.php     ✅
WalletContract.php        ✅
BankingProvider.php       ✅  (if placed in App\Contracts)

IWallet.php               ❌  (Hungarian notation)
```

---

### Enums

**Pattern:** `{Noun}{Descriptor}` — **NO `Enum` suffix**

The class is already a PHP enum type. Adding `Enum` suffix is as redundant as writing `WalletModel`.

```
TransferStatus.php              ✅
WalletStatus.php                ✅
WalletColor.php                 ✅
BeneficiaryType.php             ✅

TransferStatusEnum.php          ❌
```

**Enum cases** → `PascalCase` or `UPPER_SNAKE_CASE`:

```php
case Pending   = 'pending';    // ✅ Modern PHP 8.1+ standard
case PENDING   = 'pending';    // ✅ Also acceptable
```

---

### DTOs

**Pattern:** `{Noun}DTO`

```
WalletDTO.php          ✅
SubWalletDTO.php       ✅
TransferDTO.php        ✅

WalletData.php         ❌
TransferPayload.php    ❌
```

---

### Policies

**Pattern:** `{Noun}Policy`

```
WalletPolicy.php        ✅
SubWalletPolicy.php     ✅

WalletsPolicy.php       ❌  (plural)
WalletAuthPolicy.php    ❌  (redundant Auth)
```

---

### Observers

**Pattern:** `{Noun}Observer`

```
WalletObserver.php     ✅
TransferObserver.php   ✅
```

---

### Traits

**Pattern:** Describe capabilities or behaviors (`Has...`, `InteractsWith...`, `Is...`).
Avoid the redundant `Trait` suffix where possible, matching Laravel core traits (`Notifiable`, `SoftDeletes`).

```
HasTranslation.php                  ✅
InteractsWithWallets.php            ✅

WalletTrait.php                     ❌  (redundant suffix)
DatabaseStatementsInitTrait.php     ❌  (redundant suffix)
```

---

### Providers

**Pattern:** `{Module}ServiceProvider`

```
WalletsServiceProvider.php    ✅
UsersServiceProvider.php      ✅
```

---

### Middleware

**Pattern:** Descriptive imperative phrase — no `Middleware` suffix

```
EnsureWalletOwner.php        ✅
CheckAccountStatus.php       ✅

WalletOwnerMiddleware.php    ❌  (redundant suffix)
```

---

### Migrations

**Pattern:** `YYYY_MM_DD_HHMMSS_{verb}_{description}.php`

| Operation | Verb |
|---|---|
| New table | `create_{table}_table` |
| Add columns | `add_{column}_to_{table}` |
| Rename / alter | `change_{what}_in_{table}` |
| Drop column | `drop_{column}_from_{table}` |
| Add index | `add_{index}_to_{table}` |

```
2026_05_24_081119_create_wallets_table.php                          ✅
2026_06_02_130030_add_beneficiary_to_transfers_table.php            ✅
2026_08_02_000001_change_wallet_colors_to_json.php                  ✅
2026_09_21_000001_add_status_to_transfers_table.php                 ✅

create_wallets.php           ❌  (missing timestamp)
wallets_migration.php        ❌  (no verb, no timestamp)
```

---

### Factories and Seeders

```
WalletFactory.php       ✅
TransferFactory.php     ✅
WalletSeeder.php        ✅
DatabaseSeeder.php      ✅
```

---

### Tests

**Unit / integration:** `{TestedClass}Test`
**Feature (HTTP):** `{Verb}{Noun}Test`

```
TransferRepositoryTest.php    ✅
WalletControllerTest.php      ✅
CreateTransferTest.php        ✅
ConfirmTransferTest.php       ✅
```

---

## Database

### Tables → plural `snake_case`

```
wallets
sub_wallets
transfers
beneficiaries
bank_accounts
```

### Columns → `snake_case`

```
wallet_id
sub_wallet_id
beneficiary_id
amount
reserved_amount
paid_at
created_at
```

Foreign keys: `{singular_table}_id` → `wallet_id`, `sub_wallet_id`, `beneficiary_id`

---

## Routes

URL segments: `kebab-case`, plural nouns

```
/wallets
/wallets/{id}/sub-wallets
/sub-wallets/{id}/transfers
/transfers
```

Route names: `dot.notation` with module prefix

```
wallet.transfers.store
sub-wallet.index
transfers.lookup
```

---

## Variables and Methods

| Context | Convention | Example |
|---|---|---|
| Local variable | `camelCase` | `$walletId`, `$subWallet` |
| Method name | `camelCase` | `confirmTransfer()`, `findPrimaryAccount()` |
| Boolean variable | `is/has/can` prefix | `$isReserved`, `hasSufficientBalance()` |
| Config keys | `snake_case` | `wallet.max_sub_wallets` |

---

## Quick-Reference Cheat Sheet

| File Type | Pattern | Example |
|---|---|---|
| Controller | `{Noun}Controller` | `TransferController.php` |
| Lookup Controller | `{Noun}LookupController` | `WalletLookupController.php` |
| Model | `{Noun}` (singular) | `SubWallet.php` |
| Form Request | `{Verb}{Noun}Request` | `StoreTransferRequest.php` |
| API Resource | `{Noun}Resource` | `TransferResource.php` |
| Repository | `{Noun}Repository` | `TransferRepository.php` |
| Interface | `{Noun}Interface` or `{Noun}Contract` | `TransferInterface.php` |
| Enum | `{Noun}{Descriptor}` **(NO Enum suffix)** | `TransferStatus.php` |
| DTO | `{Noun}DTO` | `TransferDTO.php` |
| Policy | `{Noun}Policy` | `WalletPolicy.php` |
| Observer | `{Noun}Observer` | `TransferObserver.php` |
| Trait | Capability/Behavior (`Has...`) | `InteractsWithWallets.php` |
| Provider | `{Module}ServiceProvider` | `WalletsServiceProvider.php` |
| Middleware | Descriptive imperative | `EnsureWalletOwner.php` |
| Migration | `YYYY_MM_DD_{verb}_{desc}.php` | `2026_09_21_add_status_to_transfers.php` |
| Factory | `{Model}Factory` | `TransferFactory.php` |
| Seeder | `{Noun}Seeder` | `WalletSeeder.php` |
| Test | `{TestedClass}Test` | `TransferRepositoryTest.php` |
