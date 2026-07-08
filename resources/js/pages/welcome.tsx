import { Head, Link, usePage } from '@inertiajs/react';
import { ReactLenis } from 'lenis/react';
import { motion  } from 'motion/react';
import type {Variants} from 'motion/react';
import 'lenis/dist/lenis.css';
import AppLogoIcon from '@/components/app-logo-icon';
import CtaSection from '@/components/welcome/cta-section';
import DemoSection from '@/components/welcome/demo-section';
import HowItWorksSection from '@/components/welcome/how-it-works-section';
import IntegrationsSection from '@/components/welcome/integrations-section';
import IntegritySection from '@/components/welcome/integrity-section';
import PricingSection from '@/components/welcome/pricing-section';
import SiteFooter from '@/components/welcome/site-footer';
import { dashboard, login, register } from '@/routes';

const MotionLink = motion.create(Link);

const headingWords = 'Your AI Shopping Assistant'.split(' ');

const smoothEase = [0.22, 1, 0.36, 1] as const;

const wordVariants: Variants = {
    hidden: { opacity: 0, y: '0.25em' },
    visible: {
        opacity: 1,
        y: '0em',
        transition: { duration: 0.9, ease: smoothEase },
    },
};

const fadeUp: Variants = {
    hidden: { opacity: 0, y: 16 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.8, ease: smoothEase },
    },
};

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <ReactLenis root options={{ lerp: 0.18 }} />
            <Head title="Closr: Close more, effortlessly" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                {/* Header */}
                <motion.header
                    initial={{ y: -40, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{
                        duration: 0.8,
                        ease: smoothEase,
                        delay: 0.15,
                    }}
                    className="absolute inset-x-0 top-0 z-50 px-4 pt-4 sm:px-6 sm:pt-6"
                >
                    <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-5 sm:px-6">
                        <Link
                            href="/"
                            className="group flex items-center gap-2 text-black"
                            aria-label="Closr home"
                        >
                            <motion.span
                                whileHover={{ scale: 1.06 }}
                                transition={{ duration: 0.3, ease: smoothEase }}
                            >
                                <AppLogoIcon className="size-8 text-black" />
                            </motion.span>
                            <span className="text-2xl font-semibold tracking-tight">
                                Closr
                            </span>
                        </Link>

                        {auth.user ? (
                            <MotionLink
                                href={dashboard().url}
                                whileHover={{ scale: 1.03 }}
                                whileTap={{ scale: 0.98 }}
                                transition={{ duration: 0.3, ease: smoothEase }}
                                className="inline-flex h-10 items-center justify-center bg-primary px-6 text-sm font-semibold text-primary-foreground"
                            >
                                Dashboard
                            </MotionLink>
                        ) : (
                            <div className="flex items-center gap-2">
                                <Link
                                    href={login().url}
                                    className="hidden h-10 items-center justify-center px-4 text-sm font-medium text-black/70 transition hover:text-black sm:inline-flex"
                                >
                                    Log in
                                </Link>
                                <MotionLink
                                    href={register().url}
                                    whileHover={{ scale: 1.03 }}
                                    whileTap={{ scale: 0.98 }}
                                    transition={{
                                        duration: 0.3,
                                        ease: smoothEase,
                                    }}
                                    className="inline-flex h-10 items-center justify-center bg-primary px-6 text-sm font-semibold text-primary-foreground"
                                >
                                    Get started
                                </MotionLink>
                            </div>
                        )}
                    </div>
                </motion.header>

                {/* Hero */}
                <main className="relative flex min-h-screen flex-col items-center justify-end overflow-hidden bg-white px-6 pt-40 pb-24 text-center sm:pt-48">
                    <motion.h1
                        aria-label="Your AI Shopping Assistant"
                        initial="hidden"
                        animate="visible"
                        transition={{
                            delayChildren: 0.35,
                            staggerChildren: 0.18,
                        }}
                        className="relative z-10 mb-20 flex max-w-5xl flex-wrap justify-center gap-x-[0.25em] text-5xl tracking-tighter text-balance text-black sm:text-7xl lg:text-8xl"
                    >
                        {headingWords.map((word, index) => (
                            <motion.span
                                key={index}
                                aria-hidden="true"
                                variants={wordVariants}
                                className="inline-block origin-bottom"
                            >
                                {word}
                            </motion.span>
                        ))}
                    </motion.h1>

                    <motion.div
                        initial="hidden"
                        animate="visible"
                        transition={{
                            delayChildren: 1.1,
                            staggerChildren: 0.18,
                        }}
                        className="relative z-10 flex flex-col items-center"
                    >
                        <motion.p
                            variants={fadeUp}
                            className="max-w-md text-xl font-medium text-pretty text-black/80 sm:text-2xl"
                        >
                            Closr answers questions, recommends products, and
                            closes sales for your store, around the clock.
                        </motion.p>


                        <motion.div
                            variants={fadeUp}
                            className="mt-10 flex items-center"
                        >
                            <MotionLink
                                href={
                                    auth.user
                                        ? dashboard().url
                                        : register().url
                                }
                                whileHover={{ scale: 1.03 }}
                                whileTap={{ scale: 0.98 }}
                                transition={{ duration: 0.3, ease: smoothEase }}
                                className="inline-flex h-12 items-center justify-center bg-black px-7 text-base font-semibold text-white transition-colors hover:bg-black/85"
                            >
                                Get started
                            </MotionLink>
                            {!auth.user && (
                                <MotionLink
                                    href={login().url}
                                    whileHover={{ scale: 1.03 }}
                                    whileTap={{ scale: 0.98 }}
                                    transition={{
                                        duration: 0.3,
                                        ease: smoothEase,
                                    }}
                                    className="inline-flex h-12 items-center justify-center bg-white px-7 text-base font-semibold text-black ring-1 ring-black/10 transition-colors hover:bg-white/85"
                                >
                                    Log in
                                </MotionLink>
                            )}
                        </motion.div>
                    </motion.div>
                </main>

                <IntegritySection />

                <DemoSection />

                <HowItWorksSection />

                <IntegrationsSection />

                <PricingSection />

                <CtaSection />

                <SiteFooter />
            </div>
        </>
    );
}
