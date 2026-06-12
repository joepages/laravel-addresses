---
name: laravel-addresses
description: Polymorphic addresses for Laravel — attach N addresses (home, work, billing, shipping, mailing) to any Eloquent model with primary-address management, bulk sync, REST endpoints, validation rules, and lat/lng storage. Use whenever a model needs addresses of any kind — billing/shipping address forms, customer/vendor/facility locations, address CRUD APIs, "set as default address" features, address validation rules, syncing an addresses array from a request payload, or an addresses table/migration — even if the user never names this package. Triggers: address, addresses, addressable, billing address, shipping address, primary address, postal code, country code, HasAddresses, ManagesAddresses, AddressDto, addresses:install.
---

# Laravel Addresses

Polymorphic address management for Laravel 11/12. One `addresses` table holds every address in the system, attached to any Eloquent model via a `morphMany` relation (`addressable_type` / `addressable_id`). The core abstractions: the `HasAddresses` trait on the parent model (relations), the `AddressServiceInterface` (writes: store/update/delete/sync with primary-flag bookkeeping), and the `ManagesAddresses` controller trait + `Route::addressRoutes()` macro (instant REST endpoints). Mental model: "any model gets N addresses, at most one primary per parent, and the service layer keeps the primary flag consistent."

Package namespace is `Addresses\` (PSR-4 root `src/`), composer name `joepages/laravel-addresses`. The service provider (`Addresses\AddressesServiceProvider`) is auto-discovered.

## Installation & setup

If the package is not registered on Packagist for the consuming project, register the VCS repository first:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/joepages/laravel-addresses" }
]
```

```bash
composer require joepages/laravel-addresses
php artisan addresses:install   # publishes config/addresses.php + migration
php artisan migrate
```

Notes:

- Migrations are **auto-loaded** from the package (`loadMigrationsFrom` in the provider) — `php artisan migrate` creates the `addresses` table even if you never publish anything. `addresses:install` additionally copies the migration into `database/migrations/` (or `database/migrations/tenant/` when multi-tenancy is detected — see `tenancy_mode` config).
- `addresses:install --force` overwrites existing published files; `--skip-migrations` publishes only the config.
- Config alone can also be published via `php artisan vendor:publish --tag=addresses-config`.
- No env vars required. No routes are registered automatically — you opt in with the `Route::addressRoutes()` macro.

### Install this skill into Claude Code

This package ships this skill at `skills/laravel-addresses/`. Add to your project `composer.json` so the skill lands in `.claude/skills/` on every install/update:

```json
"scripts": {
    "post-install-cmd": ["@php vendor/joepages/laravel-addresses/bin/install-skill"],
    "post-update-cmd": ["@php vendor/joepages/laravel-addresses/bin/install-skill"]
}
```

The installer overwrites on every run (the package copy is the source of truth) and no-ops unless your project root contains a `.claude/` directory. Add `.claude/skills/laravel-addresses/` to your project `.gitignore`.

## Core API

### Config (`config/addresses.php`)

| Key | Default | Meaning |
|---|---|---|
| `tenancy_mode` | `'auto'` | `'auto'` detects a tenancy package (`function_exists('tenancy')` + `config('tenancy.tenant_model')` set), `'single'`/`'multi'` force the mode. Only affects where `addresses:install` publishes migrations. |
| `types` | `['home', 'work', 'billing', 'shipping', 'mailing', 'other']` | Allowed address types, used by validation when custom types are disallowed. |
| `default_type` | `'home'` | Type applied by `AddressDto::fromArray()` when the payload has no `type`. |
| `allow_custom_types` | `true` | `true`: any string type up to 50 chars validates. `false`: `type` must be in `types`. |

### `Addresses\Concerns\HasAddresses` (trait for the parent model)

| Method | Returns | Purpose |
|---|---|---|
| `addresses()` | `MorphMany` | All addresses for this model. |
| `primaryAddress()` | `MorphOne` | The address with `is_primary = true` (or null). |
| `addressesOfType(string $type)` | `MorphMany` | Addresses filtered by `type`. |

### `Addresses\Models\Address`

Eloquent model, table `addresses`, no soft deletes. Fillable: `addressable_type`, `addressable_id`, `type`, `is_primary`, `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `country_code`, `latitude`, `longitude`, `metadata`. Casts: `is_primary` → bool, `latitude`/`longitude` → float, `metadata` → array.

| Member | Signature | Purpose |
|---|---|---|
| Relation | `addressable(): MorphTo` | The owning parent model. |
| Scope | `Address::primary()` | `where is_primary = true`. |
| Scope | `Address::ofType(string $type)` | `where type = $type`. |
| Scope | `Address::forModel(Model $model)` | All addresses belonging to `$model` (matches morph class + key). |
| Helper | `markAsPrimary(): bool` | Sets this address primary and unsets `is_primary` on every sibling of the same parent. |
| Accessor | `$address->full_address` (string) | Non-empty parts of line1, line2, city, state, postal_code, country_code joined with `", "`. |
| Helper | `hasCoordinates(): bool` | True when both `latitude` and `longitude` are non-null. |
| Factory | `Address::factory()` | `Addresses\Database\Factories\AddressFactory`; states: `primary()`, `home()`, `work()`, `billing()`, `shipping()`, `withCoordinates()`. |

Schema (migration `2025_01_01_000001_create_addresses_table.php`): `id`; `addressable_type` string; `addressable_id` unsignedBigInteger; `type` string(50) default `'home'`; `is_primary` bool default false; `address_line_1` string; `address_line_2` string nullable; `city` string; `state` string nullable; `postal_code` string nullable; `country_code` string(3); `latitude`/`longitude` decimal(10,7) nullable; `metadata` json nullable; timestamps. Indexes on `(addressable_type, addressable_id)`, `type`, `is_primary`, `country_code`.

### `Addresses\Contracts\AddressServiceInterface` (singleton — resolve via `app(AddressServiceInterface::class)`)

| Method | Returns | Purpose |
|---|---|---|
| `store(Model $parent, AddressDto $dto)` | `Address` | Creates an address for `$parent`. If `$dto->isPrimary`, first unsets primary on all of the parent's other addresses. |
| `update(Address $address, AddressDto $dto)` | `Address` | Overwrites **all** address columns from the DTO (full replace, not a patch). Promoting to primary demotes siblings. Returns a fresh model. |
| `delete(Address $address)` | `bool` | Hard-deletes the row. |
| `getForParent(Model $parent)` | `Collection` | All addresses of the parent, ordered primary-first then by `type`. |
| `findForParent(int $addressId, Model $parent)` | `?Address` | The address only if it belongs to that parent; null otherwise. |
| `sync(Model $parent, array $addressesData)` | `Collection` | Bulk reconcile: items with `id` update the matching address, items without `id` create, and existing addresses missing from the payload are deleted. Returns the resulting collection. An empty `$addressesData` is a no-op (deletes nothing). |

### `Addresses\Contracts\AddressRepositoryInterface` (lower-level; bound to `Addresses\Repositories\AddressRepository`, non-singleton)

`find(int $id): ?Address` · `create(array $data): Address` · `update(Address $address, array $data): Address` · `delete(Address $address): bool` · `getForParent(Model $parent): Collection` · `findForParent(int $addressId, Model $parent): ?Address` · `unsetPrimaryForParent(Model $parent): void` · `deleteWhereNotIn(Model $parent, array $ids): void`. Prefer the service; the repository skips primary-flag bookkeeping and morph-key wiring.

### `Addresses\DataTransferObjects\AddressDto` (readonly)

Constructor (named args): `type: string`, `addressLine1: string`, `addressLine2: ?string = null`, `city: string = ''`, `state: ?string = null`, `postalCode: ?string = null`, `countryCode: string = ''`, `isPrimary: bool = false`, `latitude: ?float = null`, `longitude: ?float = null`, `metadata: ?array = null`.

| Method | Purpose |
|---|---|
| `AddressDto::fromArray(array $data): self` | Builds from snake_case keys. `address_line_1`, `city`, `country_code` are **required keys** (TypeError if missing); `type` falls back to `config('addresses.default_type')`; `is_primary` defaults false. |
| `AddressDto::fromRequest(AddressRequest $request): self` | `fromArray($request->validated())`. |
| `toArray(): array` | Snake_case array of all 11 fields (no morph keys). |

### `Addresses\Http\Requests\AddressRequest` (FormRequest, `authorize()` returns true)

`rules()`: `type` sometimes|string|max:50 (or `in:` config types when `allow_custom_types` is false) · `is_primary` sometimes|boolean · `address_line_1` required|string|max:255 · `address_line_2` nullable|string|max:255 · `city` required|string|max:255 · `state` nullable|string|max:255 · `postal_code` nullable|string|max:20 · `country_code` required|string|min:2|max:3 · `latitude` nullable|numeric|between:-90,90 · `longitude` nullable|numeric|between:-180,180 · `metadata` nullable|array.

`AddressRequest::embeddedRules(string $prefix = 'addresses'): array` — static; returns the same rules namespaced as `{$prefix}` (sometimes|array) and `{$prefix}.*.field`, plus `{$prefix}.*.id` (sometimes|integer|exists:addresses,id). Spread into a parent FormRequest's `rules()` for payloads that embed an addresses array.

### `Addresses\Concerns\ManagesAddresses` (trait for controllers)

Prerequisites the consuming controller must provide: a `$this->service` object exposing `getById(int $id): Model` to load the parent (or override `protected function resolveParentModel(int $parentId): Model`), and the `AuthorizesRequests` trait (Laravel's base `Controller` has it) with a policy on the parent model — `listAddresses` authorizes `view`, the writes authorize `update`.

| Method | Route verb | Behavior |
|---|---|---|
| `listAddresses(int $parentId): JsonResource` | GET | Returns `AddressCollection` for the parent. |
| `storeAddress(AddressRequest $request, int $parentId): JsonResponse` | POST | Validates, stores via service, returns `AddressResource` with HTTP 201. |
| `updateAddress(AddressRequest $request, int $parentId, int $addressId): JsonResource` | PUT | 404 (`abort`) if the address doesn't belong to the parent; otherwise full update, returns `AddressResource`. |
| `deleteAddress(int $parentId, int $addressId): JsonResponse` | DELETE | 404 if not owned by parent; otherwise deletes, returns 200 `{"message": "Address deleted successfully."}`. |
| `attachAddress(Request $request, Model $model): void` (protected) | — | Hook for a base controller's store/update pipeline: when the request contains a non-empty `addresses` array, calls `AddressServiceInterface::sync()`. No-op otherwise. The package does **not** ship the base controller that calls this — wire it yourself. |

### Route macro

`Route::addressRoutes(string $prefix, string $controller)` registers, under `{$prefix}/{singular-of-prefix}`: GET `/addresses` → `listAddresses`, POST `/addresses` → `storeAddress`, PUT `/addresses/{address}` → `updateAddress`, DELETE `/addresses/{address}` → `deleteAddress`. Routes are unnamed and carry no middleware — call the macro inside your own `Route::middleware(...)` group. The `{address}` segment is passed as a plain int (no route-model binding).

### Resources

- `Addresses\Http\Resources\AddressResource` — serializes: `id`, `type`, `is_primary`, `address_line_1`, `address_line_2`, `city`, `state`, `postal_code`, `country_code`, `full_address`, `latitude`, `longitude`, `metadata`, `created_at`/`updated_at` (ISO-8601).
- `Addresses\Http\Resources\AddressCollection` — wraps as `{"data": [...]}` of `AddressResource`.
- `Addresses\Concerns\WithAddressesResource` (trait for resources) — protected `addressesResource(): array` returning `addresses` and `primary_address` keys, each only when the corresponding relation is eager-loaded (`whenLoaded`). Merge into your resource's `toArray()`.

### Other

- Artisan: `php artisan addresses:install {--force} {--skip-migrations}`.
- `Addresses\Services\TenancyResolver` — `(new TenancyResolver)->isMultiTenant(): bool` (instance method, not static) resolves the tenancy mode (config override, else auto-detect); used by the installer, callable directly.

## Canonical examples

### 1. Attach addresses to a model and manage the primary

```php
use Addresses\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasAddresses;
}
```

```php
use Addresses\Contracts\AddressServiceInterface;
use Addresses\DataTransferObjects\AddressDto;

$service = app(AddressServiceInterface::class);

$home = $service->store($customer, new AddressDto(
    type: 'home',
    addressLine1: '123 Main St',
    city: 'Springfield',
    state: 'IL',
    postalCode: '62701',
    countryCode: 'US',
    isPrimary: true,
));

$customer->fresh()->primaryAddress->full_address; // "123 Main St, Springfield, IL, 62701, US"

$work = $service->store($customer, new AddressDto(
    type: 'work', addressLine1: '456 Oak Ave', city: 'Chicago', countryCode: 'US',
));
$work->markAsPrimary();           // $home->fresh()->is_primary is now false
$customer->addressesOfType('work')->get(); // 1 address
```

Storing with `isPrimary: true` (or calling `markAsPrimary()`) demotes every other address of the same parent.

### 2. Bulk sync from a request payload

```php
use Addresses\Contracts\AddressServiceInterface;

$result = app(AddressServiceInterface::class)->sync($order, [
    [
        'id' => $existingId,                  // has id → updated
        'type' => 'shipping',
        'address_line_1' => '123 Main St Updated',
        'city' => 'Springfield',
        'country_code' => 'US',
    ],
    [                                          // no id → created
        'type' => 'billing',
        'address_line_1' => '789 Pine Rd',
        'city' => 'Denver',
        'country_code' => 'US',
        'is_primary' => true,
    ],
]);
// Any other address the order had is deleted. $result is the final
// collection, ordered primary-first then by type.
```

### 3. REST endpoints via the controller trait + route macro

```php
use Addresses\Concerns\ManagesAddresses;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;

class CustomerController extends Controller
{
    use ManagesAddresses;

    // Either expose $this->service with getById(int): Model ...
    public function __construct(protected CustomerService $service) {}

    // ... or override the resolver:
    protected function resolveParentModel(int $parentId): Model
    {
        return Customer::findOrFail($parentId);
    }
}
```

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::addressRoutes('customers', CustomerController::class);
});
```

Gives `GET|POST /customers/{customer}/addresses` and `PUT|DELETE /customers/{customer}/addresses/{address}`. A `CustomerPolicy` must allow `view` (list) / `update` (writes) or the endpoints throw `AuthorizationException` (403).

### 4. Embedded validation + addresses in a parent resource

```php
use Addresses\Http\Requests\AddressRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string'],
            ...AddressRequest::embeddedRules(),           // validates addresses.*
            // ...AddressRequest::embeddedRules('billing_addresses') for a custom key
        ];
    }
}
```

```php
use Addresses\Concerns\WithAddressesResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    use WithAddressesResource;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            ...$this->addressesResource(), // addresses + primary_address when eager-loaded
        ];
    }
}

OrderResource::make($order->load('addresses', 'primaryAddress'));
```

## Events, exceptions & edge cases

- **No package events.** Nothing is dispatched on store/update/delete/sync beyond standard Eloquent model events on `Address`. Listen to those if needed.
- **Exceptions:** `Illuminate\Validation\ValidationException` from `AddressRequest` (422); `Symfony\...\NotFoundHttpException` via `abort(404, 'Address not found.')` when an address id doesn't belong to the parent; `Illuminate\Auth\Access\AuthorizationException` (403) from the trait's `authorize()` calls — including when no policy exists for the parent; PHP `Error` if you mutate a readonly `AddressDto` property; `TypeError` from `AddressDto::fromArray()` when `address_line_1`, `city`, or `country_code` keys are absent.
- **Hard deletes.** `Address` has no `SoftDeletes`; `delete()` and `sync()` removals are permanent.
- **`update()` is a full replace.** Every column is rewritten from the DTO, so omitted optional fields (line2, state, postal_code, lat/lng, metadata) become null. Build the DTO from the complete current state for partial edits.
- **Primary uniqueness is service-level only.** There is no DB constraint; writing `is_primary` via raw Eloquent (`Address::create`, `$address->update`) will not demote siblings. Demoting the current primary (DTO `isPrimary: false`) leaves the parent with no primary — `primaryAddress` returns null.
- **`sync([])` deletes nothing.** Deletion of missing rows only happens when at least one payload item survives; likewise the `attachAddress()` hook returns early when the `addresses` key is absent or empty. You cannot wipe all addresses through sync — delete them explicitly.
- **Sync matches `id` per parent.** A payload `id` that doesn't belong to the parent is not updated; that item is created as a new address instead.
- **Migrations always load centrally.** Even in a tenant setup where the installer published to `database/migrations/tenant/`, the provider still registers the package migration path, so a central `php artisan migrate` also creates `addresses` (same filename runs once per database).
- **Type is not validated at the service layer.** `config('addresses.types')` / `allow_custom_types` are enforced only by `AddressRequest`; direct `AddressDto` + service calls accept any type string (column limit 50 chars).
- **Country/postal handling is storage-only.** `country_code` is validated as a free 2–3 char string (column is varchar(3)) — no ISO list check, no uppercasing or other normalization anywhere. `postal_code` validates max:20 but the column allows 255. There is no geocoding: `latitude`/`longitude` are stored as given; `hasCoordinates()` is the only helper.
- **Caching/queues:** none. All operations are synchronous DB calls; `AddressServiceInterface` is a container singleton, the repository binding is per-resolve.

## Common mistakes

- ❌ Updating with a DTO containing only the changed fields → ✅ `update()` overwrites all columns (omitted fields become null); construct the DTO with the full desired state.
- ❌ Calling `$service->sync($parent, [])` to remove all addresses → ✅ empty payload is a no-op; iterate `getForParent()` and call `delete()` per address.
- ❌ Setting `is_primary => true` through `Address::create()` / `$address->update()` and expecting one-primary semantics → ✅ use `AddressServiceInterface::store()/update()` or `$address->markAsPrimary()`; only those demote siblings.
- ❌ Using `ManagesAddresses` on a controller with no `$this->service` → ✅ inject a service exposing `getById(int): Model` or override `resolveParentModel()` — otherwise every endpoint fatals. (The trait's docblock mentions `$modelClass`/`$serviceInterface`; the code actually uses `$this->service`.)
- ❌ Registering `Route::addressRoutes()` at top level and assuming auth/binding → ✅ routes are unnamed, middleware-free, and pass raw int IDs; wrap the macro in your middleware group and rely on the parent policy for access control.
- ❌ Feeding partial arrays to `AddressDto::fromArray()` (e.g. only `city`) → ✅ `address_line_1`, `city`, and `country_code` keys are mandatory; validate first via `AddressRequest`/`embeddedRules()`.
- ❌ Expecting `addresses`/`primary_address` in JSON after adding `WithAddressesResource` → ✅ both use `whenLoaded()`; eager-load `addresses` and `primaryAddress` relations or the keys are omitted.
- ❌ Assuming `country_code` is normalized/ISO-validated → ✅ it's stored exactly as sent (min 2, max 3 chars); normalize (e.g. uppercase ISO-3166 alpha-2) in your own request layer if you need consistency.

## Version notes

- Documented against the current 1.x source: PHP `^8.2`, Laravel (illuminate components) `^11.0|^12.0`. No deprecations; no version-gated behavior.
- The package ships no facade, no enums, no events, and registers no routes by itself — the surface above (traits, contracts, DTO, request, resources, model, macro, command, config) is the complete public API.
- Multi-tenancy support is limited to migration placement at install time (`tenancy_mode`); runtime queries are tenancy-agnostic and use whatever DB connection is active.
