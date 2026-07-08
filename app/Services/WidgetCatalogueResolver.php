<?php

namespace App\Services;

use App\Catalogue\CatalogueManager;
use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Sources\NullProductSource;
use App\Models\Widget;
use Illuminate\Contracts\Config\Repository;

/**
 * Resolves which catalogue a widget request should read from, and enriches the
 * products it returns with storefront cart URLs.
 */
class WidgetCatalogueResolver
{
    public function __construct(
        private CatalogueManager $catalogue,
        private Repository $config,
    ) {}

    /**
     * Resolve the catalogue source for the widget behind this widget key.
     *
     * With a key, the source comes from the widget's connected origin. Without
     * one (the local dev / demo widget), or for an unknown key, fall back to the
     * configured dev origin.
     */
    public function originForKey(?string $widgetKey): ProductSource
    {
        if ($widgetKey === null) {
            return $this->devOrigin();
        }

        $widget = Widget::where('widget_key', $widgetKey)->first();

        return $widget !== null
            ? $this->catalogue->forOrigin($widget->catalogueOrigin)
            : $this->devOrigin();
    }

    /**
     * Enrich recommended products for the widget: attach each product's
     * storefront cart URL, and re-hydrate the authoritative storefront URL and
     * variation options from the origin.
     *
     * Products that already carry `url`/`variations` (browsed straight from the
     * catalogue) are left as-is. Products the assistant echoed back by id are
     * looked up via the origin so their variations, storefront URL and price are
     * trustworthy rather than model-generated. The assistant's `reason` is
     * preserved. Cart URLs are null for demo origins, where the widget falls
     * back to its local bag.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    public function enrichProducts(array $products, ProductSource $origin): array
    {
        return array_map(function (array $product) use ($origin): array {
            $id = isset($product['id']) ? (int) $product['id'] : null;

            $needsHydration = $id !== null
                && (! array_key_exists('url', $product) || ! array_key_exists('variations', $product));

            if ($needsHydration && ($hydrated = $origin->find($id)) !== null) {
                $reason = $product['reason'] ?? null;
                $product = $hydrated->toArray();

                if ($reason !== null) {
                    $product['reason'] = $reason;
                }
            }

            $product['variations'] ??= [];
            $product['url'] ??= null;
            $product['cartUrl'] = $id !== null ? $origin->cartUrl($id) : null;

            return $product;
        }, $products);
    }

    /**
     * Build the optional local dev / demo origin from config, if configured.
     */
    private function devOrigin(): ProductSource
    {
        $origin = $this->config->get('closr.catalogue.dev_origin');

        if (empty($origin['config']['url'])) {
            return new NullProductSource;
        }

        return $this->catalogue->make($origin['driver'], $origin['config']);
    }
}
