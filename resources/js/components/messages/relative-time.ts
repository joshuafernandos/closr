/**
 * Format an ISO timestamp as a short, human relative label (e.g. "5m",
 * "3h", "2d") for the conversation list, falling back to a date for old
 * activity. Returns an empty string when there's no timestamp.
 */
export function relativeTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h`;
    }

    const days = Math.floor(hours / 24);

    if (days < 7) {
        return `${days}d`;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

/** Format an ISO timestamp as a clock time (e.g. "05:11 PM"). */
export function clockTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Format an ISO timestamp as a day separator label ("Today", "Yesterday", or a
 * full date) for the transcript's date dividers.
 */
export function dayLabel(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);
    const startOfDay = (value: Date): number =>
        new Date(
            value.getFullYear(),
            value.getMonth(),
            value.getDate(),
        ).getTime();

    const days = Math.round(
        (startOfDay(new Date()) - startOfDay(date)) / 86_400_000,
    );

    if (days === 0) {
        return 'Today';
    }

    if (days === 1) {
        return 'Yesterday';
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}

/** A date key (YYYY-MM-DD in local time) used to group turns by day. */
export function dayKey(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);

    return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
}
