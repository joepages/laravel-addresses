<?php

declare(strict_types=1);

namespace Addresses\Database\Factories;

use Addresses\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['home', 'work', 'billing', 'shipping']),
            'is_primary' => false,
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_code' => $this->faker->countryCode(),
            'latitude' => $this->faker->optional(0.5)->latitude(),
            'longitude' => $this->faker->optional(0.5)->longitude(),
            'metadata' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function home(): static
    {
        return $this->state(fn () => ['type' => 'home']);
    }

    public function work(): static
    {
        return $this->state(fn () => ['type' => 'work']);
    }

    public function billing(): static
    {
        return $this->state(fn () => ['type' => 'billing']);
    }

    public function shipping(): static
    {
        return $this->state(fn () => ['type' => 'shipping']);
    }

    public function withCoordinates(): static
    {
        return $this->state(fn () => [
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
        ]);
    }
}
