<?php

declare(strict_types=1);

namespace Addresses\Tests\Feature;

use Addresses\Contracts\AddressServiceInterface;
use Addresses\DataTransferObjects\AddressDto;
use Addresses\Models\Address;
use Addresses\Tests\Helpers\TestModel;
use Addresses\Tests\TestCase;

class AddressCrudTest extends TestCase
{
    private AddressServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AddressServiceInterface::class);
    }

    public function test_it_creates_an_address_for_a_model(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            state: 'IL',
            postalCode: '62701',
            countryCode: 'US',
        );

        $address = $this->service->store($parent, $dto);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('123 Main St', $address->address_line_1);
        $this->assertEquals('Springfield', $address->city);
        $this->assertEquals('US', $address->country_code);
        $this->assertEquals($parent->getMorphClass(), $address->addressable_type);
        $this->assertEquals($parent->id, $address->addressable_id);
    }

    public function test_it_updates_an_address(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
        );

        $address = $this->service->store($parent, $dto);

        $updateDto = new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        );

        $updated = $this->service->update($address, $updateDto);

        $this->assertEquals('456 Oak Ave', $updated->address_line_1);
        $this->assertEquals('Chicago', $updated->city);
        $this->assertEquals('work', $updated->type);
    }

    public function test_it_deletes_an_address(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
        );

        $address = $this->service->store($parent, $dto);
        $addressId = $address->id;

        $result = $this->service->delete($address);

        $this->assertTrue($result);
        $this->assertNull(Address::find($addressId));
    }

    public function test_it_gets_all_addresses_for_a_parent(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
        ));

        $this->service->store($parent, new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        ));

        $addresses = $this->service->getForParent($parent);

        $this->assertCount(2, $addresses);
    }

    public function test_setting_primary_unsets_other_primaries(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $address1 = $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
            isPrimary: true,
        ));

        $this->assertTrue($address1->is_primary);

        $address2 = $this->service->store($parent, new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
            isPrimary: true,
        ));

        $this->assertTrue($address2->is_primary);
        $this->assertFalse($address1->fresh()->is_primary);
    }

    public function test_it_syncs_addresses(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        // Create initial addresses
        $address1 = $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
        ));

        $address2 = $this->service->store($parent, new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        ));

        // Sync: update address1, drop address2, add new address3
        $result = $this->service->sync($parent, [
            [
                'id' => $address1->id,
                'type' => 'home',
                'address_line_1' => '123 Main St Updated',
                'city' => 'Springfield',
                'country_code' => 'US',
            ],
            [
                'type' => 'billing',
                'address_line_1' => '789 Pine Rd',
                'city' => 'Denver',
                'country_code' => 'US',
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertNull(Address::find($address2->id));
        $this->assertEquals('123 Main St Updated', $address1->fresh()->address_line_1);
    }

    public function test_has_addresses_trait_relationships(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
            isPrimary: true,
        ));

        $this->service->store($parent, new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        ));

        $parent = $parent->fresh();

        $this->assertCount(2, $parent->addresses);
        $this->assertNotNull($parent->primaryAddress);
        $this->assertEquals('123 Main St', $parent->primaryAddress->address_line_1);
        $this->assertCount(1, $parent->addressesOfType('work')->get());
    }

    public function test_mark_as_primary(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $address1 = $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
            isPrimary: true,
        ));

        $address2 = $this->service->store($parent, new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        ));

        $address2->markAsPrimary();

        $this->assertTrue($address2->fresh()->is_primary);
        $this->assertFalse($address1->fresh()->is_primary);
    }

    public function test_full_address_attribute(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $address = $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            addressLine2: 'Suite 100',
            city: 'Springfield',
            state: 'IL',
            postalCode: '62701',
            countryCode: 'US',
        ));

        $this->assertEquals(
            '123 Main St, Suite 100, Springfield, IL, 62701, US',
            $address->full_address
        );
    }

    public function test_has_coordinates(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $withCoords = $this->service->store($parent, new AddressDto(
            type: 'home',
            addressLine1: '123 Main St',
            city: 'Springfield',
            countryCode: 'US',
            latitude: 39.7817,
            longitude: -89.6501,
        ));

        $withoutCoords = $this->service->store($parent, new AddressDto(
            type: 'work',
            addressLine1: '456 Oak Ave',
            city: 'Chicago',
            countryCode: 'US',
        ));

        $this->assertTrue($withCoords->hasCoordinates());
        $this->assertFalse($withoutCoords->hasCoordinates());
    }
}
