import { ChevronLeft, ChevronRight, Sparkles } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ProductCard } from './product-card';
import type { Product } from './types';

/** A horizontally scrolling row of recommended product cards. */
export function ProductSlider({
    products,
    bagIds,
    onAddToCart,
    onViewDetails,
}: {
    products: Product[];
    bagIds: Set<number>;
    onAddToCart: (product: Product) => void;
    onViewDetails: (product: Product) => void;
}) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const updateScrollState = () => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        const { scrollLeft, scrollWidth, clientWidth } = track;
        setCanScrollLeft(scrollLeft > 1);
        setCanScrollRight(scrollLeft + clientWidth < scrollWidth - 1);
    };

    useEffect(() => {
        updateScrollState();
    }, [products]);

    const scrollByCards = (direction: 1 | -1) => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        track.scrollBy({
            left: direction * track.clientWidth * 0.8,
            behavior: 'smooth',
        });
    };

    const showControls = products.length > 1;

    return (
        <div className="flex flex-col gap-2">
            {showControls && (
                <div className="flex items-center justify-between px-1">
                    <span className="flex items-center gap-1.5 text-xs font-medium text-black/50 dark:text-white/50">
                        <Sparkles className="size-3.5" />
                        {products.length} picks for you
                    </span>
                    <div className="flex gap-1.5">
                        <SliderArrow
                            direction="left"
                            disabled={!canScrollLeft}
                            onClick={() => scrollByCards(-1)}
                        />
                        <SliderArrow
                            direction="right"
                            disabled={!canScrollRight}
                            onClick={() => scrollByCards(1)}
                        />
                    </div>
                </div>
            )}

            <div
                ref={trackRef}
                onScroll={updateScrollState}
                className="-mx-4 flex snap-x snap-mandatory scroll-px-4 [scrollbar-width:none] gap-3 overflow-x-auto px-4 pb-1 [&::-webkit-scrollbar]:hidden"
            >
                {products.map((product) => (
                    <ProductCard
                        key={product.id}
                        product={product}
                        added={bagIds.has(product.id)}
                        onAddToCart={onAddToCart}
                        onViewDetails={onViewDetails}
                    />
                ))}
            </div>
        </div>
    );
}

function SliderArrow({
    direction,
    disabled,
    onClick,
}: {
    direction: 'left' | 'right';
    disabled: boolean;
    onClick: () => void;
}) {
    const Icon = direction === 'left' ? ChevronLeft : ChevronRight;

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={
                direction === 'left' ? 'Previous products' : 'Next products'
            }
            className="flex size-7 items-center justify-center rounded-full border border-black/10 bg-white text-black transition hover:bg-black/5 disabled:cursor-not-allowed disabled:opacity-30 dark:border-white/15 dark:bg-[#0a0a0a] dark:text-white dark:hover:bg-white/10"
        >
            <Icon className="size-4" />
        </button>
    );
}
