<?php

use App\Http\Controllers\Businesses\BusinessInvitationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConnectorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Keyless demo widget (falls back to the configured dev origin).
Route::inertia('/chat', 'chat')->name('chat');
// Live widget, resolved by its public widget key.
Route::get('/widget/{widget}', [ChatController::class, 'widget'])->name('widget');
// Embeddable loader script the merchant drops onto their storefront.
Route::get('/widget/{widget}/embed.js', [ChatController::class, 'embed'])->name('widget.embed');
Route::post('/chat/message', [ChatController::class, 'message'])->name('chat.message');
// Browse a category's products directly, with no model call.
Route::post('/chat/category', [ChatController::class, 'category'])->name('chat.category');
// Resolve a cart URL for a chosen product variation (size, colour, …).
Route::post('/chat/variation-cart', [ChatController::class, 'variantCart'])->name('chat.variation-cart');
// Restore a shopper's prior transcript for their session.
Route::post('/chat/conversation', [ChatController::class, 'conversation'])->name('chat.conversation');
// Report a shopper-side action (added to cart, left the page).
Route::post('/chat/action', [ChatController::class, 'action'])->name('chat.action');

// Sandbox page to preview the embeddable widget on a bare HTML page.
Route::get('/test', [ChatController::class, 'test'])->name('widget.test');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('connectors', [ConnectorController::class, 'index'])->name('connector.index');
    Route::post('connectors', [ConnectorController::class, 'store'])->name('connector.store');
    Route::delete('connectors/{origin}', [ConnectorController::class, 'destroy'])->name('connector.destroy');

    Route::get('widgets', [WidgetController::class, 'index'])->name('widgets.index');
    Route::post('widgets', [WidgetController::class, 'store'])->name('widgets.store');
    Route::get('widgets/{widget}/edit', [WidgetController::class, 'edit'])->name('widgets.edit');
    Route::put('widgets/{widget}', [WidgetController::class, 'update'])->name('widgets.update');
    Route::delete('widgets/{widget}', [WidgetController::class, 'destroy'])->name('widgets.destroy');

    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
});

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [BusinessInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [BusinessInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';

Route::get('/test-2', function () {
    $key = 'clsr_pub_phedhnzbt6wa1kmw6usfgb3oiyu8slkz';
    $embedSrc = route('widget.embed', $key);

    $html = <<<HTML
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Closr inline widget sandbox</title>
        <style>
            body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; padding: 64px 24px; color: #171717; background: #fafafa; }
            .wrap { max-width: 720px; margin: 0 auto; }
            #closr-widget { margin-top: 32px; }
        </style>
    </head>
    <body>
        <div class="wrap">
            <h1>Inline widget</h1>
            <p>The Closr component below is embedded inline, flowing as an ordinary page section.</p>

            <!-- Closr inline widget -->
            <div id="closr-widget"></div>
            <script src="{$embedSrc}" data-closr-key="{$key}" data-closr-container="#closr-widget" async></script>
        </div>
    </body>
    </html>
    HTML;

    return response($html, 200, ['Content-Type' => 'text/html']);
});
