<?php

use App\Http\Controllers\Businesses\BusinessController;
use App\Http\Controllers\Businesses\BusinessInvitationController;
use App\Http\Controllers\Businesses\BusinessMemberController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Middleware\EnsureBusinessMembership;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/businesses', [BusinessController::class, 'index'])->name('businesses.index');
    Route::post('settings/businesses', [BusinessController::class, 'store'])->name('businesses.store');

    Route::middleware(EnsureBusinessMembership::class)->group(function () {
        Route::get('settings/businesses/{business}', [BusinessController::class, 'edit'])->name('businesses.edit');
        Route::patch('settings/businesses/{business}', [BusinessController::class, 'update'])->name('businesses.update');
        Route::delete('settings/businesses/{business}', [BusinessController::class, 'destroy'])->name('businesses.destroy');
        Route::post('settings/businesses/{business}/switch', [BusinessController::class, 'switch'])->name('businesses.switch');
        Route::delete('settings/businesses/{business}/leave', [BusinessController::class, 'leave'])->name('businesses.leave');

        Route::patch('settings/businesses/{business}/members/{user}', [BusinessMemberController::class, 'update'])->name('businesses.members.update');
        Route::delete('settings/businesses/{business}/members/{user}', [BusinessMemberController::class, 'destroy'])->name('businesses.members.destroy');

        Route::post('settings/businesses/{business}/invitations', [BusinessInvitationController::class, 'store'])->name('businesses.invitations.store');
        Route::delete('settings/businesses/{business}/invitations/{invitation}', [BusinessInvitationController::class, 'destroy'])->name('businesses.invitations.destroy');
    });
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
