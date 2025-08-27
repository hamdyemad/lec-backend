<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "uuid" => $this->uuid,
            "header" => $this->translate('header'),
            "body" => $this->translate('body'),
            "image" => ($this->image) ? asset('/' . $this->image) : '',
            "created_at" => $this->created_at,
        ];
    }
}
