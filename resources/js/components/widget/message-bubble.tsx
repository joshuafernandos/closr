import { ShoppingBag } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ProductSlider } from './product-slider';
import type { Message, Product } from './types';
import { VariationPicker } from './variation-picker';

/** Renders one transcript entry, dispatching on its kind. */
export function MessageBubble({
    message,
    bagIds,
    accentColor,
    onAddToCart,
    onViewDetails,
    onConfirmVariation,
}: {
    message: Message;
    bagIds: Set<number>;
    accentColor?: string;
    onAddToCart: (product: Product) => void;
    onViewDetails: (product: Product) => void;
    onConfirmVariation: (
        messageId: number,
        product: Product,
        selected: Record<string, string>,
    ) => void | Promise<void>;
}) {
    const isAssistant = message.role === 'assistant';
    const isConfirmation =
        message.kind === 'text' &&
        (message.step === 'checkout' || message.step === 'done');

    if (isConfirmation) {
        return (
            <div className="flex justify-start">
                <div className="w-[85%] rounded-2xl rounded-tl-sm border border-black/15 bg-black/5 p-4 dark:border-white/20 dark:bg-white/10">
                    <div className="mb-1.5 flex items-center gap-2 text-sm font-semibold">
                        <ShoppingBag className="size-4" />
                        Added to your bag
                    </div>
                    <p className="text-sm leading-relaxed">{message.text}</p>
                </div>
            </div>
        );
    }

    // Ask the shopper to choose this product's variations before adding it.
    if (message.kind === 'variation') {
        return (
            <div className="flex justify-start">
                <VariationPicker
                    product={message.product}
                    resolved={message.resolved}
                    onConfirm={(product, selected) =>
                        onConfirmVariation(message.id, product, selected)
                    }
                />
            </div>
        );
    }

    // Product recommendations break out of the bubble width so the slider has
    // room to breathe: the intro line sits in a bubble, the cards span below.
    if (message.kind === 'products') {
        return (
            <div className="flex w-full flex-col gap-3">
                <div className="flex justify-start">
                    <div className="max-w-[85%] rounded-2xl rounded-tl-sm bg-black/5 px-4 py-2.5 text-sm leading-relaxed text-black dark:bg-white/10 dark:text-white">
                        {message.text}
                    </div>
                </div>

                <ProductSlider
                    products={message.products}
                    bagIds={bagIds}
                    onAddToCart={onAddToCart}
                    onViewDetails={onViewDetails}
                />
            </div>
        );
    }

    return (
        <div
            className={cn(
                'flex w-full',
                isAssistant ? 'justify-start' : 'justify-end',
            )}
        >
            <div
                className={cn(
                    'rounded-2xl px-4 py-2.5 text-sm leading-relaxed',
                    isAssistant
                        ? 'max-w-[85%] rounded-tl-sm bg-black/5 text-black dark:bg-white/10 dark:text-white'
                        : accentColor
                          ? 'max-w-[85%] rounded-tr-sm text-white'
                          : 'max-w-[85%] rounded-tr-sm bg-black text-white dark:bg-white dark:text-black',
                )}
                style={
                    !isAssistant && accentColor
                        ? { backgroundColor: accentColor }
                        : undefined
                }
            >
                {message.text}
            </div>
        </div>
    );
}
