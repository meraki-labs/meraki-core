# Meraki Core

`merakilab/meraki-core` is the **foundation package** of the Meraki ecosystem.
It provides shared conventions, registries, events, and lifecycle hooks that allow independent modules and packages to integrate cleanly — without tight coupling.

Meraki Core **does not implement business logic** such as roles, authorization engines, or permission storage. It only *listens, aggregates, and exposes metadata*.

---

## Design Principles

* **Core is neutral**: no policy, no storage, no UI
* **Modules declare, Core aggregates**
* **IAM decides**: authorization logic lives in dedicated packages
* **Event-driven & extensible**
* **Convention over enforcement**

---

## Package Information

* **Composer name**: `merakilab/meraki-core`
* **Namespace**: `Meraki\\Core`
* **Framework**: Laravel / Illuminate components

---

## Core Responsibilities

Meraki Core is responsible for:

* Registering and bootstrapping the Meraki lifecycle
* Auto-loading Meraki modules/packages
* Managing shared configuration conventions
* Collecting permission metadata from modules
* Exposing registries, helpers, and events for other packages

Meraki Core is **NOT** responsible for:

* Authorization decisions
* Role management
* Permission persistence
* Route protection
* UI or admin panels

---

## CoreServiceProvider

`CoreServiceProvider` is the main entry point.

Responsibilities:

* Register core services and registries
* Boot lifecycle events
* Merge and publish shared configuration safely
* Discover enabled modules
* Trigger permission collection

```php
use Meraki\Core\CoreServiceProvider;
```

---

## Permission System (Metadata Only)

### Key Concept

Permissions in Meraki Core are **declarative metadata**, not access rules.

* Each module declares its own permissions
* Core collects all declared permissions
* IAM or other packages decide how to use them

---

### Declaring Permissions in a Module

Each module can declare permissions via its config file, for example:

```php
// config/meraki-blog.php
return [
    'permissions' => [
        'blog.view',
        'blog.create',
        'blog.update',
        'blog.delete',
    ],
];
```

---

### PermissionRegistry

The `PermissionRegistry` lives in Core and acts as a **central collector**.

Responsibilities:

* Register permissions from all enabled modules
* Store permission metadata in memory
* Expose permissions to other packages

Core does **not**:

* Validate permissions
* Resolve conflicts or overlaps
* Decide access rules

---

### Accessing Permissions

Use the global helper:

```php
get_permissions();
```

Returns a normalized list of all permissions declared by modules.

---

## Events & Lifecycle Hooks

Meraki Core exposes events so that other packages (e.g. IAM) can hook into the lifecycle.

Examples:

* Module booted
* Permissions registered
* Core fully booted

This allows packages to:

* Sync permissions to database
* Build role mappings
* Attach authorization engines

Without Core knowing *how* they do it.

---

## Capability Gate

Meraki Core acts as a **capability gate** — a single access point that routes calls to whichever driver is available, with automatic fallback to Laravel defaults when no Meraki package is installed.

### Supported capabilities

| Capability | Contract | Default (fallback) |
|---|---|---|
| `auth` | `Meraki\Core\Contracts\AuthDriver` | `LaravelAuthAdapter` (wraps `Auth` facade) |
| `permission` | `Meraki\Core\Contracts\PermissionDriver` | `LaravelGateAdapter` (wraps `Gate` facade) |

### Usage

```php
// Via Facade
Meraki::auth()->check();          // bool
Meraki::auth()->id();             // mixed
Meraki::auth()->user();           // ?object
Meraki::can('posts.create');      // bool
Meraki::can('posts.edit', $user); // bool — check for a specific user

// Via helpers
meraki()->auth()->check();
meraki_can('posts.create');
```

### Driver resolution order

For each capability, CoreManager resolves the driver as follows:

1. `config('meraki.capabilities.<capability>.driver')` — explicit name (not `auto`)
2. Last driver registered via `extend()` (package driver)
3. Laravel adapter (built-in fallback)

### Registering a package driver (convention for Meraki packages)

In your package's `ServiceProvider::register()`:

```php
use Meraki\Core\CoreManager;

public function register(): void
{
    $core = $this->app->make(CoreManager::class);

    // Register the package so Core knows it's installed
    $core->packages()->register('meraki-auth', [
        'provider' => static::class,
        'config'   => 'meraki-auth',   // key used to load permissions from config
    ]);

    // Register the capability driver
    $core->extend('auth', 'meraki-auth', fn ($app) => new MerakiAuthDriver(
        $app->make(\Meraki\Auth\Services\AuthManager::class)
    ));
}
```

`MerakiAuthDriver` is a thin adapter **inside your package** that implements `Meraki\Core\Contracts\AuthDriver`.

### Registering a third-party driver (e.g. spatie/laravel-permission)

```php
// In AppServiceProvider::register()
$this->app->make(\Meraki\Core\CoreManager::class)
    ->extend('permission', 'spatie', fn ($app) => new SpatiePermissionDriver());
```

Then in `config/meraki.php`:

```php
'capabilities' => [
    'permission' => ['driver' => 'spatie'],
],
```

Or set `MERAKI_PERMISSION_DRIVER=spatie` in `.env`.

---

## Authorization & IAM Integration

Meraki Core defines **conventions**, not implementations.

* Core expects IAM packages to expose methods like `can()`
* Laravel's `Gate::can()` **can be used**, but is optional
* IAM packages may wrap, replace, or ignore Laravel Gate

Core treats this as a **documented convention**, not a hard dependency.

---

## Enable / Disable Modules

Modules can be enabled or disabled via configuration.

Core only:

* Detects enabled modules
* Loads their configs
* Registers their permissions

Behavior differences are handled by consuming packages.

---

## Relationship with Other Packages

### meraki-iam (example)

Typical responsibilities:

* Persist permissions
* Manage roles and scopes
* Resolve permission conflicts
* Implement `can()` / authorization logic
* Integrate with routes and middleware

### Other Feature Packages

* Declare permissions
* Listen to Core events
* Remain independent of IAM implementation

---

## Summary

Meraki Core is the **contract layer** of the Meraki ecosystem.

It:

* Connects packages
* Aggregates metadata
* Emits lifecycle events
* Stays out of business logic

This keeps the system:

* Modular
* Replaceable
* Testable
* Long-term maintainable

---

## License

MIT