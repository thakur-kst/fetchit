<?php

namespace Modules\Shared\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostcodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_code' => $this->country_code,
            'state_code' => $this->state_code,
            'city_code' => $this->city_code,
            'postcode' => $this->postcode,
            'locality' => $this->locality,
        ];
    }
}

