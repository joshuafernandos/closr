import { motion  } from 'motion/react';
import type {Variants} from 'motion/react';

const smoothEase = [0.22, 1, 0.36, 1] as const;

const revealUp: Variants = {
    hidden: { opacity: 0, y: 40 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.8, ease: smoothEase },
    },
};

export default function DemoSection() {
    return (
        <section className="border-t border-border bg-black text-white">
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
                        See it in action
                    </motion.span>

                    <motion.h2
                        variants={revealUp}
                        className="mt-8 text-4xl font-medium tracking-tight text-balance sm:text-5xl"
                    >
                        Watch Closr close.
                    </motion.h2>

                    <motion.p
                        variants={revealUp}
                        className="mt-6 max-w-xl text-base leading-relaxed text-pretty text-white/60 sm:text-lg"
                    >
                        A real conversation, start to sale. See how Closr answers
                        questions, recommends products, and guides shoppers to
                        checkout.
                    </motion.p>
                </div>

                <motion.div
                    variants={revealUp}
                    className="mt-14 border border-white/10 bg-neutral-900 p-2 sm:mt-20 sm:p-3"
                >
                    <video
                        className="aspect-video w-full bg-black object-cover"
                        src="/closr-demo.mp4"
                        autoPlay
                        loop
                        muted
                        playsInline
                        controls
                        preload="metadata"
                    />
                </motion.div>
            </motion.div>
        </section>
    );
}
