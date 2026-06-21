import { Head } from '@inertiajs/react';
import {
    ArrowUp,
    Check,
    ChevronLeft,
    ChevronRight,
    ShoppingBag,
    Sparkles,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { message as chatMessageRoute } from '@/actions/App/Http/Controllers/ChatController';
import { cn } from '@/lib/utils';

/**
 * Closr chat — a receptionist-style assistant that walks a shopper through
 * Greet → Qualify → Recommend → Checkout (add to bag).
 *
 * The conversation is driven by Claude via the laravel/ai SDK: each shopper
 * turn is POSTed to ChatController@message, which prompts the Receptionist
 * agent. The agent searches dummyjson.com live (SearchProducts tool) and
 * returns structured output (reply + step + recommended products).
 *
 * Adding a product to the bag is a purely client-side action — it updates local
 * state and the bag indicator without sending another turn to the agent.
 */

type Step =
    | 'greet'
    | 'qualify'
    | 'recommend'
    | 'checkout'
    | 'done'
    | 'out_of_scope';

type Product = {
    id: number;
    title: string;
    price: number;
    thumbnail: string;
    category?: string;
    reason: string;
};

type ChatResponse = {
    reply: string;
    step: Step;
    products: Product[];
};

type Message =
    | {
          id: number;
          role: 'assistant' | 'shopper';
          kind: 'text';
          text: string;
          step?: Step;
      }
    | {
          id: number;
          role: 'assistant';
          kind: 'products';
          text: string;
          products: Product[];
      };

type Turn = { role: 'user' | 'assistant'; content: string };

/** Omit that distributes across a union so per-member excess checks still apply. */
type DistributiveOmit<T, K extends keyof never> = T extends unknown
    ? Omit<T, K>
    : never;

// The opener is sent to the agent so it greets in character, but is never shown.
const OPENER = 'The shopper just opened the chat.';

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

async function askClosr(
    message: string,
    history: Turn[],
    widgetKey?: string,
): Promise<ChatResponse> {
    const response = await fetch(chatMessageRoute.url(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify({ message, history, key: widgetKey }),
    });

    if (!response.ok) {
        throw new Error(`Closr request failed (${response.status})`);
    }

    return response.json();
}

export default function Chat({ widgetKey }: { widgetKey?: string }) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const [hasStarted, setHasStarted] = useState(false);
    // Bag is a demo-only, client-side cart — adding never calls the agent.
    const [bagIds, setBagIds] = useState<Set<number>>(new Set());

    // The full transcript sent to the agent (includes the hidden opener).
    const historyRef = useRef<Turn[]>([]);
    const scrollRef = useRef<HTMLDivElement>(null);
    const greetedRef = useRef(false);

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
                text: 'Sorry — I had trouble connecting just now. Please try again in a moment.',
            });
        } finally {
            setIsTyping(false);
        }
    };

    // Kick off the greeting once on mount.
    useEffect(() => {
        if (greetedRef.current) {
            return;
        }

        greetedRef.current = true;
        void sendTurn(OPENER, { display: false });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        scrollRef.current?.scrollTo({
            top: scrollRef.current.scrollHeight,
            behavior: 'smooth',
        });
    }, [messages, isTyping]);

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        const text = input.trim();

        if (!text || isTyping) {
            return;
        }

        setInput('');
        void sendTurn(text);
    };

    // Toggle the product in the local bag. No network call — this is a demo
    // cart, so the feedback is purely UI (button state + bag count).
    const handleAddToBag = (product: Product) => {
        setBagIds((prev) => {
            const next = new Set(prev);

            if (next.has(product.id)) {
                next.delete(product.id);
            } else {
                next.add(product.id);
            }

            return next;
        });
    };

    const composer = (
        <Composer
            input={input}
            onChange={setInput}
            onSubmit={handleSubmit}
            disabled={isTyping}
        />
    );

    return (
        <>
            <Head title="Chat with Closr" />
            <div className="flex min-h-screen items-stretch justify-center bg-neutral-100 text-black sm:items-center sm:p-4 dark:bg-neutral-950 dark:text-white">
                <div className="flex h-screen w-full max-w-xl flex-col overflow-hidden border-black/10 bg-white shadow-2xl sm:h-[88vh] sm:max-h-[840px] sm:rounded-3xl sm:border dark:border-white/10 dark:bg-black">
                    <Header bagCount={bagIds.size} />

                    {!hasStarted ? (
                        // Landing — bold welcome with starter chips, composer pinned below.
                        <div className="flex flex-1 flex-col items-center justify-center px-6 text-center">
                            <div className="mb-5 flex size-14 items-center justify-center rounded-2xl bg-black text-white dark:bg-white dark:text-black">
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
                                {STARTERS.map((starter) => (
                                    <button
                                        key={starter}
                                        type="button"
                                        disabled={isTyping}
                                        onClick={() => void sendTurn(starter)}
                                        className="rounded-full border border-black/15 px-4 py-2 text-sm font-medium text-black transition hover:bg-black/5 disabled:opacity-50 dark:border-white/20 dark:text-white dark:hover:bg-white/10"
                                    >
                                        {starter}
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
                                    onAddToBag={handleAddToBag}
                                />
                            ))}

                            {isTyping && <TypingIndicator />}
                        </div>
                    )}

                    <div className="border-t border-black/5 p-3 sm:p-4 dark:border-white/10">
                        {composer}
                    </div>
                </div>
            </div>
        </>
    );
}

function Header({ bagCount }: { bagCount: number }) {
    return (
        <header className="flex items-center justify-between border-b border-black/5 px-5 py-3.5 dark:border-white/10">
            <div className="flex items-center gap-2.5">
                <div className="flex size-9 items-center justify-center rounded-full bg-black text-white dark:bg-white dark:text-black">
                    <Sparkles className="size-4.5" />
                </div>
                <div className="leading-tight">
                    <p className="text-sm font-bold">Closr</p>
                    <p className="text-xs text-black/45 dark:text-white/45">
                        Shopping assistant
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
}: {
    input: string;
    onChange: (value: string) => void;
    onSubmit: (event: React.FormEvent) => void;
    disabled: boolean;
}) {
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
                className="flex size-9 shrink-0 items-center justify-center rounded-full bg-black text-white transition hover:bg-black/80 disabled:opacity-30 dark:bg-white dark:text-black dark:hover:bg-white/80"
            >
                <ArrowUp className="size-5" />
            </button>
        </form>
    );
}

function MessageBubble({
    message,
    bagIds,
    onAddToBag,
}: {
    message: Message;
    bagIds: Set<number>;
    onAddToBag: (product: Product) => void;
}) {
    const isAssistant = message.role === 'assistant';
    const isConfirmation =
        message.kind === 'text' &&
        (message.step === 'checkout' || message.step === 'done');

    if (isConfirmation) {
        return (
            <div className="flex justify-start">
                <div className="w-[85%] rounded-2xl rounded-tl-sm border border-black/15 bg-black/5 p-4 dark:border-white/20 dark:bg-white/10">
                    <div className="mb-1.5 flex items-center gap-2 text-sm font-semibold">
                        <ShoppingBag className="size-4" />
                        Added to your bag
                    </div>
                    <p className="text-sm leading-relaxed">{message.text}</p>
                </div>
            </div>
        );
    }

    // Product recommendations break out of the bubble width so the slider has
    // room to breathe: the intro line sits in a bubble, the cards span below.
    if (message.kind === 'products') {
        return (
            <div className="flex w-full flex-col gap-3">
                <div className="flex justify-start">
                    <div className="max-w-[85%] rounded-2xl rounded-tl-sm bg-black/5 px-4 py-2.5 text-sm leading-relaxed text-black dark:bg-white/10 dark:text-white">
                        {message.text}
                    </div>
                </div>

                <ProductSlider
                    products={message.products}
                    bagIds={bagIds}
                    onAddToBag={onAddToBag}
                />
            </div>
        );
    }

    return (
        <div
            className={cn(
                'flex w-full',
                isAssistant ? 'justify-start' : 'justify-end',
            )}
        >
            <div
                className={cn(
                    'rounded-2xl px-4 py-2.5 text-sm leading-relaxed',
                    isAssistant
                        ? 'max-w-[85%] rounded-tl-sm bg-black/5 text-black dark:bg-white/10 dark:text-white'
                        : 'max-w-[85%] rounded-tr-sm bg-black text-white dark:bg-white dark:text-black',
                )}
            >
                {message.text}
            </div>
        </div>
    );
}

function ProductSlider({
    products,
    bagIds,
    onAddToBag,
}: {
    products: Product[];
    bagIds: Set<number>;
    onAddToBag: (product: Product) => void;
}) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const updateScrollState = () => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        const { scrollLeft, scrollWidth, clientWidth } = track;
        setCanScrollLeft(scrollLeft > 1);
        setCanScrollRight(scrollLeft + clientWidth < scrollWidth - 1);
    };

    useEffect(() => {
        updateScrollState();
    }, [products]);

    const scrollByCards = (direction: 1 | -1) => {
        const track = trackRef.current;

        if (!track) {
            return;
        }

        track.scrollBy({
            left: direction * track.clientWidth * 0.8,
            behavior: 'smooth',
        });
    };

    const showControls = products.length > 1;

    return (
        <div className="flex flex-col gap-2">
            {showControls && (
                <div className="flex items-center justify-between px-1">
                    <span className="flex items-center gap-1.5 text-xs font-medium text-black/50 dark:text-white/50">
                        <Sparkles className="size-3.5" />
                        {products.length} picks for you
                    </span>
                    <div className="flex gap-1.5">
                        <SliderArrow
                            direction="left"
                            disabled={!canScrollLeft}
                            onClick={() => scrollByCards(-1)}
                        />
                        <SliderArrow
                            direction="right"
                            disabled={!canScrollRight}
                            onClick={() => scrollByCards(1)}
                        />
                    </div>
                </div>
            )}

            <div
                ref={trackRef}
                onScroll={updateScrollState}
                className="-mx-4 flex snap-x snap-mandatory scroll-px-4 [scrollbar-width:none] gap-3 overflow-x-auto px-4 pb-1 [&::-webkit-scrollbar]:hidden"
            >
                {products.map((product) => (
                    <ProductCard
                        key={product.id}
                        product={product}
                        added={bagIds.has(product.id)}
                        onAddToBag={onAddToBag}
                    />
                ))}
            </div>
        </div>
    );
}

function SliderArrow({
    direction,
    disabled,
    onClick,
}: {
    direction: 'left' | 'right';
    disabled: boolean;
    onClick: () => void;
}) {
    const Icon = direction === 'left' ? ChevronLeft : ChevronRight;

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={
                direction === 'left' ? 'Previous products' : 'Next products'
            }
            className="flex size-7 items-center justify-center rounded-full border border-black/10 bg-white text-black transition hover:bg-black/5 disabled:cursor-not-allowed disabled:opacity-30 dark:border-white/15 dark:bg-[#0a0a0a] dark:text-white dark:hover:bg-white/10"
        >
            <Icon className="size-4" />
        </button>
    );
}

function ProductCard({
    product,
    added,
    onAddToBag,
}: {
    product: Product;
    added: boolean;
    onAddToBag: (product: Product) => void;
}) {
    return (
        <div
            className={cn(
                'group flex w-44 shrink-0 snap-start flex-col overflow-hidden rounded-2xl border bg-white transition hover:shadow-md sm:w-48 dark:bg-[#0a0a0a]',
                added
                    ? 'border-black/40 dark:border-white/45'
                    : 'border-black/10 hover:border-black/25 dark:border-white/15 dark:hover:border-white/30',
            )}
        >
            <div className="relative aspect-square overflow-hidden bg-black/[0.03] dark:bg-white/[0.04]">
                <img
                    src={product.thumbnail}
                    alt={product.title}
                    loading="lazy"
                    className="size-full object-contain p-3 transition duration-300 group-hover:scale-105"
                />
                <span className="absolute top-2 right-2 rounded-full bg-black/85 px-2 py-0.5 text-xs font-bold text-white backdrop-blur dark:bg-white/90 dark:text-black">
                    ${product.price.toFixed(2)}
                </span>
            </div>

            <div className="flex flex-1 flex-col gap-1.5 p-3">
                {product.category && (
                    <span className="text-[0.625rem] font-semibold tracking-wider text-black/40 uppercase dark:text-white/40">
                        {product.category}
                    </span>
                )}
                <p className="line-clamp-2 text-sm font-semibold text-black dark:text-white">
                    {product.title}
                </p>
                <p className="line-clamp-2 text-xs leading-relaxed text-black/55 italic dark:text-white/55">
                    “{product.reason}”
                </p>

                <button
                    type="button"
                    onClick={() => onAddToBag(product)}
                    aria-pressed={added}
                    className={cn(
                        'mt-auto flex w-full items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold transition',
                        added
                            ? 'bg-black/5 text-black ring-1 ring-black/15 ring-inset hover:bg-black/10 dark:bg-white/10 dark:text-white dark:ring-white/20 dark:hover:bg-white/15'
                            : 'bg-black text-white hover:bg-black/80 dark:bg-white dark:text-black dark:hover:bg-white/80',
                    )}
                >
                    {added ? (
                        <>
                            <Check className="size-4" />
                            Added
                        </>
                    ) : (
                        <>
                            <ShoppingBag className="size-4" />
                            Add to bag
                        </>
                    )}
                </button>
            </div>
        </div>
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
