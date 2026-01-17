<?php

namespace Modules\Shared\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'country_code' => $this->iso2,
            'postcode_validation' => $this->postcode_validation,
            'phone_validations' => $this->phone_validations,
        ];
    }
}

