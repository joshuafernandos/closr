import { Head } from '@inertiajs/react';
import { ArrowUp, Search, ShoppingBag, Sparkles } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import {
    action as chatActionRoute,
    category as chatCategoryRoute,
    conversation as chatConversationRoute,
    message as chatMessageRoute,
    variantCart as chatVariantCartRoute,
} from '@/actions/App/Http/Controllers/ChatController';
import { MessageBubble } from '@/components/widget/message-bubble';
import type {
    ChatResponse,
    Message,
    Product,
    StoredMessage,
    Turn,
} from '@/components/widget/types';

/**
 * Closr chat — a receptionist-style assistant that walks a shopper through
 * Greet → Qualify → Recommend → Checkout (add to bag).
 *
 * The conversation is driven by Claude via the laravel/ai SDK: each free-text
 * shopper turn is POSTed to ChatController@message, which prompts the
 * Receptionist agent. The agent searches the store live (SearchProducts tool)
 * and returns structured output (reply + step + recommended products).
 *
 * To keep model calls minimal, several interactions skip Claude entirely:
 *  - The opening greeting is static (it never varies), so no turn is sent on
 *    mount.
 *  - Tapping a category chip browses that category's products directly via
 *    ChatController@category — the same listing every time.
 *  - Choosing a product's variations and adding to the bag is client-side; only
 *    resolving the variation's cart URL hits the server (no model call).
 */

/** Omit that distributes across a union so per-member excess checks still apply. */
type DistributiveOmit<T, K extends keyof never> = T extends unknown
    ? Omit<T, K>
    : never;

// The opening greeting never varies, so it's rendered client-side instead of
// spending a model call. It's also seeded into the transcript history so any
// later free-text turn gives the agent the context that it already greeted.
const GREETING = 'Hi! What are you shopping for today?';

const STARTERS = [
    'I need a gift',
    'Show me smartphones',
    'Something for skincare',
    'I need a laptop',
];

let messageId = 0;
const nextId = () => ++messageId;

/** Read Laravel's XSRF-TOKEN cookie so the POST passes CSRF verification. */
function xsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=')[1]) : '';
}

/** POST JSON to a Closr widget endpoint with the CSRF + XHR headers it expects. */
async function postJson<T>(
    url: string,
    body: unknown,
    { keepalive = false }: { keepalive?: boolean } = {},
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(`Closr request failed (${response.status})`);
    }

    return response.json();
}

/**
 * A stable per-shopper id kept in localStorage so the server can group this
 * browser's turns into one conversation and restore them on the next visit.
 */
function resolveSessionId(widgetKey?: string): string {
    const storageKey = `closr_session_${widgetKey ?? 'demo'}`;

    try {
        const existing = window.localStorage.getItem(storageKey);

        if (existing) {
            return existing;
        }

        const generated = `sess_${crypto.randomUUID()}`;
        window.localStorage.setItem(storageKey, generated);

        return generated;
    } catch {
        // Private mode / storage disabled — fall back to a per-load id so the
        // conversation still persists server-side for this session.
        return `sess_${crypto.randomUUID()}`;
    }
}

/** Send a free-text shopper turn to the agent (costs a model call). */
function askClosr(
    message: string,
    history: Turn[],
    widgetKey: string | undefined,
    session: string,
): Promise<ChatResponse> {
    return postJson<ChatResponse>(chatMessageRoute.url(), {
        message,
        history,
        key: widgetKey,
        session,
    });
}

/** Fetch a category's products directly — no model call. */
async function browseCategory(
    category: string,
    widgetKey: string | undefined,
    session: string,
): Promise<Product[]> {
    const { products } = await postJson<{ products: Product[] }>(
        chatCategoryRoute.url(),
        { category, key: widgetKey, session },
    );

    return products;
}

/** Resolve the storefront cart URL for a chosen product variation. */
async function resolveVariantCart(
    productId: number,
    attributes: Record<string, string>,
    widgetKey: string | undefined,
    session: string,
): Promise<string | null> {
    const { cartUrl } = await postJson<{ cartUrl: string | null }>(
        chatVariantCartRoute.url(),
        { productId, attributes, key: widgetKey, session },
    );

    return cartUrl;
}

/** Restore this session's prior transcript from the server, if any. */
async function loadConversation(
    widgetKey: string | undefined,
    session: string,
): Promise<StoredMessage[]> {
    const { messages } = await postJson<{ messages: StoredMessage[] }>(
        chatConversationRoute.url(),
        { key: widgetKey, session },
    );

    return messages;
}

/** The product summary sent with a cart action so it replays in the transcript. */
type CartProduct = Pick<Product, 'id' | 'title' | 'price' | 'thumbnail'>;

/** Trim a product down to the fields the transcript event row needs. */
function compactProduct(product: Product): CartProduct {
    return {
        id: product.id,
        title: product.title,
        price: product.price,
        thumbnail: product.thumbnail,
    };
}

/**
 * Report a shopper-side action (added to cart, visited a product page) for this
 * session. Conversations default to "Left" server-side, so leaving needs no
 * report — only better outcomes are sent. Cart additions carry the product (and
 * the chosen variation) so the merchant replays them inline in the transcript.
 */
function reportAction(
    action: 'added_to_cart' | 'visited_product_page',
    widgetKey: string | undefined,
    session: string,
    details: { product?: CartProduct; variant?: Record<string, string> } = {},
): void {
    void postJson(chatActionRoute.url(), {
        action,
        key: widgetKey,
        session,
        product: details.product,
        variant: details.variant,
    }).catch(() => {
        // Best-effort telemetry — never block the shopper on a failed report.
    });
}

export default function Chat({
    widgetKey,
    storeName,
    categories,
    template = 'chat',
    accentColor = '#171717',
}: {
    widgetKey?: string;
    storeName?: string;
    categories?: string[];
    template?: 'chat' | 'component';
    accentColor?: string;
}) {
    // The assistant is branded as the merchant's own ("Acme shop assistant"),
    // with Closr credited only in the footer.
    const assistantTitle = `${storeName ?? 'Shop'} shop assistant`;
    // The inline component template fills the page with a wider panel; the chat
    // template floats a centred card.
    const isComponent = template === 'component';
    // Prefer the merchant's live store categories as quick-picks; tapping one
    // browses that category's products directly (no model call). Fall back to
    // generic starter intents when the store exposes no categories — those are
    // free-text and do go through the agent.
    const preferences =
        categories && categories.length > 0
            ? categories.map((category) => ({
                  label: category,
                  category,
              }))
            : STARTERS.map((starter) => ({
                  label: starter,
                  message: starter,
              }));

    // Seed the transcript with the static greeting so opening the widget costs
    // no model call.
    const [messages, setMessages] = useState<Message[]>(() => [
        {
            id: nextId(),
            role: 'assistant',
            kind: 'text',
            text: GREETING,
            step: 'greet',
        },
    ]);
    const [input, setInput] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const [hasStarted, setHasStarted] = useState(false);
    // Bag is a demo-only, client-side cart — adding never calls the agent.
    const [bagIds, setBagIds] = useState<Set<number>>(new Set());

    // Stable per-shopper id; ties every turn back to one server-side
    // conversation so it survives reloads and the merchant can replay it.
    const [sessionId] = useState(() => resolveSessionId(widgetKey));

    // The transcript sent to the agent on free-text turns. Seeded with the
    // greeting so the agent knows it has already greeted.
    const historyRef = useRef<Turn[]>([
        { role: 'assistant', content: GREETING },
    ]);
    const scrollRef = useRef<HTMLDivElement>(null);
    // Wraps the input-only inline state so we can size the iframe to it.
    const inlineBoxRef = useRef<HTMLDivElement>(null);
    // True once the shopper expands the panel themselves (vs. restoring an
    // existing conversation on load) — only then do we animate the expansion.
    const [userExpanded, setUserExpanded] = useState(false);

    // The widget is always light, regardless of the merchant's OS theme — strip
    // any `dark` class the global theme bootstrap applied before first paint.
    useLayoutEffect(() => {
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';
    }, []);

    // Inline (component) template: report the content height to the loader script
    // so it can size the embedded iframe — collapsed to the search box before the
    // first turn, then a fixed scrollable panel once the conversation starts.
    useLayoutEffect(() => {
        if (!isComponent) {
            return;
        }

        const postHeight = (height: number, animate = false) => {
            window.parent?.postMessage(
                { type: 'closr:resize', height: Math.ceil(height), animate },
                '*',
            );
        };

        if (hasStarted) {
            // Animate only a shopper-driven expansion; a restored conversation
            // should appear at full height instantly (no collapse-then-grow).
            postHeight(520, userExpanded);

            return;
        }

        const box = inlineBoxRef.current;

        if (!box) {
            return;
        }

        postHeight(box.offsetHeight);
        const observer = new ResizeObserver(() => postHeight(box.offsetHeight));
        observer.observe(box);

        return () => observer.disconnect();
    }, [isComponent, hasStarted, userExpanded]);

    const pushMessage = (message: DistributiveOmit<Message, 'id'>) => {
        setMessages((prev) => [
            ...prev,
            { ...message, id: nextId() } as Message,
        ]);
    };

    /** Send a turn to Closr and render its reply (and any product cards). */
    const sendTurn = async (
        message: string,
        { display = true }: { display?: boolean } = {},
    ) => {
        if (display) {
            pushMessage({ role: 'shopper', kind: 'text', text: message });
            setUserExpanded(true);
            setHasStarted(true);
        }

        const history = [...historyRef.current];
        historyRef.current.push({ role: 'user', content: message });
        setIsTyping(true);

        try {
            const { reply, step, products } = await askClosr(
                message,
                history,
                widgetKey,
                sessionId,
            );
            historyRef.current.push({ role: 'assistant', content: reply });

            if (step === 'recommend' && products.length > 0) {
                pushMessage({
                    role: 'assistant',
                    kind: 'products',
                    text: reply,
                    products,
                });
            } else {
                pushMessage({
                    role: 'assistant',
                    kind: 'text',
                    text: reply,
                    step,
                });
            }
        } catch {
            pushMessage({
                role: 'assistant',
                kind: 'text',
                text: 'Sorry, I had trouble connecting just now. Please try again in a moment.',
            });
        } finally {
            setIsTyping(false);
        }
    };

    /**
     * Browse a category's products directly, with no model call. Renders the
     * shopper's pick and the resulting product slider, and records both in the
     * history so a follow-up free-text turn still has the context.
     */
    const browseCategoryProducts = async (category: string) => {
        const ask = `Show me ${category}`;
        pushMessage({ role: 'shopper', kind: 'text', text: ask });
        setUserExpanded(true);
        setHasStarted(true);
        historyRef.current.push({ role: 'user', content: ask });
        setIsTyping(true);

        try {
            const products = await browseCategory(
                category,
                widgetKey,
                sessionId,
            );

            if (products.length === 0) {
                const reply = `I couldn't find anything in ${category} right now. Tell me what you're after and I'll dig deeper.`;
                historyRef.current.push({ role: 'assistant', content: reply });
                pushMessage({ role: 'assistant', kind: 'text', text: reply });

                return;
            }

            const reply = `Here's what we have in ${category}:`;
            historyRef.current.push({ role: 'assistant', content: reply });
            pushMessage({
                role: 'assistant',
                kind: 'products',
                text: reply,
                products,
            });
        } catch {
            pushMessage({
                role: 'assistant',
                kind: 'text',
                text: 'Sorry, I had trouble loading that just now. Please try again in a moment.',
            });
        } finally {
            setIsTyping(false);
        }
    };

    useEffect(() => {
        scrollRef.current?.scrollTo({
            top: scrollRef.current.scrollHeight,
            behavior: 'smooth',
        });
    }, [messages, isTyping]);

    // Restore this session's prior transcript on mount so the shopper picks up
    // where they left off. The static greeting stays first; stored turns follow.
    useEffect(() => {
        let cancelled = false;

        void loadConversation(widgetKey, sessionId)
            .then((stored) => {
                if (cancelled || stored.length === 0) {
                    return;
                }

                const restored = stored.map((message): Message => {
                    if (message.role === 'user') {
                        return {
                            id: nextId(),
                            role: 'shopper',
                            kind: 'text',
                            text: message.content,
                        };
                    }

                    if (
                        message.step === 'recommend' &&
                        message.products.length > 0
                    ) {
                        return {
                            id: nextId(),
                            role: 'assistant',
                            kind: 'products',
                            text: message.content,
                            products: message.products,
                        };
                    }

                    return {
                        id: nextId(),
                        role: 'assistant',
                        kind: 'text',
                        text: message.content,
                        step: message.step ?? undefined,
                    };
                });

                setMessages((prev) => [...prev, ...restored]);
                historyRef.current.push(
                    ...stored.map((message) => ({
                        role: message.role,
                        content: message.content,
                    })),
                );
                setHasStarted(true);
            })
            .catch(() => {
                // No restore available — start fresh from the greeting.
            });

        return () => {
            cancelled = true;
        };
        // Session id is stable for the widget's lifetime, so this runs once.
    }, [sessionId, widgetKey]);

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        const text = input.trim();

        if (!text || isTyping) {
            return;
        }

        setInput('');
        void sendTurn(text);
    };

    /** Add a product to the local demo bag. Callers report the cart action. */
    const addToBag = (productId: number) => {
        setBagIds((prev) => new Set(prev).add(productId));
    };

    /**
     * The shopper opened a product's storefront page ("View details"). Record it
     * so the merchant sees "Visited product page" as the conversation's outcome.
     */
    const handleViewDetails = () => {
        reportAction('visited_product_page', widgetKey, sessionId);
    };

    /**
     * Handle the card's primary action. Products with variations open a
     * follow-up picker instead of adding straight away; simple products are
     * added as-is — opening the storefront cart when there's one, otherwise
     * toggling the local demo bag.
     */
    const handleAddToCart = (product: Product) => {
        if ((product.variations?.length ?? 0) > 0) {
            pushMessage({
                role: 'assistant',
                kind: 'variation',
                product,
                resolved: false,
            });
            historyRef.current.push({
                role: 'assistant',
                content: `Which options would you like for ${product.title}?`,
            });

            return;
        }

        setBagIds((prev) => {
            const next = new Set(prev);

            if (next.has(product.id)) {
                next.delete(product.id);
            } else {
                next.add(product.id);

                if (product.cartUrl) {
                    window.open(
                        product.cartUrl,
                        '_blank',
                        'noopener,noreferrer',
                    );
                }

                reportAction('added_to_cart', widgetKey, sessionId, {
                    product: compactProduct(product),
                });
            }

            return next;
        });
    };

    /**
     * Add the chosen variation to the cart: resolve the storefront cart URL for
     * the selected options (opening it when the store has a real cart), fall
     * back to the local demo bag otherwise, then lock the picker and confirm.
     */
    const handleConfirmVariation = async (
        confirmedMessageId: number,
        product: Product,
        selected: Record<string, string>,
    ) => {
        const summary = Object.values(selected).join(' / ');

        let cartUrl: string | null = product.cartUrl ?? null;

        try {
            cartUrl = await resolveVariantCart(
                product.id,
                selected,
                widgetKey,
                sessionId,
            );
        } catch {
            // Fall back to the bare product cart URL (or the local bag).
        }

        if (cartUrl) {
            window.open(cartUrl, '_blank', 'noopener,noreferrer');
        }

        addToBag(product.id);
        reportAction('added_to_cart', widgetKey, sessionId, {
            product: compactProduct(product),
            variant: selected,
        });

        setMessages((prev) =>
            prev.map((message) =>
                message.id === confirmedMessageId &&
                message.kind === 'variation'
                    ? { ...message, resolved: true }
                    : message,
            ),
        );

        const confirmation = `Added ${product.title} (${summary}) to your bag.`;
        historyRef.current.push({
            role: 'assistant',
            content: confirmation,
        });
        pushMessage({
            role: 'assistant',
            kind: 'text',
            text: confirmation,
            step: 'checkout',
        });
    };

    const composer = (
        <Composer
            input={input}
            onChange={setInput}
            onSubmit={handleSubmit}
            disabled={isTyping}
            accentColor={accentColor}
            variant={isComponent ? 'component' : 'chat'}
        />
    );

    // Inline component, before the first turn: show only the search box. The
    // full chat (header + transcript) appears once the shopper submits.
    if (isComponent && !hasStarted) {
        return (
            <>
                <Head title={assistantTitle} />
                <div ref={inlineBoxRef} className="bg-white px-1 py-1 text-black">
                    <div className="mx-auto w-full max-w-3xl">{composer}</div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={assistantTitle} />
            <div
                className={
                    isComponent
                        ? 'flex min-h-screen justify-center bg-white text-black dark:bg-black dark:text-white'
                        : 'flex min-h-screen items-stretch justify-center bg-neutral-100 text-black sm:items-center sm:p-4 dark:bg-neutral-950 dark:text-white'
                }
            >
                <div
                    className={
                        isComponent
                            ? `flex h-screen w-full max-w-3xl flex-col overflow-hidden bg-white dark:bg-black${userExpanded ? ' animate-in fade-in slide-in-from-top-2 duration-300' : ''}`
                            : 'flex h-screen w-full max-w-xl flex-col overflow-hidden border-black/10 bg-white shadow-2xl sm:h-[88vh] sm:max-h-[840px] sm:rounded-3xl sm:border dark:border-white/10 dark:bg-black'
                    }
                >
                    <Header
                        bagCount={bagIds.size}
                        storeName={storeName ?? 'Shop'}
                        accentColor={accentColor}
                    />

                    {!hasStarted ? (
                        // Landing — bold welcome with starter chips, composer pinned below.
                        <div className="flex flex-1 flex-col items-center justify-center px-6 text-center">
                            <div
                                className="mb-5 flex size-14 items-center justify-center rounded-2xl text-white"
                                style={{ backgroundColor: accentColor }}
                            >
                                <Sparkles className="size-7" />
                            </div>
                            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                What are you shopping for?
                            </h1>
                            <p className="mt-2 max-w-xs text-sm text-black/50 dark:text-white/50">
                                Tell me what you need and I'll find the right
                                product for you.
                            </p>

                            <div className="mt-6 flex flex-wrap justify-center gap-2">
                                {preferences.map((preference) => (
                                    <button
                                        key={preference.label}
                                        type="button"
                                        disabled={isTyping}
                                        onClick={() =>
                                            'category' in preference
                                                ? void browseCategoryProducts(
                                                      preference.category,
                                                  )
                                                : void sendTurn(
                                                      preference.message,
                                                  )
                                        }
                                        className="rounded-full border border-black/15 px-4 py-2 text-sm font-medium text-black transition hover:bg-black/5 disabled:opacity-50 dark:border-white/20 dark:text-white dark:hover:bg-white/10"
                                    >
                                        {preference.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ) : (
                        // Conversation — scrolling transcript capped to the panel height.
                        <div
                            ref={scrollRef}
                            className="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-5"
                        >
                            {messages.map((message) => (
                                <MessageBubble
                                    key={message.id}
                                    message={message}
                                    bagIds={bagIds}
                                    accentColor={accentColor}
                                    onAddToCart={handleAddToCart}
                                    onViewDetails={handleViewDetails}
                                    onConfirmVariation={handleConfirmVariation}
                                />
                            ))}

                            {isTyping && <TypingIndicator />}
                        </div>
                    )}

                    <div className="border-t border-black/5 p-3 sm:p-4 dark:border-white/10">
                        {composer}
                        <p className="mt-2 text-center text-[0.625rem] font-medium tracking-wide text-black/35 dark:text-white/35">
                            Powered by Closr
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

function Header({
    bagCount,
    storeName,
    accentColor,
}: {
    bagCount: number;
    storeName: string;
    accentColor: string;
}) {
    return (
        <header className="flex items-center justify-between border-b border-black/5 px-5 py-3.5 dark:border-white/10">
            <div className="flex items-center gap-2.5">
                <div
                    className="flex size-9 items-center justify-center rounded-full text-white"
                    style={{ backgroundColor: accentColor }}
                >
                    <Sparkles className="size-4.5" />
                </div>
                <div className="leading-tight">
                    <p className="text-sm font-bold">{storeName}</p>
                    <p className="text-xs text-black/45 dark:text-white/45">
                        Shop assistant
                    </p>
                </div>
            </div>

            <div className="relative">
                <ShoppingBag className="size-6" />
                {bagCount > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 flex size-4.5 items-center justify-center rounded-full bg-black text-[0.625rem] font-bold text-white tabular-nums dark:bg-white dark:text-black">
                        {bagCount}
                    </span>
                )}
            </div>
        </header>
    );
}

function Composer({
    input,
    onChange,
    onSubmit,
    disabled,
    accentColor,
    variant = 'chat',
}: {
    input: string;
    onChange: (value: string) => void;
    onSubmit: (event: React.FormEvent) => void;
    disabled: boolean;
    accentColor: string;
    variant?: 'chat' | 'component';
}) {
    // Inline component template: a roomy, search-bar style box — a tall input
    // over a secondary row with an assistant hint and a prominent search
    // button. Borderless to sit flush in the merchant's page.
    if (variant === 'component') {
        return (
            <form
                onSubmit={onSubmit}
                className="rounded-2xl bg-black/[0.03] px-4 pt-3.5 pb-2.5 dark:bg-white/[0.04]"
            >
                <input
                    value={input}
                    onChange={(event) => onChange(event.target.value)}
                    placeholder="Search products or ask anything…"
                    className="w-full bg-transparent text-base text-black outline-none placeholder:text-black/40 dark:text-white dark:placeholder:text-white/40"
                />
                <div className="mt-2.5 flex items-center justify-between">
                    <span className="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-black/45 dark:text-white/45">
                        <Sparkles
                            className="size-4"
                            style={{ color: accentColor }}
                        />
                        AI assistant
                    </span>
                    <button
                        type="submit"
                        disabled={disabled || input.trim().length === 0}
                        style={{ backgroundColor: accentColor }}
                        className="flex items-center gap-2 rounded-full px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-30"
                    >
                        <Search className="size-4" />
                        Search
                    </button>
                </div>
            </form>
        );
    }

    return (
        <form
            onSubmit={onSubmit}
            className="flex items-center gap-2 rounded-full border border-black/10 bg-black/[0.03] py-1.5 pr-1.5 pl-4 transition focus-within:border-black/30 dark:border-white/15 dark:bg-white/[0.04] dark:focus-within:border-white/40"
        >
            <input
                value={input}
                onChange={(event) => onChange(event.target.value)}
                placeholder="Ask anything…"
                className="min-w-0 flex-1 bg-transparent text-base text-black outline-none placeholder:text-black/40 dark:text-white dark:placeholder:text-white/40"
            />
            <button
                type="submit"
                disabled={disabled || input.trim().length === 0}
                aria-label="Send"
                style={{ backgroundColor: accentColor }}
                className="flex size-9 shrink-0 items-center justify-center rounded-full text-white transition hover:opacity-90 disabled:opacity-30"
            >
                <ArrowUp className="size-5" />
            </button>
        </form>
    );
}

function TypingIndicator() {
    return (
        <div className="flex justify-start">
            <div className="flex items-center gap-1 rounded-2xl rounded-tl-sm bg-black/5 px-4 py-3 dark:bg-white/10">
                <span className="size-2 animate-bounce rounded-full bg-black/40 [animation-delay:-0.3s] dark:bg-white/40" />
                <span className="size-2 animate-bounce rounded-full bg-black/40 [animation-delay:-0.15s] dark:bg-white/40" />
                <span className="size-2 animate-bounce rounded-full bg-black/40 dark:bg-white/40" />
            </div>
        </div>
    );
}
