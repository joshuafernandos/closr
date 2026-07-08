import { LayoutTemplate, MessageCircle } from 'lucide-react';
import { useState } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { WidgetTemplate } from './types';

const OPTIONS: Array<{
    value: WidgetTemplate;
    label: string;
    description: string;
    icon: typeof MessageCircle;
}> = [
    {
        value: 'chat',
        label: 'Chat widget',
        description: 'A floating launcher that opens a chat card.',
        icon: MessageCircle,
    },
    {
        value: 'component',
        label: 'Inline component',
        description: 'A wide panel embedded directly in the page.',
        icon: LayoutTemplate,
    },
];

/** Two selectable cards backing a `template` form field. */
export function TemplatePicker({
    name = 'template',
    defaultValue = 'chat',
}: {
    name?: string;
    defaultValue?: WidgetTemplate;
}) {
    const [value, setValue] = useState<WidgetTemplate>(defaultValue);

    return (
        <div className="grid gap-2">
            <Label>Template</Label>
            <input type="hidden" name={name} value={value} />
            <div className="grid gap-3 sm:grid-cols-2">
                {OPTIONS.map((option) => {
                    const active = value === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => setValue(option.value)}
                            className={cn(
                                'flex flex-col gap-2 rounded-xl border p-4 text-left transition-colors',
                                active
                                    ? 'border-primary ring-2 ring-primary/30'
                                    : 'border-border hover:border-primary/40',
                            )}
                            data-test={`template-${option.value}`}
                            aria-pressed={active}
                        >
                            <option.icon className="size-5" />
                            <span className="text-sm font-semibold">
                                {option.label}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {option.description}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
