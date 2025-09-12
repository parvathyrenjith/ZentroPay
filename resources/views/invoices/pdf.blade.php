<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }
        
        .company-info h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .company-info p {
            margin: 2px 0;
            color: #6b7280;
        }
        
        .invoice-info {
            text-align: right;
        }
        
        .invoice-info h2 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        
        .invoice-details {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .invoice-details td {
            padding: 5px 0;
            border: none;
        }
        
        .invoice-details td:first-child {
            font-weight: bold;
            width: 30%;
        }
        
        .client-info {
            margin-bottom: 30px;
        }
        
        .client-info h3 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .client-info p {
            margin: 2px 0;
            color: #6b7280;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th,
        .items-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .items-table th {
            background: #f9fafb;
            font-weight: bold;
            color: #1f2937;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            background: #f9fafb;
        }
        
        .notes {
            margin-bottom: 30px;
        }
        
        .notes h3 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        
        .notes p {
            margin: 5px 0;
            color: #6b7280;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-pending { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ $company['name'] }}</h1>
            @if($company['address'])
                <p>{{ $company['address'] }}</p>
            @endif
            @if($company['phone'])
                <p>Phone: {{ $company['phone'] }}</p>
            @endif
            @if($company['email'])
                <p>Email: {{ $company['email'] }}</p>
            @endif
            @if($company['website'])
                <p>Website: {{ $company['website'] }}</p>
            @endif
            @if($company['tax_id'])
                <p>Tax ID: {{ $company['tax_id'] }}</p>
            @endif
        </div>
        
        <div class="invoice-info">
            <h2>INVOICE</h2>
            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Invoice Number:</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td>Invoice Date:</td>
                        <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td>Due Date:</td>
                        <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td>Status:</td>
                        <td>
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ $invoice->status }}
                            </span>
                        </td>
                    </tr>
                    @if($invoice->currency !== 'USD')
                        <tr>
                            <td>Currency:</td>
                            <td>{{ $invoice->currency }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    
    <div class="client-info">
        <h3>Bill To:</h3>
        <p><strong>{{ $invoice->client->name }}</strong></p>
        @if($invoice->client->company_name)
            <p>{{ $invoice->client->company_name }}</p>
        @endif
        @if($invoice->client->address)
            <p>{{ $invoice->client->address }}</p>
        @endif
        @if($invoice->client->city || $invoice->client->state || $invoice->client->postal_code)
            <p>
                @if($invoice->client->city){{ $invoice->client->city }}@endif
                @if($invoice->client->city && $invoice->client->state), @endif
                @if($invoice->client->state){{ $invoice->client->state }}@endif
                @if($invoice->client->postal_code) {{ $invoice->client->postal_code }}@endif
            </p>
        @endif
        @if($invoice->client->country)
            <p>{{ $invoice->client->country }}</p>
        @endif
        @if($invoice->client->email)
            <p>Email: {{ $invoice->client->email }}</p>
        @endif
        @if($invoice->client->phone)
            <p>Phone: {{ $invoice->client->phone }}</p>
        @endif
        @if($invoice->client->tax_id)
            <p>Tax ID: {{ $invoice->client->tax_id }}</p>
        @endif
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if($item->details)
                            <br><small style="color: #6b7280;">{{ $item->details }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->discount_rate, 1) }}%</td>
                    <td class="text-right">{{ number_format($item->tax_rate, 1) }}%</td>
                    <td class="text-right">${{ number_format($item->final_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
                <tr>
                    <td>Discount:</td>
                    <td class="text-right">-${{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if($invoice->tax_amount > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>
    
    @if($invoice->notes || $invoice->terms_conditions)
        <div class="notes">
            @if($invoice->notes)
                <h3>Notes:</h3>
                <p>{{ $invoice->notes }}</p>
            @endif
            
            @if($invoice->terms_conditions)
                <h3>Terms & Conditions:</h3>
                <p>{{ $invoice->terms_conditions }}</p>
            @endif
        </div>
    @endif
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This invoice was generated on {{ now()->format('M d, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
