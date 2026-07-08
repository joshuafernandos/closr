import { Link } from '@inertiajs/react';
import { LayoutTemplate, MessageCircle, Plug } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { edit } from '@/routes/widgets';
import type { WidgetSummary } from './types';

export function WidgetCard({ widget }: { widget: WidgetSummary }) {
    const TemplateIcon =
        widget.template === 'component' ? LayoutTemplate : MessageCircle;

    return (
        <Link href={edit(widget.key)} className="block">
            <Card className="transition-colors hover:border-primary/40">
                <CardContent className="space-y-4 p-5">
                    <div className="flex items-start justify-between gap-2">
                        <div className="flex items-start gap-3">
                            <span
                                className="flex size-10 shrink-0 items-center justify-center rounded-xl text-white"
                                style={{ background: widget.accentColor }}
                            >
                                <TemplateIcon className="size-5" />
                            </span>
                            <div className="space-y-1">
                                <p className="leading-none font-semibold">
                                    {widget.name}
                                </p>
                                <p className="text-xs text-muted-foreground capitalize">
                                    {widget.template} template
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 rounded-lg bg-muted/50 p-3 text-sm">
                        <Plug className="size-4 shrink-0 text-muted-foreground" />
                        <span className="min-w-0 truncate">
                            {widget.sourceName ?? 'No source connected'}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}
