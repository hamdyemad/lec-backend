<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
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
            "attachable_type" => $this->attachable_type,
            "type" => $this->type,
            "attachable_id" => $this->attachable_id,
            "path" => ($this->path) ? asset('/' . $this->path) : '',
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
