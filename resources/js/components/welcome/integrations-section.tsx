import { motion, type Variants } from 'motion/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useRef } from 'react';
import { BrandLogo } from '@/components/welcome/brand-logos';

const smoothEase = [0.22, 1, 0.36, 1] as const;

const revealUp: Variants = {
    hidden: { opacity: 0, y: 40 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.8, ease: smoothEase },
    },
};

type Platform = {
    name: string;
    category: string;
    logo: string;
    color: string;
    brief: string;
    available?: boolean;
};

const platforms: Platform[] = [
    {
        name: 'WooCommerce',
        category: 'WordPress',
        logo: 'woocommerce',
        color: '#7f54b3',
        brief: 'Connect via REST API keys, no plugin required.',
        available: true,
    },
    {
        name: 'Shopify',
        category: 'Hosted',
        logo: 'shopify',
        color: '#95bf47',
        brief: 'One-click OAuth app install from your store.',
    },
    {
        name: 'Magento',
        category: 'Adobe Commerce',
        logo: 'magento',
        color: '#ee672f',
        brief: 'Sync your catalogue through the Magento REST API.',
    },
    {
        name: 'BigCommerce',
        category: 'Hosted',
        logo: 'bigcommerce',
        color: '#121118',
        brief: 'Connect with store API credentials in minutes.',
    },
    {
        name: 'Squarespace',
        category: 'Commerce',
        logo: 'squarespace',
        color: '#000000',
        brief: 'Import products via the Commerce API.',
    },
    {
        name: 'Wix',
        category: 'Commerce',
        logo: 'wix',
        color: '#0c6efc',
        brief: 'Link your store through the Wix Stores API.',
    },
    {
        name: 'PrestaShop',
        category: 'Self-hosted',
        logo: 'prestashop',
        color: '#df0067',
        brief: 'Connect using the PrestaShop Webservice API.',
    },
    {
        name: 'Salesforce',
        category: 'Commerce Cloud',
        logo: 'salesforce',
        color: '#00a1e0',
        brief: 'Integrate via Commerce Cloud APIs.',
    },
];

function PlatformCard({ platform }: { platform: Platform }) {
    return (
        <div className="flex w-64 shrink-0 snap-start flex-col gap-5 border border-border bg-background p-6 transition-colors hover:border-foreground/30">
            <div className="flex items-center justify-between">
                <span
                    className="flex size-12 items-center justify-center text-white"
                    style={{ backgroundColor: platform.color }}
                >
                    <BrandLogo logo={platform.logo} className="size-6" />
                </span>
                {platform.available ? (
                    <span className="inline-flex items-center gap-1.5 bg-primary px-2.5 py-1 text-xs font-semibold text-black">
                        <span className="size-1.5 rounded-full bg-black" />
                        Available now
                    </span>
                ) : (
                    <span className="inline-flex items-center bg-secondary px-2.5 py-1 text-xs font-medium text-muted-foreground">
                        Coming soon
                    </span>
                )}
            </div>

            <div className="space-y-1">
                <p className="text-lg font-semibold tracking-tight">
                    {platform.name}
                </p>
                <p className="font-mono text-xs tracking-wide text-muted-foreground uppercase">
                    {platform.category}
                </p>
            </div>

            <p className="text-sm leading-relaxed text-black/60">
                {platform.brief}
            </p>
        </div>
    );
}

export default function IntegrationsSection() {
    const scrollRef = useRef<HTMLDivElement>(null);

    const scrollBy = (direction: 1 | -1): void => {
        scrollRef.current?.scrollBy({
            left: direction * 288,
            behavior: 'smooth',
        });
    };

    return (
        <section className="border-t border-border bg-white text-black">
            <motion.div
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.2 }}
                transition={{ delayChildren: 0.15, staggerChildren: 0.15 }}
                className="mx-auto max-w-7xl px-6 py-24 sm:py-32"
            >
                <div className="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
                    <div className="max-w-2xl">
                        <motion.span
                            variants={revealUp}
                            className="inline-block bg-secondary px-3 py-1.5 font-mono text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            Plugs into your stack
                        </motion.span>

                        <motion.h2
                            variants={revealUp}
                            className="mt-8 max-w-2xl text-4xl font-medium tracking-tight text-balance sm:text-5xl"
                            style={{ lineHeight: 1.18 }}
                        >
                            Integrate with your favourite platforms
                        </motion.h2>

                        <motion.p
                            variants={revealUp}
                            className="mt-5 max-w-md text-base text-pretty text-black/70"
                        >
                            Connect your store in minutes and Closr starts
                            selling from your real catalogue. More integrations
                            are on the way.
                        </motion.p>
                    </div>

                    <motion.div
                        variants={revealUp}
                        className="flex items-center gap-2"
                    >
                        <button
                            type="button"
                            aria-label="Previous integrations"
                            onClick={() => scrollBy(-1)}
                            className="inline-flex size-11 items-center justify-center border border-border transition-colors hover:bg-secondary"
                        >
                            <ChevronLeft className="size-5" />
                        </button>
                        <button
                            type="button"
                            aria-label="More integrations"
                            onClick={() => scrollBy(1)}
                            className="inline-flex size-11 items-center justify-center border border-border transition-colors hover:bg-secondary"
                        >
                            <ChevronRight className="size-5" />
                        </button>
                    </motion.div>
                </div>

                <motion.div
                    variants={revealUp}
                    ref={scrollRef}
                    className="mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-6 pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {platforms.map((platform) => (
                        <PlatformCard key={platform.name} platform={platform} />
                    ))}
                </motion.div>
            </motion.div>
        </section>
    );
}
