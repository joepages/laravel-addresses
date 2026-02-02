<?php

declare(strict_types=1);

namespace Addresses\Contracts;

use Addresses\DataTransferObjects\AddressDto;
use Addresses\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface AddressServiceInterface
{
    public function store(Model $parent, AddressDto $dto): Address;

    public function update(Address $address, AddressDto $dto): Address;

    public function delete(Address $address): bool;

    public function getForParent(Model $parent): Collection;

    public function findForParent(int $addressId, Model $parent): ?Address;

    /**
     * Sync addresses for a parent model.
     * Creates new, updates existing (matched by id), deletes missing.
     *
     * @param  array<int, array>  $addressesData
     */
    public function sync(Model $parent, array $addressesData): Collection;
}
