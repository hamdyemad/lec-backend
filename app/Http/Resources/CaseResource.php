<?php

namespace App\Http\Resources;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseResource extends JsonResource
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
            'reference' => $this->reference,
            'case_name' => $this->case_name,
            'start_date' => $this->start_date,
            'created_at' => $this->created_at,
            'client' => new UserResource($this->whenLoaded('client')),
            'assigned_lawyer' => new UserResource($this->whenLoaded('lawyer')),
            'city' => new CityResource($this->whenLoaded('city')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'case_status' => new CaseStatusResource($this->whenLoaded('case_status')),
        ];
    }
}
