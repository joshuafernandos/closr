/** A recommended product card captured in an assistant turn. */
export type ConversationProduct = {
    id: number;
    title: string;
    price: number;
    thumbnail: string;
    category?: string;
    reason?: string;
    cartUrl?: string | null;
};

/** Structured payload carried by an `event` row in the transcript. */
export type ConversationEventMeta = {
    action: string;
    variant?: Record<string, string> | null;
};

/** A single row in a conversation transcript: a chat turn or a shopper action. */
export type ConversationMessage = {
    id: number;
    role: 'user' | 'assistant';
    type?: 'message' | 'event';
    content: string;
    step?: string | null;
    products: ConversationProduct[];
    meta?: ConversationEventMeta | null;
    createdAt: string | null;
};

/** A conversation summary as shown in the paginated list. */
export type ConversationSummary = {
    id: number;
    title: string;
    preview: string | null;
    lastAction: string | null;
    lastActionLabel: string | null;
    messageCount: number;
    lastMessageAt: string | null;
};

/** The full conversation shown in the detail pane. */
export type SelectedConversation = {
    id: number;
    title: string;
    lastAction: string | null;
    lastActionLabel: string | null;
    lastMessageAt: string | null;
    messages: ConversationMessage[];
};

/** A single link in a Laravel paginator's `links` array. */
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** A Laravel length-aware paginator, as serialized to the frontend. */
export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};
