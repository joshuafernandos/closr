import { Link } from '@inertiajs/react';
import { MessageSquare, Search, SquarePen, User } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import { show as showConversation } from '@/routes/messages';
import { avatarColor } from './avatar-color';
import { LastActionBadge } from './last-action-badge';
import { relativeTime } from './relative-time';
import type { ConversationSummary, Paginator } from './types';

function ConversationListItem({
    conversation,
    active,
}: {
    conversation: ConversationSummary;
    active: boolean;
}) {
    return (
        <Link
            href={showConversation(conversation.id).url}
            preserveScroll
            preserveState
            only={['selected']}
            className={cn(
                'flex items-center gap-3 rounded-2xl px-3 py-2.5 transition',
                active ? 'bg-blue-50 dark:bg-blue-500/10' : 'hover:bg-muted/60',
            )}
        >
            <Avatar className="size-11 shrink-0">
                <AvatarFallback className={avatarColor(conversation.id)}>
                    <User className="size-5" />
                </AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between gap-2">
                    <p
                        className={cn(
                            'truncate text-sm font-semibold',
                            active && 'text-blue-700 dark:text-blue-300',
                        )}
                    >
                        {conversation.title}
                    </p>
                    <span className="shrink-0 text-xs text-muted-foreground">
                        {relativeTime(conversation.lastMessageAt)}
                    </span>
                </div>
                <div className="mt-0.5 flex items-center justify-between gap-2">
                    <p className="truncate text-xs text-muted-foreground">
                        {conversation.preview ?? 'No messages yet'}
                    </p>
                    <LastActionBadge
                        action={conversation.lastAction}
                        label={conversation.lastActionLabel}
                        compact
                    />
                </div>
            </div>
        </Link>
    );
}

/** A page-number / prev-next pagination strip for the conversation list. */
function Pagination({
    paginator,
}: {
    paginator: Paginator<ConversationSummary>;
}) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1 border-t p-3">
            {paginator.links.map((link, index) => {
                const label = link.label
                    .replace('&laquo;', '‹')
                    .replace('&raquo;', '›');

                if (link.url === null) {
                    return (
                        <span
                            key={index}
                            className="px-2.5 py-1 text-xs text-muted-foreground/50"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        className={cn(
                            'rounded-md px-2.5 py-1 text-xs font-medium transition',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'text-muted-foreground hover:bg-muted',
                        )}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </nav>
    );
}

/**
 * The left-hand list of shopper conversations, paginated 12 per page, with a
 * client-side filter over the current page and the active conversation
 * highlighted.
 */
export function ConversationList({
    conversations,
    selectedId,
}: {
    conversations: Paginator<ConversationSummary>;
    selectedId: number | null;
}) {
    const [query, setQuery] = useState('');

    const filtered = useMemo(() => {
        const term = query.trim().toLowerCase();

        if (term === '') {
            return conversations.data;
        }

        return conversations.data.filter((conversation) =>
            `${conversation.title} ${conversation.preview ?? ''}`
                .toLowerCase()
                .includes(term),
        );
    }, [conversations.data, query]);

    return (
        <div className="flex h-full flex-col">

            <div className="pb-3 pe-4">
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search conversations..."
                        className="h-10 w-full rounded-full border bg-muted/40 pr-3 pl-9 text-sm transition outline-none focus:border-blue-500 focus:bg-card"
                    />
                </div>
            </div>

            {filtered.length === 0 ? (
                <div className="flex flex-1 flex-col items-center justify-center gap-2 pe-4 text-center text-muted-foreground">
                    <MessageSquare className="size-8 opacity-40" />
                    <p className="text-sm">
                        {query.trim() === ''
                            ? 'No conversations yet'
                            : 'No matches on this page'}
                    </p>
                </div>
            ) : (
                <div className="flex-1 space-y-1 overflow-y-auto pe-4">
                    {filtered.map((conversation) => (
                        <ConversationListItem
                            key={conversation.id}
                            conversation={conversation}
                            active={conversation.id === selectedId}
                        />
                    ))}
                </div>
            )}

            <Pagination paginator={conversations} />
        </div>
    );
}
