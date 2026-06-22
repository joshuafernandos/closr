import type { ComponentType, ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';

export function ConnectedPill() {
    return (
        <span className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
            <span className="size-1.5 rounded-full bg-emerald-500" />
            Connected
        </span>
    );
}

export function ComingSoonPill() {
    return (
        <span className="rounded-full border px-2.5 py-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
            Coming soon
        </span>
    );
}

export function FooterBox({ children }: { children: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-2 rounded-lg bg-muted/50 p-3 text-sm">
            {children}
        </div>
    );
}

export function ConnectorCard({
    icon: Icon,
    iconClassName,
    name,
    description,
    status,
    footer,
    mostUsed = false,
    dimmed = false,
}: {
    icon: ComponentType<{ className?: string }>;
    iconClassName: string;
    name: string;
    description: string;
    status: ReactNode;
    footer: ReactNode;
    mostUsed?: boolean;
    dimmed?: boolean;
}) {
    return (
        <Card
            className={`relative overflow-visible ${dimmed ? 'opacity-70' : ''}`}
        >
            {mostUsed && (
                <span className="absolute -top-2.5 right-4 rounded bg-neutral-900 px-2 py-1 text-[10px] font-semibold tracking-wide text-white">
                    MOST USED
                </span>
            )}
            <CardContent className="space-y-4 p-5">
                <div className="flex items-start justify-between gap-2">
                    <div className="flex items-start gap-3">
                        <div
                            className={`flex size-10 shrink-0 items-center justify-center rounded-xl text-white ${iconClassName}`}
                        >
                            <Icon className="size-5" />
                        </div>
                        <div className="space-y-1">
                            <p className="leading-none font-semibold">{name}</p>
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {status}
                </div>

                {footer}
            </CardContent>
        </Card>
    );
}
