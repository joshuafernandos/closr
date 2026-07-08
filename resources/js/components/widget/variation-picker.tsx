import { Check, ShoppingBag } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { Product } from './types';

/**
 * A follow-up prompt that asks the shopper to choose a product's variations
 * (size, colour, …) via option chips before adding it to the cart. Once every
 * attribute has a selection the "Add to cart" button resolves the choice.
 */
export function VariationPicker({
    product,
    resolved,
    onConfirm,
}: {
    product: Product;
    resolved: boolean;
    onConfirm: (
        product: Product,
        selected: Record<string, string>,
    ) => void | Promise<void>;
}) {
    const variations = product.variations ?? [];
    const [selected, setSelected] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);

    const allChosen = variations.every((variation) =>
        Boolean(selected[variation.name]),
    );

    const handleConfirm = async () => {
        if (!allChosen || submitting || resolved) {
            return;
        }

        setSubmitting(true);

        try {
            await onConfirm(product, selected);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="flex w-[85%] flex-col gap-3 rounded-2xl rounded-tl-sm border border-black/10 bg-black/[0.03] p-4 dark:border-white/15 dark:bg-white/[0.05]">
            <p className="text-sm font-semibold text-black dark:text-white">
                {product.title}
            </p>

            {variations.map((variation) => (
                <div key={variation.name} className="flex flex-col gap-1.5">
                    <span className="text-xs font-medium text-black/50 dark:text-white/50">
                        {variation.name}
                    </span>
                    <div className="flex flex-wrap gap-1.5">
                        {variation.options.map((option) => {
                            const active = selected[variation.name] === option;

                            return (
                                <button
                                    key={option}
                                    type="button"
                                    disabled={resolved}
                                    onClick={() =>
                                        setSelected((prev) => ({
                                            ...prev,
                                            [variation.name]: option,
                                        }))
                                    }
                                    className={cn(
                                        'rounded-full border px-3 py-1.5 text-sm font-medium transition disabled:cursor-not-allowed',
                                        active
                                            ? 'border-black bg-black text-white dark:border-white dark:bg-white dark:text-black'
                                            : 'border-black/15 text-black hover:bg-black/5 disabled:opacity-60 dark:border-white/20 dark:text-white dark:hover:bg-white/10',
                                    )}
                                >
                                    {option}
                                </button>
                            );
                        })}
                    </div>
                </div>
            ))}

            <button
                type="button"
                onClick={() => void handleConfirm()}
                disabled={!allChosen || submitting || resolved}
                className={cn(
                    'mt-1 flex w-full items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold transition',
                    resolved
                        ? 'bg-black/5 text-black ring-1 ring-black/15 ring-inset dark:bg-white/10 dark:text-white dark:ring-white/20'
                        : 'bg-black text-white hover:bg-black/80 disabled:opacity-30 dark:bg-white dark:text-black dark:hover:bg-white/80',
                )}
            >
                {resolved ? (
                    <>
                        <Check className="size-4" />
                        Added
                    </>
                ) : (
                    <>
                        <ShoppingBag className="size-4" />
                        Add to cart
                    </>
                )}
            </button>
        </div>
    );
}
