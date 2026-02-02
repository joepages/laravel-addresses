<?php

declare(strict_types=1);

namespace Addresses\Tests\Unit;

use Addresses\Http\Requests\AddressRequest;
use Addresses\Tests\UnitTestCase;

class EmbeddedRulesTest extends UnitTestCase
{
    public function test_it_returns_rules_with_default_prefix(): void
    {
        $rules = AddressRequest::embeddedRules();

        $this->assertArrayHasKey('addresses', $rules);
        $this->assertArrayHasKey('addresses.*.address_line_1', $rules);
        $this->assertArrayHasKey('addresses.*.city', $rules);
        $this->assertArrayHasKey('addresses.*.country_code', $rules);
        $this->assertArrayHasKey('addresses.*.id', $rules);
        $this->assertArrayHasKey('addresses.*.type', $rules);
        $this->assertArrayHasKey('addresses.*.is_primary', $rules);
        $this->assertArrayHasKey('addresses.*.address_line_2', $rules);
        $this->assertArrayHasKey('addresses.*.state', $rules);
        $this->assertArrayHasKey('addresses.*.postal_code', $rules);
        $this->assertArrayHasKey('addresses.*.latitude', $rules);
        $this->assertArrayHasKey('addresses.*.longitude', $rules);
        $this->assertArrayHasKey('addresses.*.metadata', $rules);
    }

    public function test_it_returns_rules_with_custom_prefix(): void
    {
        $rules = AddressRequest::embeddedRules('billing_addresses');

        $this->assertArrayHasKey('billing_addresses', $rules);
        $this->assertArrayHasKey('billing_addresses.*.address_line_1', $rules);
        $this->assertArrayHasKey('billing_addresses.*.city', $rules);
        $this->assertArrayHasKey('billing_addresses.*.country_code', $rules);

        // Ensure default prefix keys are not present
        $this->assertArrayNotHasKey('addresses', $rules);
        $this->assertArrayNotHasKey('addresses.*.address_line_1', $rules);
    }

    public function test_top_level_rule_is_sometimes_array(): void
    {
        $rules = AddressRequest::embeddedRules();

        $this->assertEquals(['sometimes', 'array'], $rules['addresses']);
    }

    public function test_required_fields_have_required_rule(): void
    {
        $rules = AddressRequest::embeddedRules();

        $this->assertContains('required', $rules['addresses.*.address_line_1']);
        $this->assertContains('required', $rules['addresses.*.city']);
        $this->assertContains('required', $rules['addresses.*.country_code']);
    }

    public function test_id_field_is_optional_integer(): void
    {
        $rules = AddressRequest::embeddedRules();

        $this->assertContains('sometimes', $rules['addresses.*.id']);
        $this->assertContains('integer', $rules['addresses.*.id']);
        $this->assertContains('exists:addresses,id', $rules['addresses.*.id']);
    }
}
