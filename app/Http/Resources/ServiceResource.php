<?php

namespace App\Http\Resources;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseCurrency = Currency::where('base_currency', 1)->first();
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reference' => $this->reference,
            'facts_in_details' => $this->facts_in_details,
            'subject_of_the_invitation' => $this->subject_of_the_invitation,
            'date' => $this->date,
            'price' => $this->price,
            'currency' => $baseCurrency->symbol,
            'created_at' => $this->created_at,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'client' => new UserResource($this->whenLoaded('client')),
            'case' => new CaseResource($this->whenLoaded('case')),
            'case_type' => new CaseTypeResource($this->whenLoaded('case_type')),
            'status' => new StatusResource($this->whenLoaded('status')),
            'creator' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
