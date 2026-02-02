<?php

declare(strict_types=1);

namespace Addresses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeRules = config('addresses.allow_custom_types', true)
            ? ['string', 'max:50']
            : ['string', 'in:' . implode(',', config('addresses.types', []))];

        return [
            'type' => ['sometimes', ...$typeRules],
            'is_primary' => ['sometimes', 'boolean'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'min:2', 'max:3'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Embeddable rules for parent requests.
     *
     * Usage: ...AddressRequest::embeddedRules() in a parent FormRequest::rules()
     */
    public static function embeddedRules(string $prefix = 'addresses'): array
    {
        $typeRules = config('addresses.allow_custom_types', true)
            ? ['string', 'max:50']
            : ['string', 'in:' . implode(',', config('addresses.types', []))];

        return [
            $prefix => ['sometimes', 'array'],
            "{$prefix}.*.id" => ['sometimes', 'integer', 'exists:addresses,id'],
            "{$prefix}.*.type" => ['sometimes', ...$typeRules],
            "{$prefix}.*.is_primary" => ['sometimes', 'boolean'],
            "{$prefix}.*.address_line_1" => ['required', 'string', 'max:255'],
            "{$prefix}.*.address_line_2" => ['nullable', 'string', 'max:255'],
            "{$prefix}.*.city" => ['required', 'string', 'max:255'],
            "{$prefix}.*.state" => ['nullable', 'string', 'max:255'],
            "{$prefix}.*.postal_code" => ['nullable', 'string', 'max:20'],
            "{$prefix}.*.country_code" => ['required', 'string', 'min:2', 'max:3'],
            "{$prefix}.*.latitude" => ['nullable', 'numeric', 'between:-90,90'],
            "{$prefix}.*.longitude" => ['nullable', 'numeric', 'between:-180,180'],
            "{$prefix}.*.metadata" => ['nullable', 'array'],
        ];
    }
}
