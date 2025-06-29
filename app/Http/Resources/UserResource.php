<?php

namespace App\Http\Resources;

use App\Http\Resources\Mobile\StatusResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            "status" => $this->status,
            "name" => $this->name,
            "email" => $this->email,
            "mobile" => $this->mobile,
            "mobile_code" => $this->mobile_code,
            "created_at" => $this->created_at,
        ];
    }
}
