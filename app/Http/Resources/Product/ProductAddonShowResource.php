<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAddonShowResource extends JsonResource
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
            "product_id" => $this->product_id,
            "name" => $this->translations('name'),
            "price" => $this->price,
            "created_at" => $this->created_at,
        ];
    }
}
