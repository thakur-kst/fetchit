<?php

namespace Modules\Shared\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\DBCore\Models\Core\Country;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

class GetCitiesRequest extends FormRequest
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
                    $cacheKey = CacheKeyGenerator::entity('shared', 'country', strtoupper($value));

                    $exists = $cacheService->remember($cacheKey, function () use ($value) {
                        return Country::where('iso2', strtoupper($value))->exists();
                    });

                    if (!$exists) {
                        $fail('The selected country code is invalid.');
                    }
                },
            ],
            'search' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:20',
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
            'search.required' => 'Search term is required.',
            'search.string' => 'Search term must be a string.',
            'search.min' => 'Search term must be at least 1 character.',
            'search.max' => 'Search term cannot exceed 255 characters.',
            'per_page.integer' => 'Per page must be an integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 20.',
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

