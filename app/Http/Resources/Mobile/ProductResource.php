<?php

namespace App\Http\Resources\Mobile;

use App\Http\Resources\SpecificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authUser = auth()->user();
        (in_array($this->id, $authUser->favorite_products()->pluck('product_id')->toArray())) ? $active = true : $active = false;

        $baseCurrency = \App\Models\Currency::where('base_currency', 1)->first();
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->translate('title'),
            'content' => $this->translate('content'),
            'category_id' => $this->category_id,
            'images' => $this->images,
            'favorite' => $active,
            'structural_images' => $this->structural_images,
            'base_curreny' => $baseCurrency->symbol ?? '',
            'price' => $this->price,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'colors' => ProductColorResource::collection($this->whenLoaded('productColors')),
            'versions' => ProductVersionResource::collection($this->whenLoaded('versions')),
            'specifications' => SpecificationResource::collection($this->whenLoaded('specifications')),
            'warrantlies' => ProductWarrantlyResource::collection($this->whenLoaded('warrantlies')),
            'addons' => ProductAddonResource::collection($this->whenLoaded('addons')),
        ];
    }
}
