/**
 * A palette of soft avatar tints, mirroring the dashboard's accent colors, so
 * anonymous shoppers each get a stable, distinct circle in the list.
 */
const palette = [
    'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400',
    'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400',
    'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
    'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
    'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400',
    'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
    'bg-teal-100 text-teal-600 dark:bg-teal-500/15 dark:text-teal-400',
    'bg-fuchsia-100 text-fuchsia-600 dark:bg-fuchsia-500/15 dark:text-fuchsia-400',
];

/**
 * Pick a stable tint for a shopper avatar from a seed (conversation id), so the
 * same conversation always renders the same color.
 */
export function avatarColor(seed: string | number): string {
    const value = String(seed);
    let hash = 0;

    for (let index = 0; index < value.length; index++) {
        hash = (hash * 31 + value.charCodeAt(index)) | 0;
    }

    return palette[Math.abs(hash) % palette.length];
}
