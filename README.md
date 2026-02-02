# Laravel Addresses

[![Tests](https://github.com/joepages/laravel-addresses/actions/workflows/tests.yml/badge.svg)](https://github.com/joepages/laravel-addresses/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/joepages/laravel-addresses.svg)](https://packagist.org/packages/joepages/laravel-addresses)
[![License](https://img.shields.io/packagist/l/joepages/laravel-addresses.svg)](https://packagist.org/packages/joepages/laravel-addresses)

Polymorphic addresses for Laravel. Attach multiple addresses to any Eloquent model with full CRUD, bulk sync, primary address management, geolocation support, and multi-tenancy awareness.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require joepages/laravel-addresses
```

Run the install command to publish the config and migrations:

```bash
php artisan addresses:install
php artisan migrate
```

The installer auto-detects [stancl/tenancy](https://tenancyforlaravel.com/) and publishes migrations to `database/migrations/tenant/` when present.

### Install options

```bash
php artisan addresses:install --force            # Overwrite existing files
php artisan addresses:install --skip-migrations  # Only publish config
```

## Quick Start

### 1. Add the trait to your model

```php
use Addresses\Concerns\HasAddresses;

class Facility extends Model
{
    use HasAddresses;
}
```

### 2. Add the controller trait

```php
use Addresses\Concerns\ManagesAddresses;

class FacilityController extends BaseApiController
{
    use ManagesAddresses;
}
```

### 3. Register routes

```php
Route::addressRoutes('facilities', FacilityController::class);
```

This registers the following routes:

| Method | URI | Action |
|--------|-----|--------|
| GET | `/facilities/{facility}/addresses` | `listAddresses` |
| POST | `/facilities/{facility}/addresses` | `storeAddress` |
| PUT | `/facilities/{facility}/addresses/{address}` | `updateAddress` |
| DELETE | `/facilities/{facility}/addresses/{address}` | `deleteAddress` |

## Model Trait API

The `HasAddresses` trait provides three relationships on your model:

```php
$facility->addresses;               // All addresses (MorphMany)
$facility->primaryAddress;          // Primary address (MorphOne)
$facility->addressesOfType('work'); // Filtered by type (MorphMany)
```

## Address Model

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `type` | string | Address type (`home`, `work`, `billing`, `shipping`, `mailing`, `other`) |
| `is_primary` | boolean | Whether this is the primary address |
| `address_line_1` | string | Street address line 1 |
| `address_line_2` | string\|null | Street address line 2 |
| `city` | string | City |
| `state` | string\|null | State/province |
| `postal_code` | string\|null | ZIP/postal code |
| `country_code` | string | 2-3 letter country code |
| `latitude` | float\|null | Latitude coordinate |
| `longitude` | float\|null | Longitude coordinate |
| `metadata` | array\|null | Custom JSON data |

### Scopes

```php
Address::primary()->get();            // Only primary addresses
Address::ofType('billing')->get();    // Filter by type
Address::forModel($facility)->get();  // All addresses for a specific model
```

### Helpers

```php
$address->markAsPrimary();    // Sets as primary, unsets all others for the same parent
$address->full_address;       // "123 Main St, Apt 4, Springfield, IL, 62701, US"
$address->hasCoordinates();   // true if latitude and longitude are set
```

## Controller Trait

The `ManagesAddresses` trait provides two integration modes:

### Standalone CRUD

Use the `storeAddress`, `updateAddress`, `deleteAddress`, and `listAddresses` methods directly via the route macro.

### Bulk Sync via BaseApiController

When your controller extends `BaseApiController`, the `attachAddress()` method is called automatically during `store()` and `update()`. Send an `addresses` array in the request body to create, update, and delete addresses in a single operation:

```json
{
  "name": "Main Facility",
  "addresses": [
    {
      "id": 1,
      "address_line_1": "123 Updated St",
      "city": "Springfield",
      "country_code": "US"
    },
    {
      "address_line_1": "456 New Ave",
      "city": "Shelbyville",
      "country_code": "US",
      "type": "billing",
      "is_primary": true
    }
  ]
}
```

- Records **with an `id`** are updated
- Records **without an `id`** are created
- Existing records **not included** in the array are deleted

## API Resource

Add addresses to your JSON responses:

```php
use Addresses\Concerns\WithAddressesResource;

class FacilityResource extends JsonResource
{
    use WithAddressesResource;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            ...$this->addressesResource(),
        ];
    }
}
```

## Validation

The `AddressRequest` form request validates:

| Field | Rules |
|-------|-------|
| `address_line_1` | required, string, max:255 |
| `address_line_2` | nullable, string, max:255 |
| `city` | required, string, max:255 |
| `state` | nullable, string, max:255 |
| `postal_code` | nullable, string, max:20 |
| `country_code` | required, string, min:2, max:3 |
| `type` | sometimes, string (validated against config when `allow_custom_types` is false) |
| `is_primary` | sometimes, boolean |
| `latitude` | nullable, numeric, -90 to 90 |
| `longitude` | nullable, numeric, -180 to 180 |
| `metadata` | nullable, array |

## Configuration

```php
// config/addresses.php

return [
    // 'auto' detects stancl/tenancy, 'single' or 'multi' to force
    'tenancy_mode' => 'auto',

    // Allowed address types
    'types' => ['home', 'work', 'billing', 'shipping', 'mailing', 'other'],

    // Default type when none specified
    'default_type' => 'home',

    // When false, only types in the 'types' array are accepted
    'allow_custom_types' => true,
];
```

## Database Schema

```sql
CREATE TABLE addresses (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    addressable_type VARCHAR(255) NOT NULL,
    addressable_id   BIGINT UNSIGNED NOT NULL,
    type            VARCHAR(50) DEFAULT 'home',
    is_primary      BOOLEAN DEFAULT FALSE,
    address_line_1  VARCHAR(255) NOT NULL,
    address_line_2  VARCHAR(255) NULL,
    city            VARCHAR(255) NOT NULL,
    state           VARCHAR(255) NULL,
    postal_code     VARCHAR(255) NULL,
    country_code    VARCHAR(3) NOT NULL,
    latitude        DECIMAL(10,7) NULL,
    longitude       DECIMAL(10,7) NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX (addressable_type, addressable_id),
    INDEX (type),
    INDEX (is_primary),
    INDEX (country_code)
);
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
