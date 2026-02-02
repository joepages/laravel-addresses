<?php

declare(strict_types=1);

namespace Addresses\Tests\Unit;

use Addresses\DataTransferObjects\AddressDto;
use Addresses\Tests\UnitTestCase;

class AddressDtoTest extends UnitTestCase
{
    public function test_it_creates_dto_from_array(): void
    {
        $data = [
            'type' => 'home',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Suite 100',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62701',
            'country_code' => 'US',
            'is_primary' => true,
            'latitude' => 39.7817,
            'longitude' => -89.6501,
            'metadata' => ['notes' => 'Main office'],
        ];

        $dto = AddressDto::fromArray($data);

        $this->assertEquals('home', $dto->type);
        $this->assertEquals('123 Main St', $dto->addressLine1);
        $this->assertEquals('Suite 100', $dto->addressLine2);
        $this->assertEquals('Springfield', $dto->city);
        $this->assertEquals('IL', $dto->state);
        $this->assertEquals('62701', $dto->postalCode);
        $this->assertEquals('US', $dto->countryCode);
        $this->assertTrue($dto->isPrimary);
        $this->assertEquals(39.7817, $dto->latitude);
        $this->assertEquals(-89.6501, $dto->longitude);
        $this->assertEquals(['notes' => 'Main office'], $dto->metadata);
    }

    public function test_it_converts_to_array(): void
    {
        $dto = new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        );

        $array = $dto->toArray();

        $this->assertEquals('work', $array['type']);
        $this->assertEquals('456 Oak Ave', $array['address_line_1']);
        $this->assertEquals('Chicago', $array['city']);
        $this->assertEquals('US', $array['country_code']);
        $this->assertFalse($array['is_primary']);
        $this->assertNull($array['address_line_2']);
        $this->assertNull($array['state']);
        $this->assertNull($array['postal_code']);
        $this->assertNull($array['latitude']);
        $this->assertNull($array['longitude']);
        $this->assertNull($array['metadata']);
    }

    public function test_it_uses_default_type_from_config(): void
    {
        config(['addresses.default_type' => 'billing']);

        $data = [
            'address_line_1' => '789 Pine Rd',
            'city' => 'Denver',
            'country_code' => 'US',
        ];

        $dto = AddressDto::fromArray($data);

        $this->assertEquals('billing', $dto->type);
    }

    public function test_is_primary_defaults_to_false(): void
    {
        $data = [
            'type' => 'home',
            'address_line_1' => '123 Main St',
            'city' => 'Springfield',
            'country_code' => 'US',
        ];

        $dto = AddressDto::fromArray($data);

        $this->assertFalse($dto->isPrimary);
    }

    public function test_it_is_readonly(): void
    {
        $dto = new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
        );

        $this->expectException(\Error::class);
        $dto->type = 'work'; // @phpstan-ignore-line
    }
}
