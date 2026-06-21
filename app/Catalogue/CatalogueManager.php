<?php

namespace App\Catalogue;

use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Sources\DummyJsonSource;
use App\Catalogue\Sources\NullProductSource;
use App\Catalogue\Sources\WooCommerceSource;
use App\Models\Team;
use Closure;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

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
     * Resolve the catalogue origin for a merchant, falling back to a no-op
     * source when the merchant has not connected a store.
     */
    public function forTeam(Team $team): ProductSource
    {
        $origin = $team->catalogueOrigin;

        if ($origin === null) {
            return new NullProductSource;
        }

        return $this->make($origin->driver, $origin->config ?? []);
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
