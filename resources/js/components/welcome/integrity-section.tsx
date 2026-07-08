import {
    motion,
    useScroll,
    useTransform,
    type MotionValue,
    type Variants,
} from 'motion/react';
import { Boxes, MessagesSquare, TrendingUp } from 'lucide-react';
import { useRef, type ComponentType, type CSSProperties, type ReactNode } from 'react';

const smoothEase = [0.22, 1, 0.36, 1] as const;

const revealUp: Variants = {
    hidden: { opacity: 0, y: 48 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.9, ease: smoothEase },
    },
};

/**
 * Inline highlight whose black background wipe and text colour are driven by
 * the section's scroll progress. Scrolling down fills the highlight in; scrolling
 * back up empties it again, so the effect is fully reversible.
 */
function Highlight({
    icon: Icon,
    progress,
    range,
    children,
}: {
    icon: ComponentType<{ className?: string; style?: CSSProperties }>;
    progress: MotionValue<number>;
    range: [number, number];
    children: ReactNode;
}) {
    const [start, end] = range;
    const mid = start + (end - start) * 0.55;

    const backgroundSize = useTransform(
        progress,
        [start, end],
        ['0% 100%', '100% 100%'],
    );
    const color = useTransform(progress, [mid, end], ['#000000', '#ffffff']);
    const iconScale = useTransform(progress, [start, mid], [0.4, 1]);
    const iconOpacity = useTransform(progress, [start, mid], [0, 1]);

    return (
        <span className="inline">
            <motion.span
                aria-hidden="true"
                style={{
                    scale: iconScale,
                    opacity: iconOpacity,
                    marginRight: '0.18em',
                    width: '0.95em',
                    height: '0.95em',
                    transform: 'translateY(0.06em)',
                }}
                className="inline-flex items-center justify-center bg-primary text-black"
            >
                <Icon style={{ width: '0.6em', height: '0.6em' }} />
            </motion.span>
            <motion.span
                style={{
                    backgroundSize,
                    color,
                    paddingInline: '0.18em',
                    boxDecorationBreak: 'clone',
                    WebkitBoxDecorationBreak: 'clone',
                    backgroundImage: 'linear-gradient(#000000, #000000)',
                    backgroundRepeat: 'no-repeat',
                }}
            >
                {children}
            </motion.span>
        </span>
    );
}

export default function IntegritySection() {
    const ref = useRef<HTMLDivElement>(null);
    const { scrollYProgress } = useScroll({
        target: ref,
        offset: ['start 0.85', 'end end'],
    });

    return (
        <section className="bg-white text-black">
            <motion.div
                ref={ref}
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.25 }}
                transition={{ delayChildren: 0.45, staggerChildren: 0.25 }}
                className="mx-auto max-w-7xl px-6 py-24 sm:py-32"
            >
                <motion.span
                    variants={revealUp}
                    className="inline-block bg-secondary px-3 py-1.5 font-mono text-xs tracking-wide text-muted-foreground uppercase"
                >
                    Selling, rebuilt around the shopper
                </motion.span>

                <motion.h2
                    variants={revealUp}
                    className="mt-8 max-w-5xl text-3xl font-medium tracking-tight text-balance sm:text-4xl lg:text-5xl"
                    style={{ lineHeight: 1.18 }}
                >
                    Closr is the only assistant that knows your catalogue, asks
                    the right questions, and gets shoppers to exactly what they
                    need. The result?{' '}
                    <Highlight
                        icon={Boxes}
                        progress={scrollYProgress}
                        range={[0.3, 0.55]}
                    >
                        More conversions
                    </Highlight>
                    ,{' '}
                    <Highlight
                        icon={MessagesSquare}
                        progress={scrollYProgress}
                        range={[0.5, 0.75]}
                    >
                        happier shoppers
                    </Highlight>
                    , and{' '}
                    <Highlight
                        icon={TrendingUp}
                        progress={scrollYProgress}
                        range={[0.7, 1]}
                    >
                        sales that close themselves
                    </Highlight>
                    .
                </motion.h2>
            </motion.div>
        </section>
    );
}
