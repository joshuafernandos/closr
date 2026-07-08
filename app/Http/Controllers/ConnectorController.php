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
     * Show the catalogue connector page with the business's connected sources.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness;

        abort_if($business === null, 403);

        Gate::authorize('view', $business);

        return Inertia::render('connectors', [
            'connections' => $business->catalogueOrigins()
                ->latest()
                ->get()
                ->map(fn (CatalogueOrigin $origin): array => [
                    'id' => $origin->id,
                    'driver' => $origin->driver,
                    'name' => $origin->name ?? (string) ($origin->config['url'] ?? ''),
                    'url' => (string) ($origin->config['url'] ?? ''),
                ])
                ->all(),
        ]);
    }

    /**
     * Connect a new WooCommerce store as a catalogue source for the business.
     */
    public function store(ConnectWooCommerceRequest $request): RedirectResponse
    {
        $business = $request->user()->currentBusiness;

        $credentials = $request->credentials();

        $business->catalogueOrigins()->create([
            'driver' => 'woocommerce',
            'name' => $this->labelFor($credentials['url']),
            'config' => $credentials,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('WooCommerce store connected.')]);

        return to_route('connector.index');
    }

    /**
     * Disconnect one of the business's catalogue sources.
     */
    public function destroy(Request $request, CatalogueOrigin $origin): RedirectResponse
    {
        $business = $request->user()->currentBusiness;

        abort_if($business === null, 403);

        Gate::authorize('update', $business);
        abort_unless($origin->business_id === $business->id, 403);

        $origin->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store disconnected.')]);

        return to_route('connector.index');
    }

    /**
     * Build a friendly label for a source from its store URL (the host).
     */
    private function labelFor(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $url;
    }
}
