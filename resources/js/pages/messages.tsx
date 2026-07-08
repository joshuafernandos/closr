import { Head } from '@inertiajs/react';
import { ConversationList } from '@/components/messages/conversation-list';
import { ConversationView } from '@/components/messages/conversation-view';
import type {
    ConversationSummary,
    Paginator,
    SelectedConversation,
} from '@/components/messages/types';
import { index as messages } from '@/routes/messages';

type Props = {
    conversations: Paginator<ConversationSummary>;
    selected: SelectedConversation | null;
};

export default function Messages({ conversations, selected }: Props) {
    return (
        <>
            <Head title="Messages" />

            <div className="flex h-[calc(100svh-4rem)] flex-col overflow-hidden bg-muted/30 p-4 md:p-6">
                <div className="border-b pb-4">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Messages
                    </h1>
                </div>
                <div className="flex min-h-0 w-full flex-1 overflow-hidden">
                    {/* Conversation list */}
                    <div className="flex w-full max-w-xs shrink-0 flex-col border-r pt-6 sm:max-w-sm">
                        <ConversationList
                            conversations={conversations}
                            selectedId={selected?.id ?? null}
                        />
                    </div>

                    {/* Transcript */}
                    <div className="hidden min-w-0 flex-1 pt-2 md:flex">
                        <ConversationView conversation={selected} />
                    </div>
                </div>
            </div>
        </>
    );
}

Messages.layout = () => ({
    breadcrumbs: [
        {
            title: 'Messages',
            href: messages(),
        },
    ],
});
