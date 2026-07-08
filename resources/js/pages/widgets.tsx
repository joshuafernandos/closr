import { Head } from '@inertiajs/react';
import { LayoutTemplate } from 'lucide-react';
import { CreateWidgetDialog } from '@/components/widget-admin/create-widget-dialog';
import type {
    WidgetSource,
    WidgetSummary,
} from '@/components/widget-admin/types';
import { WidgetCard } from '@/components/widget-admin/widget-card';
import { index as widgets } from '@/routes/widgets';

interface WidgetsProps {
    widgets: WidgetSummary[];
    sources: WidgetSource[];
}

export default function Widgets({ widgets: list, sources }: WidgetsProps) {
    return (
        <>
            <Head title="Widgets" />

            <div className="flex h-full flex-1 flex-col gap-6 bg-muted/30 p-4 md:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Widgets
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Create embeddable assistants, each connected to one
                            catalogue source.
                        </p>
                    </div>
                    <CreateWidgetDialog sources={sources} />
                </div>

                {list.length === 0 ? (
                    <EmptyState sources={sources} />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {list.map((widget) => (
                            <WidgetCard key={widget.id} widget={widget} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

function EmptyState({ sources }: { sources: WidgetSource[] }) {
    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-16 text-center">
            <span className="flex size-12 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                <LayoutTemplate className="size-6" />
            </span>
            <div className="space-y-1">
                <p className="font-semibold">No widgets yet</p>
                <p className="text-sm text-muted-foreground">
                    Create your first widget to embed Closr on your storefront.
                </p>
            </div>
            <CreateWidgetDialog sources={sources} />
        </div>
    );
}

Widgets.layout = () => ({
    breadcrumbs: [
        {
            title: 'Widgets',
            href: widgets(),
        },
    ],
});
