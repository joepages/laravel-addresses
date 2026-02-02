<?php

declare(strict_types=1);

namespace Addresses\Concerns;

use Addresses\Contracts\AddressServiceInterface;
use Addresses\DataTransferObjects\AddressDto;
use Addresses\Http\Requests\AddressRequest;
use Addresses\Http\Resources\AddressCollection;
use Addresses\Http\Resources\AddressResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Controller trait for managing addresses on a parent model.
 *
 * Provides:
 * - attachAddress(): called by BaseApiController::attachRelatedData() for bulk sync
 * - storeAddress(), updateAddress(), deleteAddress(), listAddresses(): standalone CRUD endpoints
 *
 * The consuming controller MUST define:
 * - $modelClass (string): The parent model class
 * - $serviceInterface: The parent model's service interface
 */
trait ManagesAddresses
{
    /**
     * Called by BaseApiController::attachRelatedData() during store/update.
     * Supports bulk sync: if 'addresses' key exists in request, syncs all addresses.
     */
    protected function attachAddress(Request $request, Model $model): void
    {
        if (! $request->has('addresses')) {
            return;
        }

        $addressesData = $request->input('addresses', []);

        if (empty($addressesData)) {
            return;
        }

        $addressService = app(AddressServiceInterface::class);
        $addressService->sync($model, $addressesData);
    }

    /**
     * List all addresses for a parent model.
     */
    public function listAddresses(int $parentId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('view', $parent);

        $addressService = app(AddressServiceInterface::class);
        $addresses = $addressService->getForParent($parent);

        return new AddressCollection($addresses);
    }

    /**
     * Store a new address for a parent model.
     */
    public function storeAddress(AddressRequest $request, int $parentId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $dto = AddressDto::fromRequest($request);
        $addressService = app(AddressServiceInterface::class);
        $address = $addressService->store($parent, $dto);

        return (new AddressResource($address))
            ->response()
            ->setStatusCode(201)
            ->original;
    }

    /**
     * Update an existing address for a parent model.
     */
    public function updateAddress(AddressRequest $request, int $parentId, int $addressId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $addressService = app(AddressServiceInterface::class);
        $address = $addressService->findForParent($addressId, $parent);

        if (! $address) {
            abort(404, 'Address not found.');
        }

        $dto = AddressDto::fromRequest($request);
        $address = $addressService->update($address, $dto);

        return new AddressResource($address);
    }

    /**
     * Delete an address for a parent model.
     */
    public function deleteAddress(int $parentId, int $addressId): JsonResponse
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $addressService = app(AddressServiceInterface::class);
        $address = $addressService->findForParent($addressId, $parent);

        if (! $address) {
            abort(404, 'Address not found.');
        }

        $addressService->delete($address);

        return response()->json(['message' => 'Address deleted successfully.'], 200);
    }

    /**
     * Resolve the parent model by ID.
     */
    protected function resolveParentModel(int $parentId): Model
    {
        return $this->service->getById($parentId);
    }
}
