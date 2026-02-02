<?php

declare(strict_types=1);

namespace Addresses\Concerns;

use Addresses\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait to add to any Eloquent model that can have addresses.
 *
 * Usage:
 *   use Addresses\Concerns\HasAddresses;
 *
 *   class Facility extends Model {
 *       use HasAddresses;
 *   }
 */
trait HasAddresses
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('is_primary', true);
    }

    public function addressesOfType(string $type): MorphMany
    {
        return $this->addresses()->where('type', $type);
    }
}
