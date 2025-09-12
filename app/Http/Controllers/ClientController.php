<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,accountant');
    }

    /**
     * Display a listing of clients
     */
    public function index(Request $request)
    {
        $query = Client::with('creator');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'overdue') {
                $query->overCreditLimit();
            }
        }

        // Search by name, email, or company
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['name', 'email', 'company_name', 'outstanding_balance', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $clients = $query->paginate(15);

        // Calculate stats with null safety
        $totalOutstanding = Client::sum('outstanding_balance') ?? 0;

        return inertia('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['status', 'search', 'sort_by', 'sort_order']),
            'stats' => [
                'total' => Client::count(),
                'active' => Client::active()->count(),
                'inactive' => Client::where('is_active', false)->count(),
                'overdue' => Client::overCreditLimit()->count(),
                'totalOutstanding' => $totalOutstanding,
            ]
        ]);
    }

    /**
     * Show the form for creating a new client
     */
    public function create()
    {
        return inertia('Clients/Create');
    }

    /**
     * Store a newly created client
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'company_name' => $request->company_name,
            'tax_id' => $request->tax_id,
            'website' => $request->website,
            'notes' => $request->notes,
            'credit_limit' => $request->credit_limit ?? 0,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified client
     */
    public function show(Client $client)
    {
        $client->load([
            'creator',
            'invoices' => function($query) {
                $query->latest()->take(10);
            },
            'payments' => function($query) {
                $query->latest()->take(10);
            }
        ]);

        $stats = [
            'total_invoices' => $client->invoices()->count(),
            'paid_invoices' => $client->paidInvoices()->count(),
            'pending_invoices' => $client->pendingInvoices()->count(),
            'overdue_invoices' => $client->overdueInvoices()->count(),
            'total_invoiced' => $client->total_invoiced,
            'total_paid' => $client->total_paid,
            'outstanding_amount' => $client->outstanding_amount,
        ];

        return inertia('Clients/Show', [
            'client' => $client,
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for editing the specified client
     */
    public function edit(Client $client)
    {
        return inertia('Clients/Edit', [
            'client' => $client
        ]);
    }

    /**
     * Update the specified client
     */
    public function update(Request $request, Client $client)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('clients')->ignore($client->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $client->update($request->all());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified client
     */
    public function destroy(Client $client)
    {
        // Check if client has invoices
        if ($client->invoices()->count() > 0) {
            return redirect()->route('clients.index')
                ->with('error', 'Cannot delete client with existing invoices.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    /**
     * Toggle client active status
     */
    public function toggleStatus(Client $client)
    {
        $client->update(['is_active' => !$client->is_active]);

        $status = $client->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Client {$status} successfully.");
    }

    /**
     * Get client dashboard data
     */
    public function dashboard(Client $client)
    {
        $client->load([
            'invoices' => function($query) {
                $query->latest()->take(5);
            },
            'payments' => function($query) {
                $query->latest()->take(5);
            }
        ]);

        $stats = [
            'total_invoices' => $client->invoices()->count(),
            'paid_invoices' => $client->paidInvoices()->count(),
            'pending_invoices' => $client->pendingInvoices()->count(),
            'overdue_invoices' => $client->overdueInvoices()->count(),
            'total_invoiced' => $client->total_invoiced,
            'total_paid' => $client->total_paid,
            'outstanding_amount' => $client->outstanding_amount,
            'credit_limit' => $client->credit_limit,
            'credit_utilization' => $client->credit_limit > 0 ? 
                ($client->outstanding_amount / $client->credit_limit) * 100 : 0,
        ];

        return inertia('Clients/Dashboard', [
            'client' => $client,
            'stats' => $stats
        ]);
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

    /**
     * Get client's own payments (for client portal)
     */
    public function myPayments(Request $request)
    {
        $user = auth()->user();
        
        // Find the client record for this user
        $client = Client::where('email', $user->email)->first();
        
        if (!$client) {
            return redirect()->route('dashboard')
                ->with('error', 'Client profile not found.');
        }

        $payments = $client->payments()
            ->with('invoice')
            ->latest()
            ->paginate(15);

        return inertia('Client/Payments', [
            'payments' => $payments,
            'client' => $client
        ]);
    }

    /**
     * Get client's own profile (for client portal)
     */
    public function myProfile()
    {
        $user = auth()->user();
        
        // Find the client record for this user
        $client = Client::where('email', $user->email)->first();
        
        if (!$client) {
            return redirect()->route('dashboard')
                ->with('error', 'Client profile not found.');
        }

        return inertia('Client/Profile', [
            'client' => $client,
            'user' => $user
        ]);
    }
}
