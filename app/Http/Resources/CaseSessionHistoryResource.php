<?php

namespace App\Http\Resources;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseSessionHistoryResource extends JsonResource
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
            'case' => new CaseResource($this->whenLoaded('case')),
            'status' => new CaseSessionStatusResource($this->whenLoaded('status')),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
