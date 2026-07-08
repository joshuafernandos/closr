import { useEffect, useRef } from 'react';

type DottedWavesProps = {
    /** Spacing between dots in CSS pixels. */
    gap?: number;
    /** Base dot colour (accepts any CSS colour string). */
    color?: string;
    className?: string;
};

/**
 * An animated field of dots that ripples with ambient sine waves and reacts
 * to the pointer — dots near the cursor swell, brighten, and get pushed away,
 * trailing the mouse like waves on water. Rendered on a canvas for smoothness.
 */
export default function DottedWaves({
    gap = 26,
    color = '184, 240, 0',
    className,
}: DottedWavesProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const container = containerRef.current;
        const canvas = canvasRef.current;

        if (!container || !canvas) {
            return;
        }

        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        let width = 0;
        let height = 0;
        let frame = 0;
        let start = performance.now();

        // Target pointer position and its eased follower for a trailing feel.
        const pointer = { x: -9999, y: -9999, active: false };
        const eased = { x: -9999, y: -9999, strength: 0 };

        const setSize = () => {
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            const rect = container.getBoundingClientRect();
            width = rect.width;
            height = rect.height;
            canvas.width = Math.floor(width * ratio);
            canvas.height = Math.floor(height * ratio);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
        };

        const draw = (now: number) => {
            const time = (now - start) / 1000;
            context.clearRect(0, 0, width, height);

            // Ease the follower toward the pointer for a soft trailing wave.
            eased.x += (pointer.x - eased.x) * 0.08;
            eased.y += (pointer.y - eased.y) * 0.08;
            const targetStrength = pointer.active ? 1 : 0;
            eased.strength += (targetStrength - eased.strength) * 0.06;

            const radius = 150;

            for (let x = gap; x < width; x += gap) {
                for (let y = gap; y < height; y += gap) {
                    // Ambient wave displacement.
                    const wave = reduceMotion
                        ? 0
                        : Math.sin(x * 0.02 + time) +
                          Math.cos(y * 0.025 + time * 0.8);

                    let offsetX = wave * 1.6;
                    let offsetY = wave * 1.6;
                    let scale = 1 + wave * 0.12;
                    let alpha = 0.16 + Math.abs(wave) * 0.06;

                    // Pointer ripple: push dots outward and light them up.
                    const dx = x - eased.x;
                    const dy = y - eased.y;
                    const dist = Math.hypot(dx, dy);

                    if (eased.strength > 0.01 && dist < radius) {
                        const influence = (1 - dist / radius) * eased.strength;
                        const push = influence * 18;
                        const angle = Math.atan2(dy, dx);
                        offsetX += Math.cos(angle) * push;
                        offsetY += Math.sin(angle) * push;
                        scale += influence * 1.8;
                        alpha += influence * 0.8;
                    }

                    const size = Math.max(0.4, 1.1 * scale);
                    context.beginPath();
                    context.arc(x + offsetX, y + offsetY, size, 0, Math.PI * 2);
                    context.fillStyle = `rgba(${color}, ${Math.min(alpha, 1)})`;
                    context.fill();
                }
            }

            frame = requestAnimationFrame(draw);
        };

        const handlePointerMove = (event: PointerEvent) => {
            const rect = container.getBoundingClientRect();
            pointer.x = event.clientX - rect.left;
            pointer.y = event.clientY - rect.top;
            pointer.active = true;
        };

        const handlePointerLeave = () => {
            pointer.active = false;
        };

        setSize();
        start = performance.now();
        frame = requestAnimationFrame(draw);

        const resizeObserver = new ResizeObserver(setSize);
        resizeObserver.observe(container);
        container.addEventListener('pointermove', handlePointerMove);
        container.addEventListener('pointerleave', handlePointerLeave);

        return () => {
            cancelAnimationFrame(frame);
            resizeObserver.disconnect();
            container.removeEventListener('pointermove', handlePointerMove);
            container.removeEventListener('pointerleave', handlePointerLeave);
        };
    }, [gap, color]);

    return (
        <div ref={containerRef} className={className}>
            <canvas ref={canvasRef} className="block h-full w-full" />
        </div>
    );
}
