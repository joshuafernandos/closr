<?php

use App\Http\Controllers\Businesses\BusinessInvitationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConnectorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureBusinessMembership;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Keyless demo widget (falls back to the configured dev origin).
Route::inertia('/chat', 'chat')->name('chat');
// Per-merchant widget, resolved by the business's public widget key.
Route::get('/widget/{business:widget_key}', [ChatController::class, 'widget'])->name('widget');
Route::post('/chat/message', [ChatController::class, 'message'])->name('chat.message');

Route::prefix('{current_business}')
    ->middleware(['auth', 'verified', EnsureBusinessMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('connectors', [ConnectorController::class, 'edit'])->name('connector.edit');
    Route::post('connectors', [ConnectorController::class, 'store'])->name('connector.store');
    Route::delete('connectors', [ConnectorController::class, 'destroy'])->name('connector.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [BusinessInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [BusinessInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
