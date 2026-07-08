import { Link } from '@inertiajs/react';
import { Check, Clock, Plug, Sparkles, Wand2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { chat } from '@/routes';
import { index as connectorIndex } from '@/routes/connector';
import { index as widgetIndex } from '@/routes/widgets';

export type OnboardingState = {
    storeConnected: boolean;
    widgetCustomized: boolean;
    widgetPreviewed: boolean;
};

type Step = {
    title: string;
    description: string;
    icon: LucideIcon;
    completed: boolean;
    href?: string;
    cta?: string;
    comingSoon?: boolean;
};

export function OnboardingSteps({
    onboarding,
}: {
    onboarding: OnboardingState;
}) {
    const steps: Step[] = [
        {
            title: 'Connect your store',
            description:
                'Link your WooCommerce catalogue so the widget can recommend your products.',
            icon: Plug,
            completed: onboarding.storeConnected,
            href: connectorIndex().url,
            cta: 'Connect store',
        },
        {
            title: 'Create your widget',
            description:
                'Build a widget, pick a template, and match it to your brand colour.',
            icon: Wand2,
            completed: onboarding.widgetCustomized,
            href: widgetIndex().url,
            cta: 'Create widget',
        },
        {
            title: 'See how it works',
            description:
                'Open the live demo and chat with your widget the way a shopper would.',
            icon: Sparkles,
            completed: onboarding.widgetPreviewed,
            href: chat().url,
            cta: 'Try the demo',
        },
    ];

    const completedCount = steps.filter((step) => step.completed).length;
    const allComplete = completedCount === steps.length;

    if (allComplete) {
        return null;
    }

    return (
        <Card className="gap-5 p-5">
            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Finish setting up Closr
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Complete these steps to start converting shoppers.
                    </p>
                </div>
                <span className="text-sm font-medium text-muted-foreground">
                    {completedCount} of {steps.length} done
                </span>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
                {steps.map((step, index) => (
                    <div
                        key={step.title}
                        className={cn(
                            'flex flex-col gap-3 rounded-lg border p-4 transition-colors',
                            step.completed
                                ? 'border-primary/30 bg-primary/10 dark:border-primary/20 dark:bg-primary/5'
                                : 'border-border bg-muted/30',
                        )}
                    >
                        <div className="flex items-center justify-between">
                            <span
                                className={cn(
                                    'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                    step.completed
                                        ? 'bg-primary/15 text-foreground dark:bg-primary/20'
                                        : 'bg-background text-muted-foreground',
                                )}
                            >
                                <step.icon className="size-4.5" />
                            </span>
                            <span
                                className={cn(
                                    'flex size-6 items-center justify-center rounded-full text-xs font-semibold',
                                    step.completed
                                        ? 'bg-primary text-primary-foreground'
                                        : step.comingSoon
                                          ? 'bg-muted text-muted-foreground'
                                          : 'border border-border bg-background text-muted-foreground',
                                )}
                            >
                                {step.completed ? (
                                    <Check className="size-3.5" />
                                ) : step.comingSoon ? (
                                    <Clock className="size-3.5" />
                                ) : (
                                    index + 1
                                )}
                            </span>
                        </div>

                        <div className="space-y-1">
                            <p className="text-sm font-semibold">
                                {step.title}
                            </p>
                            <p className="text-xs leading-relaxed text-muted-foreground">
                                {step.description}
                            </p>
                        </div>

                        <div className="mt-auto pt-1">
                            {step.completed ? (
                                <span className="text-xs font-semibold text-foreground">
                                    Completed
                                </span>
                            ) : step.href ? (
                                <Link
                                    href={step.href}
                                    className="text-xs font-semibold text-foreground hover:underline"
                                >
                                    {step.cta}
                                </Link>
                            ) : (
                                <span className="text-xs font-medium text-muted-foreground">
                                    {step.cta}
                                </span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </Card>
    );
}
