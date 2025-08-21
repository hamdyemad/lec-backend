<?php

namespace App\Http\Resources;

use App\Http\Resources\Mobile\StatusResource;
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
            'payment_method' => $this->payment_method,
            'payment' => $this->payment,
            'client' => new UserResource($this->whenLoaded('client')),
            'items' => OrderItemResource::collection($this->whenLoaded('items'))
        ];
    }
}
