<?php

declare(strict_types=1);

namespace Addresses\DataTransferObjects;

use Addresses\Http\Requests\AddressRequest;

readonly class AddressDto
{
    public function __construct(
        public string $type,
        public string $addressLine1,
        public ?string $addressLine2 = null,
        public string $city = '',
        public ?string $state = null,
        public ?string $postalCode = null,
        public string $countryCode = '',
        public bool $isPrimary = false,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(AddressRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? config('addresses.default_type', 'home'),
            addressLine1: $data['address_line_1'],
            addressLine2: $data['address_line_2'] ?? null,
            city: $data['city'],
            state: $data['state'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            countryCode: $data['country_code'],
            isPrimary: (bool) ($data['is_primary'] ?? false),
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'is_primary' => $this->isPrimary,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'metadata' => $this->metadata,
        ];
    }
}
