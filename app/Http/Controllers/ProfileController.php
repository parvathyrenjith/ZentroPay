<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's additional profile information (role-specific).
     */
    public function updateAdditional(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
        ]);

        $user->update($request->only(['phone', 'address', 'company_name', 'tax_id']));

        return Redirect::route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return Redirect::route('profile.edit')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Get user dashboard data based on role
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $data = [];

        if ($user->isAdmin() || $user->isAccountant()) {
            // Admin/Accountant dashboard
            $data = [
                'totalClients' => \App\Models\Client::count(),
                'totalInvoices' => \App\Models\Invoice::count(),
                'totalRevenue' => \App\Models\Invoice::where('status', 'paid')->sum('total_amount'),
                'pendingInvoices' => \App\Models\Invoice::where('status', 'pending')->count(),
                'overdueInvoices' => \App\Models\Invoice::where('status', 'overdue')->count(),
                'recentInvoices' => \App\Models\Invoice::with('client')->latest()->take(5)->get(),
                'recentClients' => \App\Models\Client::latest()->take(5)->get(),
            ];
        } else {
            // Client dashboard
            $data = [
                'totalInvoices' => $user->invoices()->count(),
                'paidInvoices' => $user->invoices()->where('status', 'paid')->count(),
                'pendingInvoices' => $user->invoices()->where('status', 'pending')->count(),
                'overdueInvoices' => $user->invoices()->where('status', 'overdue')->count(),
                'totalAmount' => $user->invoices()->sum('total_amount'),
                'paidAmount' => $user->invoices()->where('status', 'paid')->sum('total_amount'),
                'outstandingAmount' => $user->invoices()->whereIn('status', ['pending', 'overdue'])->sum('total_amount'),
                'recentInvoices' => $user->invoices()->with('client')->latest()->take(5)->get(),
            ];
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'data' => $data,
        ]);
    }
}
