import {
    motion,
    useScroll,
    useSpring,
    useTransform,
    type Variants,
} from 'motion/react';
import { MousePointerClick, Plug, Sparkles, Wand2 } from 'lucide-react';
import { useRef, type ComponentType } from 'react';

const smoothEase = [0.22, 1, 0.36, 1] as const;

const revealUp: Variants = {
    hidden: { opacity: 0, y: 40 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.8, ease: smoothEase },
    },
};

type Step = {
    title: string;
    brief: string;
    icon: ComponentType<{ className?: string }>;
};

const steps: Step[] = [
    {
        title: 'Sign up',
        brief: 'Create your Closr account in seconds. No credit card, no setup call, no waiting.',
        icon: MousePointerClick,
    },
    {
        title: 'Connect your store',
        brief: 'Link WooCommerce, Shopify, and more. Your live catalogue syncs automatically.',
        icon: Plug,
    },
    {
        title: 'Create your widget',
        brief: 'Shape your assistant, then drop a single line of code onto your storefront.',
        icon: Wand2,
    },
    {
        title: 'Watch the magic',
        brief: 'Closr chats with shoppers, recommends products, and closes sales around the clock.',
        icon: Sparkles,
    },
];

function StepRow({ step, index }: { step: Step; index: number }) {
    const Icon = step.icon;
    const number = String(index + 1).padStart(2, '0');

    return (
        <motion.div
            variants={revealUp}
            className="relative pl-16 sm:pl-24"
        >
            {/* Node on the rail */}
            <span
                aria-hidden="true"
                className="absolute top-7 left-[1.4375rem] z-10 size-2.5 -translate-x-1/2 bg-white sm:left-[2.4375rem]"
            />

            <div className="group border border-white/10 bg-neutral-900 transition-colors hover:border-white/25">
                <div className="flex items-center gap-4 border-b border-white/10 px-5 py-4 sm:px-7">
                    <span className="flex size-10 shrink-0 items-center justify-center border border-white/15 font-mono text-sm tracking-wide text-white sm:size-11">
                        {number}
                    </span>
                    <span className="font-mono text-sm tracking-[0.2em] text-white uppercase sm:text-base">
                        {step.title}
                    </span>
                </div>

                <div className="flex items-start gap-4 px-5 py-5 sm:px-7 sm:py-6">
                    <span className="flex size-11 shrink-0 items-center justify-center border border-white/15 bg-secondary text-black transition-colors group-hover:bg-primary">
                        <Icon className="size-5" />
                    </span>
                    <p className="max-w-xl text-sm leading-relaxed text-pretty text-white/60 sm:text-base">
                        {step.brief}
                    </p>
                </div>
            </div>
        </motion.div>
    );
}

export default function HowItWorksSection() {
    const sectionRef = useRef<HTMLDivElement>(null);
    const timelineRef = useRef<HTMLDivElement>(null);

    // Drives the progress fill + the tracking square as the timeline scrolls past.
    const { scrollYProgress } = useScroll({
        target: timelineRef,
        offset: ['start 0.65', 'end 0.85'],
    });
    const progress = useSpring(scrollYProgress, {
        stiffness: 120,
        damping: 28,
        mass: 0.4,
    });

    const fillScaleY = useTransform(progress, [0, 1], [0, 1]);
    const squareTop = useTransform(progress, [0, 1], ['0%', '100%']);
    const squareRotate = useTransform(progress, [0, 1], [0, 135]);

    return (
        <section
            ref={sectionRef}
            className="border-t border-border bg-black text-white"
        >
            <div className="mx-auto max-w-7xl px-6 py-24 sm:py-32">
                <motion.div
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.4 }}
                    transition={{ delayChildren: 0.1, staggerChildren: 0.12 }}
                    className="max-w-3xl"
                >
                    <motion.span
                        variants={revealUp}
                        className="inline-block bg-secondary px-3 py-1.5 font-mono text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        From signup to selling in four steps
                    </motion.span>

                    <motion.h2
                        variants={revealUp}
                        className="mt-8 text-5xl font-medium tracking-tighter text-balance sm:text-6xl lg:text-7xl"
                    >
                        How it works.
                    </motion.h2>
                </motion.div>

                {/* Timeline */}
                <motion.div
                    ref={timelineRef}
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.15 }}
                    transition={{ delayChildren: 0.15, staggerChildren: 0.18 }}
                    className="relative mt-16 sm:mt-24"
                >
                    {/* Dotted rail */}
                    <div
                        aria-hidden="true"
                        className="absolute inset-y-0 left-[1.4375rem] w-px -translate-x-1/2 border-l border-dashed border-white/20 sm:left-[2.4375rem]"
                    />

                    {/* Solid progress fill that grows with scroll */}
                    <motion.div
                        aria-hidden="true"
                        style={{ scaleY: fillScaleY }}
                        className="absolute inset-y-0 left-[1.4375rem] w-px origin-top -translate-x-1/2 bg-gradient-to-b from-primary via-primary to-primary/40 sm:left-[2.4375rem]"
                    />

                    {/* Tracking square that follows the scroll position */}
                    <motion.div
                        aria-hidden="true"
                        style={{ top: squareTop }}
                        className="absolute left-[1.4375rem] z-20 -translate-x-1/2 -translate-y-1/2 sm:left-[2.4375rem]"
                    >
                        <motion.svg
                            width="18"
                            height="18"
                            viewBox="0 0 18 18"
                            style={{ rotate: squareRotate }}
                            className="drop-shadow-[0_0_12px_rgba(207,255,79,0.55)]"
                        >
                            <rect
                                x="2.5"
                                y="2.5"
                                width="13"
                                height="13"
                                className="fill-primary"
                            />
                        </motion.svg>
                    </motion.div>

                    <div className="flex flex-col gap-8 sm:gap-10">
                        {steps.map((step, index) => (
                            <StepRow
                                key={step.title}
                                step={step}
                                index={index}
                            />
                        ))}
                    </div>
                </motion.div>
            </div>
        </section>
    );
}
