import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import DottedWaves from '@/components/dotted-waves';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-[1fr_1.05fr]">
            {/* Left: logo + form */}
            <div className="relative flex flex-col px-6 py-8 sm:px-10">
                <Link
                    href={home()}
                    className="flex items-center gap-2 font-semibold"
                >
                    <AppLogoIcon className="size-8 fill-current text-foreground" />
                    <span className="text-lg tracking-tight">Closr</span>
                </Link>

                <div className="flex flex-1 items-center justify-center py-10">
                    <div className="w-full max-w-sm">
                        <div className="mb-8 flex flex-col gap-2">
                            <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-sm text-balance text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </div>
                </div>
            </div>

            {/* Right: interactive dotted-wave panel */}
            <div className="relative hidden overflow-hidden rounded-l-[2rem] bg-black lg:block">
                <DottedWaves className="absolute inset-0 h-full w-full" />
                <div className="pointer-events-none absolute inset-0 bg-gradient-to-tr from-black/60 via-transparent to-transparent" />
                <div className="pointer-events-none absolute bottom-10 left-10 max-w-xs">
                    <p className="text-2xl font-medium text-white/90">
                        Close every conversation.
                    </p>
                    <p className="mt-2 text-sm text-white/50">
                        Your AI receptionist, always on.
                    </p>
                </div>
            </div>
        </div>
    );
}
