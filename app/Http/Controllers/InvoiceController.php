<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,accountant');
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'creator']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->has('client_id') && $request->client_id !== '') {
            $query->where('client_id', $request->client_id);
        }

        // Search by invoice number or client name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['invoice_number', 'invoice_date', 'due_date', 'total_amount', 'status', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $invoices = $query->paginate(15);
        $clients = Client::active()->get(['id', 'name', 'company_name']);

        return inertia('Invoices/Index', [
            'invoices' => $invoices,
            'clients' => $clients,
            'filters' => $request->only(['status', 'client_id', 'search', 'date_from', 'date_to', 'sort_by', 'sort_order']),
            'stats' => [
                'total' => Invoice::count(),
                'draft' => Invoice::draft()->count(),
                'pending' => Invoice::pending()->count(),
                'paid' => Invoice::paid()->count(),
                'overdue' => Invoice::overdue()->count(),
                'totalAmount' => Invoice::sum('total_amount'),
                'paidAmount' => Invoice::paid()->sum('total_amount'),
                'outstandingAmount' => Invoice::whereIn('status', ['pending', 'overdue'])->sum('total_amount'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new invoice
     */
    public function create()
    {
        $clients = Client::active()->get(['id', 'name', 'company_name', 'email']);
        
        return inertia('Invoices/Create', [
            'clients' => $clients,
            'defaultTerms' => config('invoice.default_terms', ''),
        ]);
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurring_end_date' => 'nullable|date|after:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.details' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice = Invoice::create([
            'client_id' => $request->client_id,
            'created_by' => Auth::id(),
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'terms_conditions' => $request->terms_conditions,
            'currency' => $request->currency ?? 'USD',
            'is_recurring' => $request->boolean('is_recurring', false),
            'recurring_frequency' => $request->recurring_frequency,
            'recurring_end_date' => $request->recurring_end_date,
        ]);

        // Create invoice items
        foreach ($request->items as $index => $itemData) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $itemData['description'],
                'details' => $itemData['details'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'] ?? 0,
                'discount_rate' => $itemData['discount_rate'] ?? 0,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'creator', 'items', 'payments']);
        
        return inertia('Invoices/Show', [
            'invoice' => $invoice
        ]);
    }

    /**
     * Show the form for editing the specified invoice
     */
    public function edit(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot edit paid invoice.');
        }

        $invoice->load(['items']);
        $clients = Client::active()->get(['id', 'name', 'company_name', 'email']);
        
        return inertia('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Cannot edit paid invoice.');
        }

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurring_end_date' => 'nullable|date|after:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.details' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice->update([
            'client_id' => $request->client_id,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'terms_conditions' => $request->terms_conditions,
            'currency' => $request->currency ?? 'USD',
            'is_recurring' => $request->boolean('is_recurring', false),
            'recurring_frequency' => $request->recurring_frequency,
            'recurring_end_date' => $request->recurring_end_date,
        ]);

        // Delete existing items and create new ones
        $invoice->items()->delete();
        
        foreach ($request->items as $index => $itemData) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $itemData['description'],
                'details' => $itemData['details'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'] ?? 0,
                'discount_rate' => $itemData['discount_rate'] ?? 0,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('invoices.index')
                ->with('error', 'Cannot delete paid invoice.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Generate PDF for invoice
     */
    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);
        
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => [
                'name' => config('app.name', 'ZentroPay'),
                'address' => config('invoice.company_address', ''),
                'phone' => config('invoice.company_phone', ''),
                'email' => config('invoice.company_email', ''),
                'website' => config('invoice.company_website', ''),
                'tax_id' => config('invoice.company_tax_id', ''),
            ]
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Send invoice via email
     */
    public function send(Invoice $invoice)
    {
        $invoice->load(['client']);
        
        // Mark as sent
        $invoice->markAsSent();
        
        // Send email (you can implement this based on your email service)
        // Mail::to($invoice->client->email)->send(new InvoiceMail($invoice));
        
        return redirect()->back()
            ->with('success', 'Invoice sent successfully.');
    }

    /**
     * Mark invoice as paid
     */
    public function markPaid(Invoice $invoice)
    {
        $invoice->markAsPaid();
        
        return redirect()->back()
            ->with('success', 'Invoice marked as paid.');
    }

    /**
     * Duplicate invoice
     */
    public function duplicate(Invoice $invoice)
    {
        $newInvoice = $invoice->replicate();
        $newInvoice->invoice_number = null; // Will be auto-generated
        $newInvoice->status = 'draft';
        $newInvoice->sent_at = null;
        $newInvoice->paid_at = null;
        $newInvoice->save();

        // Duplicate items
        foreach ($invoice->items as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        return redirect()->route('invoices.edit', $newInvoice)
            ->with('success', 'Invoice duplicated successfully.');
    }

    /**
     * Get client's own invoices (for client portal)
     */
    public function myInvoices(Request $request)
    {
        $user = auth()->user();
        
        // Find the client record for this user
        $client = Client::where('email', $user->email)->first();
        
        if (!$client) {
            return redirect()->route('dashboard')
                ->with('error', 'Client profile not found.');
        }

        $query = $client->invoices();

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Search by invoice number
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        $invoices = $query->latest()->paginate(15);

        return inertia('Client/Invoices', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'search']),
            'client' => $client
        ]);
    }
}
