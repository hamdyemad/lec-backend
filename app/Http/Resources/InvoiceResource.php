<?php

namespace App\Http\Resources;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'paid_status' => $this->paid_status,
            'created_at' => $this->created_at,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'account' => $this->whenLoaded('account'),
        ];
    }
}
