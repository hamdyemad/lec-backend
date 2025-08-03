<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductShowResource extends JsonResource
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
            'uuid' => $this->uuid,
            'title' => $this->translations('title'),
            'content' => $this->translations('content'),
            'category_id' => $this->category_id,
            'image' => ($this->image) ? asset('/' . $this->image) : '',
            'price' => $this->price,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'colors' => ProductColorShowResource::collection($this->whenLoaded('productColors')),
            'versions' => ProductVersionShowResource::collection($this->whenLoaded('versions')),
            'addons' => ProductAddonShowResource::collection($this->whenLoaded('addons')),
            'warrantlies' => ProductWarrantlyShowResource::collection($this->whenLoaded('warrantlies')),
        ];
    }
}
