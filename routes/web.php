<?php

use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\MockWebserviceController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('tickets', TicketController::class)->except(['edit', 'update', 'destroy']);

    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::post('tickets/{ticket}/approve', [AdminTicketController::class, 'approve'])->name('tickets.approve');
        Route::post('tickets/{ticket}/reject', [AdminTicketController::class, 'reject'])->name('tickets.reject');
        Route::post('tickets/bulk-approve', [AdminTicketController::class, 'bulkApprove'])->name('tickets.bulk-approve');
    });
});

Route::get('/api/mock-webservice', MockWebserviceController::class);

require __DIR__.'/settings.php';
