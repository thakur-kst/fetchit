<?php

namespace Modules\Shared\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\DBCore\Models\Core\Country;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

class GetPostcodesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country_code' => [
                'required',
                'string',
                'size:2',
                function ($attribute, $value, $fail) {
                    // Validate that the country_code exists in countries table with caching
                    $cacheService = app(CacheService::class);
                    $cacheKey = CacheKeyGenerator::entity('shared', 'country_exists', strtoupper($value));

                    $exists = $cacheService->remember($cacheKey, function () use ($value) {
                        return Country::where('iso2', strtoupper($value))->exists();
                    });

                    if (!$exists) {
                        $fail('The selected country code is invalid.');
                    }
                },
            ],
            'postcode' => [
                'required',
                'string',
                'min:1',
                function ($attribute, $value, $fail) {
                    $countryCode = strtoupper($this->input('country_code', ''));
                    if (empty($countryCode)) {
                        return;
                    }

                    // Validate against country postcode regex
                    $cacheService = app(CacheService::class);
                    $cacheKey = CacheKeyGenerator::entity('shared', 'country', $countryCode);

                    $country = $cacheService->remember($cacheKey, function () use ($countryCode) {
                        return Country::where('iso2', $countryCode)->first();
                    });

                    if (!$country || !($country instanceof Country)) {
                        $fail('Country configuration not found.');
                        return;
                    }

                    $postcodeValidation = $country->postcode_validation ?? [];
                    $regex = $postcodeValidation['regex'] ?? null;


                    if ($regex) {
                        // Remove delimiters from regex if present
                        $pattern = $regex;
                        if (str_starts_with($pattern, '/') && str_ends_with($pattern, '/')) {
                            $pattern = substr($pattern, 1, -1);
                        }

                        if (!preg_match('/' . $pattern . '/', $value)) {
                            $fail('The postcode format is invalid for the selected country.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country_code.required' => 'Country code is required.',
            'country_code.string' => 'Country code must be a string.',
            'country_code.size' => 'Country code must be exactly 2 characters (ISO2 format).',
            'postcode.required' => 'Postcode is required.',
            'postcode.string' => 'Postcode must be a string.',
            'postcode.min' => 'Postcode must be at least 1 character.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert country_code to uppercase if provided
        if ($this->has('country_code')) {
            $this->merge([
                'country_code' => strtoupper($this->country_code),
            ]);
        }
    }
}

