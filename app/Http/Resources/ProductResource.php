<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductColorResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'category_id' => $this->category_id,
            'price'       => $this->price,

            'images'            => $this->images,
            'structural_images' => $this->structural_images,

            // Translated fields using Translatable trait
            'title'   => translate('title'),
            'content' => translate('content'),

            // Related resources (if loaded)
            'colors' => $this->whenLoaded('colors'),

            // 'versions'     => ProductVersion::collection($this->whenLoaded('versions')),
            // 'addons'       => ProductAddon::collection($this->whenLoaded('addons')),
            // 'warrantlies'  => ProductWarrantly::collection($this->whenLoaded('warrantlies')),
            // 'specifications' => $this->whenLoaded('specifications', fn () => $this->specifications->pluck('id')),
        ];
    }
}
