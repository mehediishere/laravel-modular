# mehediishere/laravel-modular

A native, zero-dependency modular architecture package for Laravel Modular systems.

No magic traits. No JSON state files. Just pure Laravel.

---

## Features

- **Module discovery** — enable/disable modules from a single config file
- **Zero dependencies** — built entirely on Laravel's own service container, routing, and filesystem
- **Sidebar management with group merging** — modules declare a `group_id`; any modules sharing the same `group_id` are automatically merged into one dropdown in the admin panel
- **Permission-filtered sidebar** — items invisible to the current user are stripped before rendering
- **Artisan scaffolding** — `php artisan module:make POS` generates the full folder structure with stubs
- **Publishable stubs** — customise the scaffolding output to match your team's conventions
- **Per-module config, migrations, views, routes, translations, and commands** — all self-registering
- **Sidebar caching** — per-user cache with configurable TTL
- **Laravel auto-discovery** — no manual provider registration needed

---

## Requirements

| Dependency | Version           |
|------------|-------------------|
| PHP        | ^8.2              |
| Laravel    | ^11.0 or ^12.0    |

---

## Installation

```bash
composer require mehediishere/laravel-modular
```

Laravel's package auto-discovery registers the service provider automatically.

Publish the config files:

```bash
php artisan vendor:publish --tag=modular-config
```

This creates two files in your project:

- `config/modular.php` — package settings (sidebar cache, TTL)
- `config/modules.php` — your enabled modules list and base path

Add the `Modules` namespace to your project's `composer.json`:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\": "Modules/"
    }
}
```

Then run `composer dump-autoload`.

---

## Quick start

### 1. Scaffold a module

```bash
php artisan module:make POS
php artisan module:make Ecommerce
php artisan module:make Account
php artisan module:make Payroll
```

Each command creates a full module folder at `Modules/{Name}/`:

```
Modules/POS/
├── app/
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   ├── Models/
│   ├── Services/
│   ├── Contracts/
│   ├── Providers/
│   │   └── POSServiceProvider.php
│   ├── Console/Commands/
│   ├── Events/
│   └── Listeners/
├── config/
│   ├── config.php
│   └── sidebar.php           ← define group_id here
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/views/
├── resources/lang/en/
├── routes/
│   ├── web.php
│   └── api.php
└── tests/
    ├── Feature/
    ├── Unit/
    └── TestCase.php
```

### 2. Enable the module

Open `config/modules.php` and add your module:

```php
'enabled' => [
    'POS',
    'Ecommerce',
    'Account',
    'Payroll',
],
```

### 3. Autoload and migrate

```bash
composer dump-autoload
php artisan migrate
```

---

## Sidebar management

### The `group_id` concept

The sidebar is built from each module's `config/sidebar.php`. The key field is `group_id` — a short snake_case string that identifies which dropdown group this module's items belong to.

**Modules with the same `group_id` are merged into one dropdown.**

This means you can have `Account` and `Payroll` as separate modules but group them both under a single "Finance" dropdown in the sidebar — neither module needs to know about the other.

```
Account module           Payroll module
group_id: 'finance'  +  group_id: 'finance'
─────────────────────────────────────────────
                 Merged result
                 ▼ Finance            ← one dropdown
                   Chart of Accounts  ← from Account
                   Journal Entries    ← from Account
                   Payroll Runs       ← from Payroll
                   Tax Reports        ← from Payroll
```

### Sidebar config schema

```php
<?php
// Modules/Account/config/sidebar.php

return [
    'group_id' => 'finance',      // shared with Payroll — they merge into one dropdown
    'group'    => 'Finance',      // dropdown label (use the same across sharing modules)
    'icon'     => 'bar-chart',    // group header icon
    'order'    => 20,             // sidebar position (lower = higher)

    'items' => [
        [
            'label'      => 'Chart of Accounts',
            'route'      => 'account.coa.index',
            'icon'       => 'list',
            'order'      => 1,
            'permission' => 'account.coa.view',  // Laravel Gate ability; empty = all users
        ],
        [
            'label'      => 'Journal Entries',
            'route'      => 'account.journal.index',
            'icon'       => 'book',
            'order'      => 2,
            'permission' => 'account.journal.view',
        ],
    ],
];
```

```php
<?php
// Modules/Payroll/config/sidebar.php

return [
    'group_id' => 'finance',      // same group_id → items merge with Account above
    'group'    => 'Finance',
    'icon'     => 'bar-chart',
    'order'    => 20,

    'items' => [
        [
            'label'      => 'Payroll Runs',
            'route'      => 'payroll.runs.index',
            'icon'       => 'dollar-sign',
            'order'      => 3,
            'permission' => 'payroll.runs.view',
        ],
        [
            'label'      => 'Tax Reports',
            'route'      => 'payroll.tax.index',
            'icon'       => 'file-text',
            'order'      => 4,
            'permission' => 'payroll.tax.view',
        ],
    ],
];
```

### Standalone groups

If a module should appear as its own top-level dropdown with no sharing, just use its own unique `group_id`:

```php
return [
    'group_id' => 'pos',          // unique — no other module uses this
    'group'    => 'Point of Sale',
    'icon'     => 'shopping-cart',
    'order'    => 10,
    'items'    => [...],
];
```

### Using the sidebar in your admin layout

```blade
@php
    $sidebarGroups = app(\Mehediishere\LaravelModular\Services\SidebarManager::class)->build();
@endphp

@foreach($sidebarGroups as $group)
    <div class="sidebar-group" data-group="{{ $group['group_id'] }}">

        <button class="sidebar-group-toggle">
            {{ $group['group'] }}
        </button>

        <div id="sidebar-group-{{ $group['group_id'] }}">
            @foreach($group['items'] as $item)
                <a href="{{ route($item['route']) }}"
                   class="{{ request()->routeIs($item['route'] . '*') ? 'active' : '' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

    </div>
@endforeach
```

Or publish and use the bundled layout:

```bash
php artisan vendor:publish --tag=modular-views
```

### The built sidebar array structure

```php
[
    [
        'group_id' => 'finance',
        'group'    => 'Finance',
        'icon'     => 'bar-chart',
        'order'    => 20,
        'items'    => [
            ['label' => 'Chart of Accounts', 'route' => 'account.coa.index',     'icon' => 'list',        'order' => 1, 'permission' => 'account.coa.view'],
            ['label' => 'Journal Entries',   'route' => 'account.journal.index', 'icon' => 'book',        'order' => 2, 'permission' => 'account.journal.view'],
            ['label' => 'Payroll Runs',      'route' => 'payroll.runs.index',    'icon' => 'dollar-sign', 'order' => 3, 'permission' => 'payroll.runs.view'],
            ['label' => 'Tax Reports',       'route' => 'payroll.tax.index',     'icon' => 'file-text',   'order' => 4, 'permission' => 'payroll.tax.view'],
        ],
    ],
    [
        'group_id' => 'pos',
        'group'    => 'Point of Sale',
        'icon'     => 'shopping-cart',
        'order'    => 10,
        'items'    => [...],
    ],
]
```

### Flushing the sidebar cache

```php
use Mehediishere\LaravelModular\Services\SidebarManager;

// Flush for the current user (call after role/permission changes)
app(SidebarManager::class)->flush();

// Flush for a specific user
app(SidebarManager::class)->flush($userId);

// Flush for all users
app(SidebarManager::class)->flushAll();
```

Disable caching during development:

```env
MODULAR_SIDEBAR_CACHE=false
```

---

## Module service provider

The generated `{Name}ServiceProvider` extends `BaseModuleServiceProvider`:

```php
<?php

namespace Modules\Account\app\Providers;

use Mehediishere\LaravelModular\BaseModuleServiceProvider;

class AccountServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleName = 'Account';

    protected array $bindings = [
        \Modules\Account\app\Contracts\LedgerRepositoryInterface::class =>
        \Modules\Account\app\Repositories\LedgerRepository::class,
    ];

    protected array $singletons = [
        'account.currency' => \Modules\Account\app\Services\CurrencyService::class,
    ];

    protected array $commands = [
        \Modules\Account\app\Console\Commands\ReconcileCommand::class,
    ];
}
```

`BaseModuleServiceProvider` automatically handles:

| What            | From where                          |
|-----------------|-------------------------------------|
| Migrations      | `database/migrations/`              |
| Views           | `resources/views/` → `account::`    |
| Translations    | `resources/lang/`  → `account::`    |
| Web routes      | `routes/web.php`                    |
| API routes      | `routes/api.php`                    |
| Module config   | `config/config.php` → `account.*`   |

---

## Accessing module config

```php
// Modules/POS/config/config.php
return ['per_page' => 25, 'currency' => 'BDT'];

// Anywhere in the app
config('pos.per_page');   // 25
config('pos.currency');   // BDT
```

---

## Inter-module communication

Modules should never import classes from each other directly. Use one of:

**Events (zero coupling):**

```php
// Fire from Order module
event(new \Modules\Order\app\Events\OrderPlaced($order));

// Listen in Inventory module's ServiceProvider boot()
\Illuminate\Support\Facades\Event::listen(
    \Modules\Order\app\Events\OrderPlaced::class,
    \Modules\Inventory\app\Listeners\ReserveStock::class,
);
```

**Contracts (return values needed):**

```php
// Define interface in the consuming module
// Modules/Order/app/Contracts/ProductStockInterface.php

// Implement in Product module
// Modules/Product/app/Services/ProductStockService.php

// Bind in Product's ServiceProvider
protected array $bindings = [
    \Modules\Order\app\Contracts\ProductStockInterface::class =>
    \Modules\Product\app\Services\ProductStockService::class,
];
```

---

## Customising stubs

Publish the stubs to your project:

```bash
php artisan vendor:publish --tag=modular-stubs
```

Stubs are written to `stubs/modular/`. The `module:make` command checks for your custom stubs first before falling back to package defaults.

| Stub file                | Generates                         |
|--------------------------|-----------------------------------|
| `service-provider.stub`  | `app/Providers/{Name}ServiceProvider.php` |
| `sidebar-config.stub`    | `config/sidebar.php`              |
| `module-config.stub`     | `config/config.php`               |
| `routes-web.stub`        | `routes/web.php`                  |
| `routes-api.stub`        | `routes/api.php`                  |
| `test-case.stub`         | `tests/TestCase.php`              |

---

## PHPUnit test suites

Add a testsuite entry per module in `phpunit.xml`:

```xml
<testsuites>
    <testsuite name="Application">
        <directory>tests/Feature</directory>
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Account">
        <directory>Modules/Account/tests</directory>
    </testsuite>
    <testsuite name="POS">
        <directory>Modules/POS/tests</directory>
    </testsuite>
</testsuites>
```

Run a single module's tests:

```bash
php artisan test --testsuite=Account
```

---

## Configuration reference

### `config/modules.php`

```php
return [
    'enabled' => ['POS', 'Ecommerce', 'Account', 'Payroll'],
    'path'    => base_path('Modules'),
];
```

### `config/modular.php`

```php
return [
    'sidebar' => [
        'cache'     => true,    // env: MODULAR_SIDEBAR_CACHE
        'cache_ttl' => 3600,    // env: MODULAR_SIDEBAR_TTL (seconds)
    ],
];
```

---

## Changelog

### v1.0.0
- Initial release
- Module discovery via `config/modules.php`
- `BaseModuleServiceProvider` with auto-registration of migrations, views, routes, config, translations, commands
- `SidebarManager` with `group_id` merging, per-user permission filtering, and caching
- `php artisan module:make` scaffold command with `--force` flag
- Publishable config, views, and stubs

---

## License

MIT — see [LICENSE](LICENSE)

---

## Author

**Mehedi Hassan** — [@mehediishere](https://github.com/mehediishere)

---

## Full command reference (v1.1)

### Utility

```bash
php artisan module:list                   # all modules — status, routes, migrations, sidebar
php artisan module:enable  Account        # adds Account to config/modules.php enabled[]
php artisan module:disable Account        # removes Account from config/modules.php enabled[]
php artisan module:events                 # event → listener map across all enabled modules
php artisan module:events POS             # filtered to one module
php artisan module:events --static        # static file scan fallback (no app boot needed)
```

### Generators

All generators follow the pattern: `php artisan module:make-{type} {ClassName} {Module}`

```bash
# Controller
php artisan module:make-controller PostController      POS
php artisan module:make-controller PostController      POS --api
php artisan module:make-controller PostController      POS --plain
php artisan module:make-controller PostController      POS --invokable

# Model — individual flags
php artisan module:make-model Product POS              # model only
php artisan module:make-model Product POS -m           # + migration
php artisan module:make-model Product POS -c           # + controller
php artisan module:make-model Product POS -f           # + factory
php artisan module:make-model Product POS -s           # + seeder
php artisan module:make-model Product POS -r           # + form request

# Model — combined shorthand (any combination of m c f s r)
php artisan module:make-model Product POS --mcfsr=mc   # migration + controller
php artisan module:make-model Product POS --mcfsr=mfs  # migration + factory + seeder
php artisan module:make-model Product POS --mcfsr      # all five

# Migration
php artisan module:make-migration create_products_table POS
php artisan module:make-migration add_price_to_products POS --table=products

# Everything else
php artisan module:make-request      StoreProductRequest    POS
php artisan module:make-service      ProductService         POS
php artisan module:make-event        ProductCreated         POS
php artisan module:make-listener     SendProductAlert       POS --event=ProductCreated
php artisan module:make-listener     SendProductAlert       POS --event=ProductCreated --queued
php artisan module:make-job          ProcessProductImport   POS
php artisan module:make-job          ProcessProductImport   POS --sync
php artisan module:make-command      SyncCatalogCommand     POS
php artisan module:make-middleware   CheckStoreIsOpen       POS
php artisan module:make-mail         OrderConfirmation      POS
php artisan module:make-notification LowStockAlert          POS
php artisan module:make-observer     ProductObserver        POS
php artisan module:make-policy       ProductPolicy          POS
php artisan module:make-resource     ProductResource        POS
php artisan module:make-resource     ProductResource        POS --collection
php artisan module:make-seeder       ProductSeeder          POS
php artisan module:make-factory      ProductFactory         POS
php artisan module:make-trait        HasSku                 POS
php artisan module:make-interface    ProductRepositoryInterface POS
php artisan module:make-enum         ProductStatus          POS
php artisan module:make-exception    ProductNotFoundException POS
php artisan module:make-cast         MoneyValueCast         POS
php artisan module:make-scope        ActiveProductScope     POS
php artisan module:make-action       PublishProduct         POS
php artisan module:make-helper       PriceHelper            POS
php artisan module:make-repository   ProductRepository      POS
php artisan module:make-class        ProductSyncHandler     POS
```

All generators accept `--force` to overwrite an existing file.

### Translation

```bash
# Check missing keys across all modules vs English baseline
php artisan module:lang

# Check a single module
php artisan module:lang POS

# Check only one locale
php artisan module:lang POS --locale=bn

# Auto-fill missing keys using English value as fallback
php artisan module:lang-sync

# Sync only one module
php artisan module:lang-sync POS

# Sync only one locale across all modules
php artisan module:lang-sync --locale=bn

# Preview what would be added without writing files
php artisan module:lang-sync --dry-run
php artisan module:lang-sync POS --locale=bn --dry-run
```
