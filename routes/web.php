<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Client Management Routes
Route::middleware(['auth', 'role:admin,accountant'])->group(function () {
    Route::resource('clients', ClientController::class);
    Route::patch('clients/{client}/toggle-status', [ClientController::class, 'toggleStatus'])->name('clients.toggle-status');
    Route::get('clients/{client}/dashboard', [ClientController::class, 'dashboard'])->name('clients.dashboard');
});

// Client Portal Routes (for client users)
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('my-invoices', [ClientController::class, 'myInvoices'])->name('client.invoices');
    Route::get('my-payments', [ClientController::class, 'myPayments'])->name('client.payments');
    Route::get('my-profile', [ClientController::class, 'myProfile'])->name('client.profile');
});

// Invoice Management Routes
Route::middleware(['auth', 'role:admin,accountant'])->group(function () {
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
});

// Client Portal Routes for Invoices
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('my-invoices', [InvoiceController::class, 'myInvoices'])->name('client.invoices');
    Route::get('my-invoices/{invoice}', [InvoiceController::class, 'show'])->name('client.invoices.show');
    Route::get('my-invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('client.invoices.pdf');
});

require __DIR__.'/auth.php';
