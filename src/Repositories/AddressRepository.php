<?php

declare(strict_types=1);

namespace Addresses\Repositories;

use Addresses\Contracts\AddressRepositoryInterface;
use Addresses\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AddressRepository implements AddressRepositoryInterface
{
    public function __construct(
        protected Address $model,
    ) {}

    public function find(int $id): ?Address
    {
        return $this->model->find($id);
    }

    public function create(array $data): Address
    {
        return $this->model->create($data);
    }

    public function update(Address $address, array $data): Address
    {
        $address->update($data);

        return $address->fresh();
    }

    public function delete(Address $address): bool
    {
        return (bool) $address->delete();
    }

    public function getForParent(Model $parent): Collection
    {
        return $this->model
            ->where('addressable_type', $parent->getMorphClass())
            ->where('addressable_id', $parent->getKey())
            ->orderByDesc('is_primary')
            ->orderBy('type')
            ->get();
    }

    public function findForParent(int $addressId, Model $parent): ?Address
    {
        return $this->model
            ->where('id', $addressId)
            ->where('addressable_type', $parent->getMorphClass())
            ->where('addressable_id', $parent->getKey())
            ->first();
    }

    public function unsetPrimaryForParent(Model $parent): void
    {
        $this->model
            ->where('addressable_type', $parent->getMorphClass())
            ->where('addressable_id', $parent->getKey())
            ->update(['is_primary' => false]);
    }

    public function deleteWhereNotIn(Model $parent, array $ids): void
    {
        $this->model
            ->where('addressable_type', $parent->getMorphClass())
            ->where('addressable_id', $parent->getKey())
            ->whereNotIn('id', $ids)
            ->delete();
    }
}
