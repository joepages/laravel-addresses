<?php

declare(strict_types=1);

namespace Addresses\Concerns;

use Addresses\Http\Resources\AddressResource;

/**
 * Trait for API Resources to include addresses in the response.
 *
 * Usage:
 *   class FacilityResource extends BaseResource {
 *       use WithAddressesResource;
 *
 *       public function toArray($request): array {
 *           return array_merge([
 *               'id' => $this->id,
 *               'name' => $this->name,
 *           ], $this->addressesResource());
 *       }
 *   }
 */
trait WithAddressesResource
{
    protected function addressesResource(): array
    {
        return [
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'primary_address' => $this->whenLoaded('primaryAddress', function () {
                return $this->primaryAddress ? new AddressResource($this->primaryAddress) : null;
            }),
        ];
    }
}
