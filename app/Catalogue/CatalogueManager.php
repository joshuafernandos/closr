<?php

namespace App\Catalogue;

use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Sources\DummyJsonSource;
use App\Catalogue\Sources\NullProductSource;
use App\Catalogue\Sources\WooCommerceSource;
use App\Models\CatalogueOrigin;
use App\Models\Widget;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Throwable;

/**
 * Resolves a ProductSource for a given merchant from their stored origin
 * config. New platforms (Shopify, a CSV feed, a future plugin) register a
 * driver via `extend` without touching this class.
 */
class CatalogueManager
{
    /**
     * The registered driver factories, keyed by driver name.
     *
     * @var array<string, Closure(array<string, mixed>): ProductSource>
     */
    protected array $drivers = [];

    public function __construct(protected Repository $config)
    {
        $this->registerDefaultDrivers();
    }

    /**
     * Resolve the catalogue source behind an origin, falling back to a no-op
     * source when the widget has not picked one.
     */
    public function forOrigin(?CatalogueOrigin $origin): ProductSource
    {
        if ($origin === null) {
            return new NullProductSource;
        }

        return $this->make($origin->driver, $origin->config ?? []);
    }

    /**
     * Resolve a widget's top product categories for its quick-pick chips.
     * Cached for an hour and resilient: an unreachable or empty store yields an
     * empty list (so the widget falls back to default starters) without caching
     * the failure.
     *
     * @return array<int, string>
     */
    public function categoriesFor(Widget $widget): array
    {
        $key = "catalogue:categories:widget:{$widget->id}";

        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $categories = $this->forOrigin($widget->catalogueOrigin)->categories()->all();
        } catch (Throwable) {
            $categories = [];
        }

        if ($categories !== []) {
            Cache::put($key, $categories, now()->addHour());
        }

        return $categories;
    }

    /**
     * Build a ProductSource from a driver name and its config.
     *
     * @param  array<string, mixed>  $config
     */
    public function make(string $driver, array $config): ProductSource
    {
        if (! isset($this->drivers[$driver])) {
            throw new InvalidArgumentException("Catalogue origin driver [{$driver}] is not supported.");
        }

        return ($this->drivers[$driver])($config);
    }

    /**
     * Register a custom origin driver.
     *
     * @param  Closure(array<string, mixed>): ProductSource  $factory
     */
    public function extend(string $driver, Closure $factory): self
    {
        $this->drivers[$driver] = $factory;

        return $this;
    }

    /**
     * Register the drivers shipped with Closr.
     */
    protected function registerDefaultDrivers(): void
    {
        $this->extend('woocommerce', fn (array $config): ProductSource => new WooCommerceSource(
            url: (string) ($config['url'] ?? ''),
            key: (string) ($config['key'] ?? ''),
            secret: (string) ($config['secret'] ?? ''),
            timeout: (int) ($config['timeout'] ?? $this->config->get('closr.catalogue.drivers.woocommerce.timeout', 15)),
        ));

        $this->extend('dummyjson', fn (array $config): ProductSource => new DummyJsonSource(
            url: (string) (($config['url'] ?? '') ?: 'https://dummyjson.com'),
            timeout: (int) ($config['timeout'] ?? $this->config->get('closr.catalogue.drivers.dummyjson.timeout', 15)),
        ));
    }
}
