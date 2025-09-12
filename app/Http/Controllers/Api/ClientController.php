<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
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

        return ClientResource::collection($clients);
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
            'created_by' => auth()->id(),
        ]);

        return new ClientResource($client->load('creator'));
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

        return new ClientResource($client);
    }

    /**
     * Update the specified client
     */
    public function update(Request $request, Client $client)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->id,
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

        return new ClientResource($client->load('creator'));
    }

    /**
     * Remove the specified client
     */
    public function destroy(Client $client)
    {
        // Check if client has invoices
        if ($client->invoices()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete client with existing invoices.'
            ], 422);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully.'
        ]);
    }

    /**
     * Toggle client active status
     */
    public function toggleStatus(Client $client)
    {
        $client->update(['is_active' => !$client->is_active]);

        return new ClientResource($client->load('creator'));
    }

    /**
     * Get client statistics
     */
    public function stats()
    {
        return response()->json([
            'total' => Client::count(),
            'active' => Client::active()->count(),
            'inactive' => Client::where('is_active', false)->count(),
            'overdue' => Client::overCreditLimit()->count(),
            'total_outstanding' => Client::sum('outstanding_balance'),
        ]);
    }
}
