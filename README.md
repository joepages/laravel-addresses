# Addresses Package

Polymorphic addresses package for Laravel. Attach N addresses to any Eloquent model.

## Installation

```bash
composer require your-vendor/addresses
php artisan addresses:install
php artisan migrate
```

## Usage

### Add the trait to your model

```php
use Addresses\Concerns\HasAddresses;

class Facility extends Model
{
    use HasAddresses;
}
```

### Register routes in your route file

```php
use App\Http\Controllers\Api\FacilityController;

Route::addressRoutes('facilities', FacilityController::class);
```

### Add the controller trait

```php
use Addresses\Concerns\ManagesAddresses;

class FacilityController extends BaseApiController
{
    use ManagesAddresses;
}
```

### Add addresses to your API resource

```php
use Addresses\Concerns\WithAddressesResource;

class FacilityResource extends BaseResource
{
    use WithAddressesResource;

    public function toArray($request): array
    {
        return array_merge([
            'id' => $this->id,
            'name' => $this->name,
        ], $this->addressesResource());
    }
}
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=addresses-config
```

## Testing

```bash
composer test
```
