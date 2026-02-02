<?php

declare(strict_types=1);

namespace Addresses\Services;

use Addresses\Contracts\AddressRepositoryInterface;
use Addresses\Contracts\AddressServiceInterface;
use Addresses\DataTransferObjects\AddressDto;
use Addresses\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AddressService implements AddressServiceInterface
{
    public function __construct(
        protected AddressRepositoryInterface $repository,
    ) {}

    public function store(Model $parent, AddressDto $dto): Address
    {
        $data = array_merge($dto->toArray(), [
            'addressable_type' => $parent->getMorphClass(),
            'addressable_id' => $parent->getKey(),
        ]);

        if ($dto->isPrimary) {
            $this->repository->unsetPrimaryForParent($parent);
        }

        return $this->repository->create($data);
    }

    public function update(Address $address, AddressDto $dto): Address
    {
        $data = $dto->toArray();

        if ($dto->isPrimary && ! $address->is_primary) {
            $parent = $address->addressable;
            $this->repository->unsetPrimaryForParent($parent);
        }

        return $this->repository->update($address, $data);
    }

    public function delete(Address $address): bool
    {
        return $this->repository->delete($address);
    }

    public function getForParent(Model $parent): Collection
    {
        return $this->repository->getForParent($parent);
    }

    public function findForParent(int $addressId, Model $parent): ?Address
    {
        return $this->repository->findForParent($addressId, $parent);
    }

    /**
     * Sync addresses for a parent model.
     * Creates new entries, updates existing (matched by id), deletes missing.
     */
    public function sync(Model $parent, array $addressesData): Collection
    {
        $keptIds = [];

        foreach ($addressesData as $addressData) {
            $dto = AddressDto::fromArray($addressData);

            if (isset($addressData['id'])) {
                // Update existing
                $address = $this->findForParent((int) $addressData['id'], $parent);
                if ($address) {
                    $this->update($address, $dto);
                    $keptIds[] = $address->id;

                    continue;
                }
            }

            // Create new
            $address = $this->store($parent, $dto);
            $keptIds[] = $address->id;
        }

        // Delete addresses not in the payload
        if (! empty($keptIds)) {
            $this->repository->deleteWhereNotIn($parent, $keptIds);
        }

        return $this->getForParent($parent);
    }
}
