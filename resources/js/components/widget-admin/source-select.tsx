import { Link } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { index as connectors } from '@/routes/connector';
import type { WidgetSource } from './types';

/** A native select backing the nullable `catalogue_origin_id` form field. */
export function SourceSelect({
    sources,
    name = 'catalogue_origin_id',
    defaultValue = null,
}: {
    sources: WidgetSource[];
    name?: string;
    defaultValue?: number | null;
}) {
    return (
        <div className="grid gap-2">
            <div className="flex items-center justify-between">
                <Label htmlFor="catalogue_origin_id">Source</Label>
                <Link
                    href={connectors()}
                    className="text-xs font-medium text-muted-foreground hover:text-foreground hover:underline"
                >
                    Manage sources
                </Link>
            </div>
            <select
                id="catalogue_origin_id"
                name={name}
                defaultValue={defaultValue ?? ''}
                data-test="source-select"
                className={cn(
                    'h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs',
                    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                )}
            >
                <option value="">No source, connect later</option>
                {sources.map((source) => (
                    <option key={source.id} value={source.id}>
                        {source.name}
                    </option>
                ))}
            </select>
        </div>
    );
}
