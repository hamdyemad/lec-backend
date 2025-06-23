<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            "reference" => $this->reference,
            'status' => new StatusResource($this->whenLoaded('status')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
