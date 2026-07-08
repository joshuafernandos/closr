import {
    ArrowRight,
    FileSpreadsheet,
    Search,
    ShoppingBag,
    ShoppingCart,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

function FilterChip({
    label,
    count,
    active = false,
}: {
    label: string;
    count: number;
    active?: boolean;
}) {
    return (
        <button
            type="button"
            className={`inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors ${
                active
                    ? 'border-neutral-900 bg-neutral-900 text-white'
                    : 'border-border bg-background text-foreground hover:bg-muted'
            }`}
        >
            {label}
            <span
                className={active ? 'text-white/60' : 'text-muted-foreground'}
            >
                {count}
            </span>
        </button>
    );
}

function Stat({
    label,
    value,
    suffix,
    hint,
    hintClassName,
}: {
    label: string;
    value: string;
    suffix?: string;
    hint: ReactNode;
    hintClassName?: string;
}) {
    return (
        <div className="space-y-2 p-5">
            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="text-2xl font-bold">
                {value}
                {suffix && (
                    <span className="ml-1 text-sm font-normal text-muted-foreground">
                        {suffix}
                    </span>
                )}
            </p>
            <p
                className={`text-xs text-muted-foreground ${hintClassName ?? ''}`}
            >
                {hint}
            </p>
        </div>
    );
}

export function ConnectorsShowcase() {
    return (
        <>
            {/* Hero banner */}
            <div className="flex flex-col gap-6 rounded-2xl bg-neutral-900 p-6 text-white lg:flex-row lg:items-center lg:justify-between">
                <div className="max-w-2xl space-y-2">
                    <h2 className="text-xl font-bold">
                        Connect your catalogue. Let Closr do the selling.
                    </h2>
                    <p className="text-sm text-neutral-300">
                        Sync products from WooCommerce, Shopify or a CSV into
                        one place. Closr searches your catalogue and recommends
                        the right products in every conversation,
                        automatically.
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <div className="flex -space-x-2">
                        {[
                            { Icon: ShoppingCart, cls: 'bg-[#7f54b3]' },
                            { Icon: ShoppingBag, cls: 'bg-[#95bf47]' },
                            { Icon: FileSpreadsheet, cls: 'bg-neutral-600' },
                        ].map(({ Icon, cls }, index) => (
                            <span
                                key={index}
                                className={`flex size-8 items-center justify-center rounded-full ring-2 ring-neutral-900 ${cls}`}
                            >
                                <Icon className="size-4 text-white" />
                            </span>
                        ))}
                    </div>
                    <ArrowRight className="size-4 text-neutral-400" />
                    <Button
                        variant="secondary"
                        className="rounded-full bg-white text-neutral-900 hover:bg-neutral-100"
                    >
                        <Search />
                        Searchable catalogue
                    </Button>
                </div>
            </div>
        </>
    );
}
