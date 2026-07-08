import { MessageSquare, Plug, Wand2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card } from '@/components/ui/card';

export type DashboardStats = {
    conversations: number;
    widgets: number;
    stores: number;
};

type Stat = {
    label: string;
    value: number;
    description: string;
    icon: LucideIcon;
};

export function DashboardStats({ stats }: { stats: DashboardStats }) {
    const items: Stat[] = [
        {
            label: 'Conversations',
            value: stats.conversations,
            description: 'Shopper chats captured by your widget.',
            icon: MessageSquare,
        },
        {
            label: 'Widgets',
            value: stats.widgets,
            description: 'Embeddable widgets you have created.',
            icon: Wand2,
        },
        {
            label: 'Connected stores',
            value: stats.stores,
            description: 'Catalogues synced to power recommendations.',
            icon: Plug,
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {items.map((item) => (
                <Card key={item.label} className="gap-4 p-5">
                    <div className="flex items-center justify-between">
                        <p className="text-sm font-medium text-muted-foreground">
                            {item.label}
                        </p>
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/15 text-foreground dark:bg-primary/20">
                            <item.icon className="size-4.5" />
                        </span>
                    </div>
                    <span className="text-3xl font-bold tracking-tight">
                        {item.value}
                    </span>
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        {item.description}
                    </p>
                </Card>
            ))}
        </div>
    );
}
