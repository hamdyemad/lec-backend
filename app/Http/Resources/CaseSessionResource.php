<?php

namespace App\Http\Resources;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseSessionResource extends JsonResource
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
            'session_date' => $this->session_date,
            'session_hour' => $this->session_hour,
            'duration' => $this->duration,
            'court_name' => $this->court_name,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'histories' => CaseSessionHistoryResource::collection($this->whenLoaded('histories', function($histories) {
                return $histories->sortByDesc('created_at');
            })),
            'status' => new CaseSessionStatusResource($this->whenLoaded('status')),
            'case' => new CaseResource($this->whenLoaded('case')),
        ];
    }
}
