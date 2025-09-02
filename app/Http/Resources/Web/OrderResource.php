<?php

namespace App\Http\Resources\Web;

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
            'payment_method' => $this->payment_method,
            'stripe_public_key' => env('STRIPE_PUBLIC_KEY'),
            'payment' => $this->payment,
            'status' => new StatusResource($this->whenLoaded('status')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'delivery_information' => $this->whenLoaded('delivery_information'),
        ];
    }
}
