<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnectWooCommerceRequest;
use App\Models\CatalogueOrigin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConnectorController extends Controller
{
    /**
     * Show the catalogue connector page for the current business.
     */
    public function edit(Request $request): Response
    {
        $business = $request->user()->currentBusiness;

        abort_if($business === null, 403);

        Gate::authorize('view', $business);

        $origin = $business->catalogueOrigin;

        return Inertia::render('connectors', [
            'connection' => $origin === null ? null : [
                'driver' => $origin->driver,
                'url' => (string) ($origin->config['url'] ?? ''),
            ],
        ]);
    }

    /**
     * Connect (or reconnect) the current business to a WooCommerce store.
     */
    public function store(ConnectWooCommerceRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness;

        CatalogueOrigin::updateOrCreate(
            ['business_id' => $business->id],
            ['driver' => 'woocommerce', 'config' => $request->credentials()],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('WooCommerce store connected.')]);

        return to_route('connector.edit');
    }

    /**
     * Disconnect the current business's catalogue origin.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness;

        abort_if($business === null, 403);

        Gate::authorize('update', $business);

        $business->catalogueOrigin()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store disconnected.')]);

        return to_route('connector.edit');
    }
}
