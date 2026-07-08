import { Link, usePage } from '@inertiajs/react';
import { motion  } from 'motion/react';
import type {Variants} from 'motion/react';
import { dashboard, login, register } from '@/routes';

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

export default function CtaSection() {
    const { auth } = usePage().props;

    return (
        <section className="border-t border-border bg-black text-white">
            <motion.div
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.4 }}
                transition={{ delayChildren: 0.1, staggerChildren: 0.14 }}
                className="mx-auto flex max-w-7xl flex-col items-center px-6 py-28 text-center sm:py-40"
            >
                <motion.span
                    variants={revealUp}
                    className="inline-block bg-secondary px-3 py-1.5 font-mono text-xs tracking-wide text-muted-foreground uppercase"
                >
                    Start closing today
                </motion.span>

                <motion.h2
                    variants={revealUp}
                    className="mt-8 max-w-4xl text-4xl font-medium tracking-tight text-balance sm:text-5xl"
                >
                    Turn browsers into buyers.
                </motion.h2>

                <motion.p
                    variants={revealUp}
                    className="mt-6 max-w-xl text-lg text-pretty text-white/60"
                >
                    Set up your AI shopping assistant in minutes. No credit card,
                    no setup call, no waiting.
                </motion.p>

                <motion.div
                    variants={revealUp}
                    className="mt-12 flex items-center"
                >
                    <MotionLink
                        href={auth.user ? dashboard().url : register().url}
                        whileHover={{ scale: 1.03 }}
                        whileTap={{ scale: 0.98 }}
                        transition={{ duration: 0.3, ease: smoothEase }}
                        className="inline-flex h-12 items-center justify-center bg-primary px-7 text-base font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        {auth.user ? 'Go to dashboard' : 'Get started'}
                    </MotionLink>
                    {!auth.user && (
                        <MotionLink
                            href={login().url}
                            whileHover={{ scale: 1.03 }}
                            whileTap={{ scale: 0.98 }}
                            transition={{ duration: 0.3, ease: smoothEase }}
                            className="inline-flex h-12 items-center justify-center bg-transparent px-7 text-base font-semibold text-white ring-1 ring-white/20 transition-colors hover:bg-white/10"
                        >
                            Log in
                        </MotionLink>
                    )}
                </motion.div>
            </motion.div>
        </section>
    );
}
