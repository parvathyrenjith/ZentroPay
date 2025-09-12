<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'company_name' => $this->company_name,
            'tax_id' => $this->tax_id,
            'website' => $this->website,
            'notes' => $this->notes,
            'credit_limit' => $this->credit_limit,
            'outstanding_balance' => $this->outstanding_balance,
            'is_active' => $this->is_active,
            'full_address' => $this->full_address,
            'available_credit' => $this->available_credit,
            'has_exceeded_credit_limit' => $this->hasExceededCreditLimit(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'invoices_count' => $this->when(isset($this->invoices_count), $this->invoices_count),
            'payments_count' => $this->when(isset($this->payments_count), $this->payments_count),
        ];
    }
}
