import {
    MessagesSquare,
    MoreHorizontal,
    ShoppingCart,
    Sparkles,
    User,
} from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import { avatarColor } from './avatar-color';
import { LastActionBadge } from './last-action-badge';
import { clockTime, dayKey, dayLabel } from './relative-time';
import type {
    ConversationMessage,
    ConversationProduct,
    SelectedConversation,
} from './types';

/** A run of consecutive turns from the same sender on the same day. */
type MessageGroup = {
    role: 'user' | 'assistant';
    messages: ConversationMessage[];
};

/** A single rendered block in the transcript: a date divider, a message group, or a shopper action. */
type TranscriptItem =
    | { kind: 'date'; key: string; label: string }
    | { kind: 'group'; key: string; group: MessageGroup }
    | { kind: 'event'; key: string; message: ConversationMessage };

/**
 * Fold the flat, oldest-first transcript into date dividers, per-sender groups,
 * and inline event rows so each speaker's avatar and timestamp show once per
 * run while shopper actions break out as their own centered line.
 */
function buildTranscript(messages: ConversationMessage[]): TranscriptItem[] {
    const items: TranscriptItem[] = [];
    let lastDay: string | null = null;
    let current: MessageGroup | null = null;

    messages.forEach((message, index) => {
        const day = dayKey(message.createdAt);

        if (day !== lastDay) {
            items.push({
                kind: 'date',
                key: `date-${day}-${index}`,
                label: dayLabel(message.createdAt),
            });
            lastDay = day;
            current = null;
        }

        if (message.type === 'event') {
            current = null;
            items.push({
                kind: 'event',
                key: `event-${message.id}`,
                message,
            });

            return;
        }

        if (current === null || current.role !== message.role) {
            current = { role: message.role, messages: [] };
            items.push({
                kind: 'group',
                key: `group-${message.id}`,
                group: current,
            });
        }

        current.messages.push(message);
    });

    return items;
}

/**
 * A shopper action replayed inline — adding a product to the cart, with the
 * variation they picked — rendered as a centered system line so it reads as an
 * event in the timeline rather than a chat bubble.
 */
function EventRow({ message }: { message: ConversationMessage }) {
    const product = message.products[0];
    const variant = message.meta?.variant ?? null;
    const variantLabel = variant ? Object.values(variant).join(' / ') : null;
    const title = product?.title ?? 'product';

    return (
        <div className="flex justify-center py-1">
            <div className="flex items-center gap-2 rounded-full border bg-card px-3 py-1.5 text-xs text-muted-foreground shadow-sm">
                <ShoppingCart className="size-3.5 text-emerald-600 dark:text-emerald-400" />
                <span>
                    Added{' '}
                    <span className="font-medium text-foreground">{title}</span>
                    {variantLabel && (
                        <span className="text-foreground/80">
                            {' '}
                            ({variantLabel})
                        </span>
                    )}{' '}
                    to cart
                </span>
                <span className="text-muted-foreground/70">
                    {clockTime(message.createdAt)}
                </span>
            </div>
        </div>
    );
}

function ProductRow({ products }: { products: ConversationProduct[] }) {
    return (
        <div className="mt-2 flex gap-2 overflow-x-auto pb-1">
            {products.map((product) => (
                <div
                    key={product.id}
                    className="flex w-36 shrink-0 flex-col overflow-hidden rounded-2xl border bg-card shadow-sm"
                >
                    <div className="aspect-square bg-muted/40">
                        <img
                            src={product.thumbnail}
                            alt={product.title}
                            loading="lazy"
                            className="size-full object-contain p-2"
                        />
                    </div>
                    <div className="flex flex-col gap-0.5 p-2">
                        <p className="line-clamp-2 text-xs font-semibold">
                            {product.title}
                        </p>
                        <span className="text-xs text-muted-foreground">
                            ${product.price.toFixed(2)}
                        </span>
                    </div>
                </div>
            ))}
        </div>
    );
}

/**
 * One sender's run of turns: an avatar + name + time header followed by the
 * stacked message bubbles, aligned left for the shopper and right for Closr.
 */
function MessageGroupBlock({ group }: { group: MessageGroup }) {
    const isClosr = group.role === 'assistant';
    const lead = group.messages[0];

    const avatar = isClosr ? (
        <Avatar className="size-9">
            <AvatarFallback className="bg-blue-600 text-white">
                <Sparkles className="size-4" />
            </AvatarFallback>
        </Avatar>
    ) : (
        <Avatar className="size-9">
            <AvatarFallback className="bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <User className="size-4" />
            </AvatarFallback>
        </Avatar>
    );

    return (
        <div
            className={cn(
                'flex gap-3',
                isClosr ? 'flex-row-reverse' : 'flex-row',
            )}
        >
            <div className="mt-6 shrink-0">{avatar}</div>

            <div
                className={cn(
                    'flex min-w-0 flex-col gap-1.5',
                    isClosr ? 'items-end' : 'items-start',
                )}
            >
                <div className="flex items-center gap-2 px-1">
                    <span className="text-sm font-semibold">
                        {isClosr ? 'Closr' : 'Shopper'}
                    </span>
                    <span className="text-xs text-muted-foreground">
                        {clockTime(lead.createdAt)}
                    </span>
                </div>

                {group.messages.map((message) => (
                    <div
                        key={message.id}
                        className={cn(
                            'flex w-full flex-col',
                            isClosr ? 'items-end' : 'items-start',
                        )}
                    >
                        <div
                            className={cn(
                                'max-w-md rounded-2xl px-4 py-2.5 text-sm leading-relaxed',
                                isClosr
                                    ? 'rounded-tr-sm bg-blue-600 text-white'
                                    : 'rounded-tl-sm border bg-card text-foreground shadow-sm',
                            )}
                        >
                            {message.content}
                        </div>

                        {message.products.length > 0 && (
                            <div className="w-full max-w-md">
                                <ProductRow products={message.products} />
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

/**
 * The right-hand transcript pane, replaying a single shopper conversation as a
 * chat thread — shopper on the left, Closr on the right — including the product
 * cards the shopper was shown.
 */
export function ConversationView({
    conversation,
}: {
    conversation: SelectedConversation | null;
}) {
    if (conversation === null) {
        return (
            <div className="flex h-full flex-col items-center justify-center gap-2 bg-muted/30 text-muted-foreground">
                <MessagesSquare className="size-10 opacity-40" />
                <p className="text-sm">Select a conversation to read it</p>
            </div>
        );
    }

    const transcript = buildTranscript(conversation.messages);

    return (
        <div className="flex h-full w-full flex-col">
            {/* Header */}
            <header className="flex items-center justify-between gap-3 border-b bg-card px-5 py-3.5">
                <div className="flex min-w-0 items-center gap-3">
                    <Avatar className="size-10">
                        <AvatarFallback
                            className={avatarColor(conversation.id)}
                        >
                            <User className="size-4" />
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-bold">
                            {conversation.title}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {conversation.messages.length} messages
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <LastActionBadge
                        action={conversation.lastAction}
                        label={conversation.lastActionLabel}
                    />
                    <button
                        type="button"
                        disabled
                        className="flex size-9 items-center justify-center rounded-full text-muted-foreground/60"
                        aria-label="More"
                    >
                        <MoreHorizontal className="size-4" />
                    </button>
                </div>
            </header>

            {/* Transcript */}
            <div className="flex-1 space-y-4 overflow-y-auto bg-muted/30 px-5 py-6">
                {transcript.map((item) => {
                    if (item.kind === 'date') {
                        return (
                            <div
                                key={item.key}
                                className="flex justify-center py-1"
                            >
                                <span className="rounded-full bg-card px-3 py-1 text-xs font-medium text-muted-foreground shadow-sm">
                                    {item.label}
                                </span>
                            </div>
                        );
                    }

                    if (item.kind === 'event') {
                        return (
                            <EventRow key={item.key} message={item.message} />
                        );
                    }

                    return (
                        <MessageGroupBlock key={item.key} group={item.group} />
                    );
                })}
            </div>

            {/* Read-only footer — shoppers chat through the merchant's widget. */}
            <div className="border-t bg-card px-5 py-3">
                <p className="text-center text-xs text-muted-foreground">
                    Read-only transcript. Shoppers chat through your storefront
                    widget.
                </p>
            </div>
        </div>
    );
}
