<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductColorShowResource extends JsonResource
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
            'product_id' => $this->product_id,
            'name' => $this->translations('name'),
            'images' => AttachmentResource::collection($this->whenLoaded('attachments', function() {
                return $this->attachments->filter(function ($attachment) {
                    return in_array($attachment->type, ['image']);
                });
            })),
            'structural_image' => AttachmentResource::collection($this->whenLoaded('attachments', function() {
                return $this->attachments->filter(function ($attachment) {
                    return in_array($attachment->type, ['structural_image']);
                });
            })),
            'value' => $this->value,
            "created_at" => $this->created_at,
        ];
    }
}
