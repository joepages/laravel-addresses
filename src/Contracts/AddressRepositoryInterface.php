<?php

declare(strict_types=1);

namespace Addresses\Contracts;

use Addresses\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface AddressRepositoryInterface
{
    public function find(int $id): ?Address;

    public function create(array $data): Address;

    public function update(Address $address, array $data): Address;

    public function delete(Address $address): bool;

    public function getForParent(Model $parent): Collection;

    public function findForParent(int $addressId, Model $parent): ?Address;

    public function unsetPrimaryForParent(Model $parent): void;

    public function deleteWhereNotIn(Model $parent, array $ids): void;
}
