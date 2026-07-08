import { Check, ExternalLink, ShoppingBag } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Product } from './types';

/**
 * A single recommended product. Shows two actions: "Add to cart" (the primary
 * action) and "View details" (opens the product's storefront page). Products
 * with variations open a follow-up picker instead of adding straight away; the
 * card itself never shows an "added" state for them.
 */
export function ProductCard({
    product,
    added,
    onAddToCart,
    onViewDetails,
}: {
    product: Product;
    added: boolean;
    onAddToCart: (product: Product) => void;
    onViewDetails: (product: Product) => void;
}) {
    const hasVariations = (product.variations?.length ?? 0) > 0;
    // A real storefront (WooCommerce) hands us a cart URL; the demo catalogue
    // has none, so the simple add just toggles the local bag.
    const goesToCart = Boolean(product.cartUrl);

    return (
        <div
            className={cn(
                'group flex w-44 shrink-0 snap-start flex-col overflow-hidden rounded-2xl border bg-white transition hover:shadow-md sm:w-48 dark:bg-[#0a0a0a]',
                added
                    ? 'border-black/40 dark:border-white/45'
                    : 'border-black/10 hover:border-black/25 dark:border-white/15 dark:hover:border-white/30',
            )}
        >
            <div className="relative aspect-square overflow-hidden bg-black/[0.03] dark:bg-white/[0.04]">
                <img
                    src={product.thumbnail}
                    alt={product.title}
                    loading="lazy"
                    className="size-full object-contain p-3 transition duration-300 group-hover:scale-105"
                />
                <span className="absolute top-2 right-2 rounded-full bg-black/85 px-2 py-0.5 text-xs font-bold text-white backdrop-blur dark:bg-white/90 dark:text-black">
                    ${product.price.toFixed(2)}
                </span>
            </div>

            <div className="flex flex-1 flex-col gap-1.5 p-3">
                {product.category && (
                    <span className="text-[0.625rem] font-semibold tracking-wider text-black/40 uppercase dark:text-white/40">
                        {product.category}
                    </span>
                )}
                <p className="line-clamp-2 text-sm font-semibold text-black dark:text-white">
                    {product.title}
                </p>
                {product.reason && (
                    <p className="line-clamp-2 text-xs leading-relaxed text-black/55 italic dark:text-white/55">
                        “{product.reason}”
                    </p>
                )}

                <div className="mt-auto flex flex-col gap-1.5 pt-1.5">
                    <button
                        type="button"
                        onClick={() => onAddToCart(product)}
                        aria-pressed={added}
                        className={cn(
                            'flex w-full items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold transition',
                            added
                                ? 'bg-black/5 text-black ring-1 ring-black/15 ring-inset hover:bg-black/10 dark:bg-white/10 dark:text-white dark:ring-white/20 dark:hover:bg-white/15'
                                : 'bg-black text-white hover:bg-black/80 dark:bg-white dark:text-black dark:hover:bg-white/80',
                        )}
                    >
                        {added ? (
                            <>
                                <Check className="size-4" />
                                Added
                            </>
                        ) : (
                            <>
                                <ShoppingBag className="size-4" />
                                {hasVariations
                                    ? 'Choose options'
                                    : goesToCart
                                      ? 'Add to cart'
                                      : 'Add to bag'}
                            </>
                        )}
                    </button>

                    {product.url && (
                        <a
                            href={product.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={() => onViewDetails(product)}
                            className="flex w-full items-center justify-center gap-1.5 rounded-xl border border-black/10 px-3 py-2 text-sm font-medium text-black transition hover:bg-black/5 dark:border-white/15 dark:text-white dark:hover:bg-white/10"
                        >
                            <ExternalLink className="size-4" />
                            View details
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
