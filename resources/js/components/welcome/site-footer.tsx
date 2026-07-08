import AppLogoIcon from '@/components/app-logo-icon';

export default function SiteFooter() {
    return (
        <footer className="bg-black text-white">
            <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-6 py-10 sm:flex-row">
                <div className="flex items-center gap-2">
                    <AppLogoIcon className="size-6 text-white" />
                    <span className="text-lg font-semibold tracking-tight">
                        Closr
                    </span>
                </div>

                <p className="font-mono text-xs tracking-wide text-white/50 uppercase">
                    Developed by Joshua Suprianto
                </p>
            </div>
        </footer>
    );
}
