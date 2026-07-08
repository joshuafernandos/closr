<?php

namespace App\Http\Controllers;

use App\Ai\Agents\Receptionist;
use App\Ai\Agents\ScopeGuard;
use App\Catalogue\CatalogueManager;
use App\Catalogue\Product;
use App\Enums\ConversationAction;
use App\Models\Widget;
use App\Services\ConversationPresenter;
use App\Services\ConversationRecorder;
use App\Services\WidgetCatalogueResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * The reply shown when the gatekeeper filters out an off-topic message.
     */
    private const OUT_OF_SCOPE_REPLY = "I'm here to help you find and buy the right product in this store. Tell me what you're shopping for and I'll take it from there.";

    public function __construct(
        private CatalogueManager $catalogue,
        private ConversationRecorder $conversations,
        private ConversationPresenter $presenter,
        private WidgetCatalogueResolver $origins,
    ) {}

    /**
     * Render the chat widget, resolved by its public widget key. The key is
     * handed to the page so every shopper turn is tied back to this widget's
     * catalogue source rather than the local dev origin.
     */
    public function widget(Widget $widget): Response
    {
        // The shopper-facing widget always renders light, regardless of the
        // viewer's OS theme — override the cookie-derived appearance share.
        View::share('appearance', 'light');

        return Inertia::render('chat', [
            'widgetKey' => $widget->widget_key,
            'storeName' => $widget->name,
            'categories' => $this->catalogue->categoriesFor($widget),
            'template' => $widget->template->value,
            'accentColor' => $widget->accent_color,
        ]);
    }

    /**
     * Serve the merchant's embeddable loader script. Merchants drop a single
     * <script> tag on their storefront; the widget key in the path is all we
     * need — the catalogue source (WooCommerce, etc.) is resolved server-side
     * from the business's connected origin.
     *
     * The script mounts differently per template:
     *  - `chat` floats a launcher button that toggles an iframe pinned to the
     *    bottom-right corner.
     *  - `component` (inline) inserts the iframe directly where the <script>
     *    tag sits, so it flows as an ordinary in-page section rather than an
     *    overlay.
     */
    public function embed(Widget $widget): \Illuminate\Http\Response
    {
        $widgetUrl = route('widget', $widget);
        $accent = $this->jsString($widget->accent_color);
        $isInline = $widget->template->value === 'component';

        $mount = $isInline
            ? $this->inlineMountScript()
            : $this->floatingMountScript($accent);

        $script = <<<JS
        (function () {
            if (window.__closrLoaded) { return; }
            window.__closrLoaded = true;

            var WIDGET_URL = {$this->jsString($widgetUrl)};
            // Capture the loader's own <script> tag now — currentScript is null
            // by the time DOMContentLoaded fires, and the inline mount needs it
            // to know where to drop the section.
            var SCRIPT = document.currentScript;

            function mount() {
                {$mount}
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', mount);
            } else {
                mount();
            }
        })();
        JS;

        return response($script, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Mount body for the floating `chat` template: a launcher button toggling a
     * corner-pinned iframe.
     */
    private function floatingMountScript(string $accent): string
    {
        return <<<JS
        var iframe = document.createElement('iframe');
                iframe.src = WIDGET_URL;
                iframe.title = 'Closr chat';
                iframe.style.cssText = 'position:fixed;bottom:96px;right:24px;width:400px;max-width:calc(100vw - 48px);height:640px;max-height:calc(100vh - 120px);border:0;border-radius:16px;box-shadow:0 12px 48px rgba(0,0,0,0.18);z-index:2147483000;display:none;background:#fff;';

                var button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('aria-label', 'Open chat');
                button.style.cssText = 'position:fixed;bottom:24px;right:24px;width:56px;height:56px;border:0;border-radius:9999px;background:' + {$accent} + ';color:#fff;cursor:pointer;z-index:2147483001;box-shadow:0 8px 24px rgba(0,0,0,0.24);display:flex;align-items:center;justify-content:center;';
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/></svg>';

                var open = false;
                button.addEventListener('click', function () {
                    open = !open;
                    iframe.style.display = open ? 'block' : 'none';
                });

                document.body.appendChild(iframe);
                document.body.appendChild(button);
        JS;
    }

    /**
     * Mount body for the inline `component` template: drop the iframe in the
     * document flow right where the loader <script> sits, so it renders as an
     * ordinary page section instead of a floating overlay.
     */
    private function inlineMountScript(): string
    {
        return <<<'JS'
        var iframe = document.createElement('iframe');
                iframe.src = WIDGET_URL;
                iframe.title = 'Closr chat';
                // Start at the collapsed search-box height; the widget posts its
                // real height back so there's no empty space beneath it.
                iframe.style.cssText = 'display:block;width:100%;height:96px;border:0;border-radius:16px;background:#fff;';

                // The widget reports its content height (collapsed search box, then
                // a taller panel once the conversation starts). Resize to match —
                // animating only a shopper-driven expansion, never a page load /
                // restore, so refreshing doesn't replay the grow animation.
                window.addEventListener('message', function (event) {
                    if (event.source !== iframe.contentWindow) { return; }

                    var data = event.data;

                    if (data && data.type === 'closr:resize' && typeof data.height === 'number') {
                        iframe.style.transition = data.animate ? 'height 320ms cubic-bezier(0.22,1,0.36,1)' : 'none';
                        iframe.style.height = data.height + 'px';
                    }
                });

                // Prefer an explicit container (data-closr-container="#id"); fall
                // back to inserting right after the loader script tag.
                var selector = SCRIPT && SCRIPT.getAttribute('data-closr-container');
                var target = selector ? document.querySelector(selector) : null;

                if (target) {
                    target.appendChild(iframe);
                } else if (SCRIPT && SCRIPT.parentNode) {
                    SCRIPT.parentNode.insertBefore(iframe, SCRIPT.nextSibling);
                } else {
                    document.body.appendChild(iframe);
                }
        JS;
    }

    /**
     * Render a bare HTML sandbox page that loads the embeddable widget exactly
     * as a merchant's storefront would, so we can preview the launcher + iframe
     * end-to-end. Uses the first business as the demo merchant.
     */
    public function test(): \Illuminate\Http\Response
    {
        $widget = Widget::query()->orderBy('id')->first();

        if ($widget === null) {
            return response('<h1>No widget found. Create a widget to preview it.</h1>', 200, [
                'Content-Type' => 'text/html',
            ]);
        }

        $storeName = $widget->business->name;
        $embedSrc = route('widget.embed', $widget);

        $html = <<<HTML
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Closr widget sandbox — {$storeName}</title>
            <style>
                body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; padding: 64px 24px; color: #171717; background: #fafafa; }
                .wrap { max-width: 640px; margin: 0 auto; }
                code { background: #ececec; padding: 2px 6px; border-radius: 6px; font-size: 13px; }
            </style>
        </head>
        <body>
            <div class="wrap">
                <h1>Pretend storefront for {$storeName}</h1>
                <p>This is a plain HTML page with nothing but the Closr embed snippet. Look for the chat launcher in the bottom-right corner — click it to open the widget.</p>
                <p>The snippet only carries the public widget key (<code>{$widget->widget_key}</code>); the catalogue source is resolved server-side from this widget's connected store.</p>
            </div>

            <!-- Closr widget -->
            <script src="{$embedSrc}" data-closr-key="{$widget->widget_key}" async></script>
        </body>
        </html>
        HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Encode a PHP value as a safe JavaScript literal for inlining.
     */
    private function jsString(string $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
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
            'session' => ['nullable', 'string', 'max:64'],
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

        $conversation = $this->conversations->resolveForSession($validated['key'] ?? null, $validated['session'] ?? null);

        if ($conversation !== null) {
            $this->conversations->recordTurn($conversation, 'user', $validated['message']);
        }

        // Gatekeeper: filter out anything that isn't about products or the store
        // before it ever reaches the Receptionist or the catalogue.
        $verdict = (new ScopeGuard($history))->prompt($validated['message']);

        if ($verdict['verdict'] === 'out_of_scope') {
            if ($conversation !== null) {
                $this->conversations->recordTurn($conversation, 'assistant', self::OUT_OF_SCOPE_REPLY, 'out_of_scope');
            }

            return response()->json([
                'reply' => self::OUT_OF_SCOPE_REPLY,
                'step' => 'out_of_scope',
                'products' => [],
            ]);
        }

        $origin = $this->origins->originForKey($validated['key'] ?? null);

        $response = (new Receptionist($history, $origin))
            ->prompt($validated['message']);

        $products = $this->origins->enrichProducts($response['products'] ?? [], $origin);

        if ($conversation !== null) {
            $this->conversations->recordTurn($conversation, 'assistant', $response['reply'], $response['step'], $products);
        }

        return response()->json([
            'reply' => $response['reply'],
            'step' => $response['step'],
            'products' => $products,
        ]);
    }

    /**
     * Browse a single category's products directly, with no model call.
     *
     * The widget's quick-pick category chips return the same catalogue listing
     * on every open, so there is nothing for the assistant to reason about —
     * we fetch the category's products straight from the merchant's store and
     * let the widget render them. This keeps a "tap a category" interaction
     * entirely free of Claude calls.
     */
    public function category(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => ['nullable', 'string'],
            'session' => ['nullable', 'string', 'max:64'],
            'category' => ['required', 'string', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $origin = $this->origins->originForKey($validated['key'] ?? null);

        try {
            $products = $origin->search($validated['category'])
                ->map(fn (Product $product): array => $product->toArray())
                ->values()
                ->all();
        } catch (\Throwable) {
            $products = [];
        }

        $products = $this->origins->enrichProducts($products, $origin);

        $conversation = $this->conversations->resolveForSession($validated['key'] ?? null, $validated['session'] ?? null);

        if ($conversation !== null) {
            $this->conversations->recordTurn($conversation, 'user', "Show me {$validated['category']}");

            $reply = $products === []
                ? "I couldn't find anything in {$validated['category']} right now."
                : "Here's what we have in {$validated['category']}:";

            $this->conversations->recordTurn($conversation, 'assistant', $reply, 'recommend', $products);
        }

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Restore a shopper's prior transcript so the widget can pick up where the
     * session left off. Returns an empty transcript when the session is new.
     */
    public function conversation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => ['nullable', 'string'],
            'session' => ['required', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $conversation = $this->conversations->findForSession($validated['key'] ?? null, $validated['session']);

        if ($conversation === null) {
            return response()->json(['messages' => []]);
        }

        return response()->json([
            'messages' => $this->presenter->transcript($conversation),
        ]);
    }

    /**
     * Record a shopper-side action (added an item to the cart, visited a product
     * page) against their conversation so the merchant sees it on the dashboard.
     * Conversations default to "Left", so there's no action to report for that.
     */
    public function action(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => ['nullable', 'string'],
            'session' => ['required', 'string', 'max:64'],
            'action' => ['required', 'string', 'in:added_to_cart,visited_product_page'],
            // The product the shopper acted on, plus any variation they chose,
            // so an "added to cart" action can be replayed inline in the transcript.
            'product' => ['nullable', 'array'],
            'product.id' => ['required_with:product', 'integer'],
            'product.title' => ['required_with:product', 'string', 'max:200'],
            'product.price' => ['nullable', 'numeric'],
            'product.thumbnail' => ['nullable', 'string'],
            'variant' => ['nullable', 'array'],
            'variant.*' => ['string', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $action = ConversationAction::from($validated['action']);

        $conversation = $this->conversations->findForSession($validated['key'] ?? null, $validated['session']);

        if ($conversation !== null) {
            $this->conversations->markAction($conversation, $action);

            // Only cart additions are replayed inline; "left" and bare page
            // visits stay as the header status badge.
            if ($action === ConversationAction::AddedToCart && isset($validated['product'])) {
                $this->conversations->recordEvent(
                    $conversation,
                    $action,
                    $validated['product'],
                    $validated['variant'] ?? [],
                );
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Resolve the storefront cart URL for a chosen product variation. The widget
     * calls this once a shopper picks their options (size, colour, …) so it can
     * add the exact variation to the merchant's cart. Returns a null cart URL for
     * demo origins, where the widget falls back to its local bag.
     */
    public function variantCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => ['nullable', 'string'],
            'session' => ['nullable', 'string', 'max:64'],
            'productId' => ['required', 'integer'],
            'attributes' => ['required', 'array'],
            'attributes.*' => ['required', 'string', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $origin = $this->origins->originForKey($validated['key'] ?? null);

        try {
            $cartUrl = $origin->variantCartUrl((int) $validated['productId'], $validated['attributes']);
        } catch (\Throwable) {
            $cartUrl = null;
        }

        return response()->json(['cartUrl' => $cartUrl]);
    }
}
