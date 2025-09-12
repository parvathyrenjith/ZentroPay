<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'description' => $this->description,
            'details' => $this->details,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_rate' => $this->tax_rate,
            'discount_rate' => $this->discount_rate,
            'line_total' => $this->line_total,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'final_amount' => $this->final_amount,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_unit_price' => $this->formatted_unit_price,
            'formatted_line_total' => $this->formatted_line_total,
            'formatted_final_amount' => $this->formatted_final_amount,
        ];
    }
}
