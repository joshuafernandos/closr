<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Receptionist;
use App\Ai\Agents\ScopeGuard;
use App\Catalogue\CatalogueManager;
use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Sources\NullProductSource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * The reply shown when the gatekeeper filters out an off-topic message.
     */
    private const OUT_OF_SCOPE_REPLY = "I'm Closr — I'm here to help you find and buy the right product in this store. Tell me what you're shopping for and I'll take it from there.";

    public function __construct(private CatalogueManager $catalogue) {}

    /**
     * Render the chat widget for a specific merchant, resolved by their public
     * widget key. The key is handed to the page so every shopper turn is tied
     * back to this merchant's catalogue rather than the local dev origin.
     */
    public function widget(Business $business): Response
    {
        return Inertia::render('chat', [
            'widgetKey' => $business->widget_key,
            'storeName' => $business->name,
        ]);
    }

    /**
     * Handle a shopper's chat turn and return Closr's structured reply.
     */
    public function message(Request $request): JsonResponse
    {
        // Validate manually and return JSON — the app only renders JSON exceptions
        // for api/* paths, and this widget endpoint is consumed by fetch().
        $validator = Validator::make($request->all(), [
            'key' => ['nullable', 'string'],
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['array', 'max:100'],
            'history.*.role' => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $history = $validated['history'] ?? [];

        // Gatekeeper: filter out anything that isn't about products or the store
        // before it ever reaches the Receptionist or the catalogue.
        $verdict = (new ScopeGuard($history))->prompt($validated['message']);

        if ($verdict['verdict'] === 'out_of_scope') {
            return response()->json([
                'reply' => self::OUT_OF_SCOPE_REPLY,
                'step' => 'out_of_scope',
                'products' => [],
            ]);
        }

        $response = (new Receptionist($history, $this->resolveOrigin($validated['key'] ?? null)))
            ->prompt($validated['message']);

        return response()->json([
            'reply' => $response['reply'],
            'step' => $response['step'],
            'products' => $response['products'] ?? [],
        ]);
    }

    /**
     * Resolve the catalogue origin for the merchant behind this widget key.
     *
     * With a key, the origin comes from that business's connected store. Without
     * one (the local dev / demo widget), fall back to the configured dev origin.
     */
    private function resolveOrigin(?string $widgetKey): ProductSource
    {
        if ($widgetKey === null) {
            return $this->devOrigin();
        }

        $business = Business::where('widget_key', $widgetKey)->first();

        return $business !== null
            ? $this->catalogue->forBusiness($business)
            : $this->devOrigin();
    }

    /**
     * Build the optional local dev / demo origin from config, if configured.
     */
    private function devOrigin(): ProductSource
    {
        $origin = config('closr.catalogue.dev_origin');

        if (empty($origin['config']['url'])) {
            return new NullProductSource;
        }

        return $this->catalogue->make($origin['driver'], $origin['config']);
    }
}
