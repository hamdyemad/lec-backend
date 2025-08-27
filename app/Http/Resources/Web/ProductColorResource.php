<?php

namespace App\Http\Resources\Mobile;

use App\Http\Resources\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductColorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->translate('name'),
            'images' => AttachmentResource::collection($this->whenLoaded('attachments', function () {
                return $this->attachments->filter(function ($attachment) {
                    return in_array($attachment->type, ['image']);
                });
            })),
            'structural_image' => AttachmentResource::collection($this->whenLoaded('attachments', function () {
                return $this->attachments->filter(function ($attachment) {
                    return in_array($attachment->type, ['structural_image']);
                });
            })),
            'value' => $this->value,
            "created_at" => $this->created_at,
        ];
    }
}
