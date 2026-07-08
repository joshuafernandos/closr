import { Link, usePage } from '@inertiajs/react';
import { Check, KeyRound } from 'lucide-react';
import { motion } from 'motion/react';
import type { Variants } from 'motion/react';
import { dashboard, register } from '@/routes';

const MotionLink = motion.create(Link);

const smoothEase = [0.22, 1, 0.36, 1] as const;

const revealUp: Variants = {
    hidden: { opacity: 0, y: 40 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.8, ease: smoothEase },
    },
};

const perks = [
    'Unlimited AI conversations',
    'Bring your own API key (Claude or GPT)',
    'Connect WooCommerce, Shopify, and more',
    'Live catalogue sync',
    'Your own embeddable widget',
    'No credit card required',
];

export default function PricingSection() {
    const { auth } = usePage().props;

    return (
        <section className="border-t border-border bg-white text-black">
            <motion.div
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.3 }}
                transition={{ delayChildren: 0.1, staggerChildren: 0.14 }}
                className="mx-auto max-w-7xl px-6 py-24 sm:py-32"
            >
                <div className="max-w-3xl">
                    <motion.span
                        variants={revealUp}
                        className="inline-block bg-secondary px-3 py-1.5 font-mono text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        Simple, honest pricing
                    </motion.span>

                    <motion.h2
                        variants={revealUp}
                        className="mt-8 text-4xl font-medium tracking-tight text-balance sm:text-5xl"
                    >
                        Free while we build.
                    </motion.h2>
                </div>

                <motion.div
                    variants={revealUp}
                    className="mt-14 grid gap-px overflow-hidden border border-black/10 bg-black/10 sm:mt-20 lg:grid-cols-[1.1fr_1fr]"
                >
                    {/* Price */}
                    <div className="flex flex-col justify-between gap-10 bg-white p-8 sm:p-12">
                        <div>
                            <span className="font-mono text-sm tracking-[0.2em] text-black/50 uppercase">
                                Early access
                            </span>
                            <div className="mt-6 flex items-end gap-2">
                                <span className="text-7xl font-medium tracking-tighter sm:text-8xl">
                                    $0
                                </span>
                                <span className="mb-2 text-lg text-black/50">
                                    / forever, for now
                                </span>
                            </div>
                            <p className="mt-6 max-w-md text-base leading-relaxed text-pretty text-black/60">
                                Closr is completely free to use until{' '}
                                <span className="font-semibold text-black">
                                    January 2027
                                </span>
                                . Set up your assistant today and start closing
                                sales, no strings attached.
                            </p>

                            <div className="mt-6 flex items-start gap-3 border border-black/10 bg-neutral-50 p-4">
                                <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center bg-primary text-black">
                                    <KeyRound className="size-3.5" />
                                </span>
                                <p className="text-sm leading-relaxed text-pretty text-black/60">
                                    Connect your own API key. Choose{' '}
                                    <span className="font-semibold text-black">
                                        Claude
                                    </span>{' '}
                                    or{' '}
                                    <span className="font-semibold text-black">
                                        GPT
                                    </span>
                                    , stay in control of your model, and only pay
                                    your provider for usage.
                                </p>
                            </div>
                        </div>

                        <MotionLink
                            href={auth.user ? dashboard().url : register().url}
                            whileHover={{ scale: 1.02 }}
                            whileTap={{ scale: 0.98 }}
                            transition={{ duration: 0.3, ease: smoothEase }}
                            className="inline-flex h-12 w-full items-center justify-center bg-black px-7 text-base font-semibold text-white transition-colors hover:bg-black/85 sm:w-auto"
                        >
                            {auth.user ? 'Go to dashboard' : 'Get started free'}
                        </MotionLink>
                    </div>

                    {/* Perks */}
                    <div className="bg-neutral-50 p-8 sm:p-12">
                        <span className="font-mono text-sm tracking-[0.2em] text-black/50 uppercase">
                            Everything included
                        </span>
                        <ul className="mt-8 flex flex-col gap-4">
                            {perks.map((perk) => (
                                <li
                                    key={perk}
                                    className="flex items-start gap-3 text-sm text-pretty text-black/80"
                                >
                                    <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center bg-primary text-black">
                                        <Check className="size-3.5" />
                                    </span>
                                    {perk}
                                </li>
                            ))}
                        </ul>
                    </div>
                </motion.div>
            </motion.div>
        </section>
    );
}
