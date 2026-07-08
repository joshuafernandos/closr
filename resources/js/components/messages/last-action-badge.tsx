import { ExternalLink, LogOut, ShoppingCart } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type ActionStyle = {
    label: string;
    icon: LucideIcon;
    className: string;
};

// Visual treatment per last-action, mirroring the dashboard status styling.
const actionStyles: Record<string, ActionStyle> = {
    left: {
        label: 'Left',
        icon: LogOut,
        className:
            'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
    },
    added_to_cart: {
        label: 'Added to cart',
        icon: ShoppingCart,
        className:
            'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
    },
    visited_product_page: {
        label: 'Visited product page',
        icon: ExternalLink,
        className:
            'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400',
    },
};

/**
 * A small status pill showing the most recent thing that happened in a
 * conversation (left, added to cart, visited a product page).
 */
export function LastActionBadge({
    action,
    label,
    className,
    compact = false,
}: {
    action: string | null;
    label?: string | null;
    className?: string;
    compact?: boolean;
}) {
    if (!action) {
        return null;
    }

    const style = actionStyles[action];

    if (!style) {
        return null;
    }

    const Icon = style.icon;

    if (compact) {
        return (
            <span
                title={label ?? style.label}
                className={cn(
                    'inline-flex size-5 shrink-0 items-center justify-center rounded-full',
                    style.className,
                    className,
                )}
            >
                <Icon className="size-3" />
            </span>
        );
    }

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                style.className,
                className,
            )}
        >
            <Icon className="size-3" />
            {label ?? style.label}
        </span>
    );
}
