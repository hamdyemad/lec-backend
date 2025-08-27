<?php

namespace App\Http\Resources\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            "name" => $this->translate('name'),
            "image" => ($this->image) ? asset('/' . $this->image) : '',
            "created_at" => $this->created_at,
        ];
    }
}
