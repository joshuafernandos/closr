# Closr

An AI shopping assistant embedded into ecommerce stores.
A shopper opens the widget, has a conversation, and walks away with the right product.
No browsing. No filters. No abandoned carts.

---

## The flow

A shopper lands on a store. The Closr widget appears in the corner.
They click it. The AI greets them and asks what they're looking for.

From there the conversation follows four steps — always in order, never skipped:

**1. Greet**
The AI welcomes the shopper and opens with a single question — what brings you in?
The shopper can type freely or tap a quick-reply chip.

**2. Qualify**
The AI asks up to two targeted follow-up questions to understand exactly what the
shopper needs. Use case, budget, constraints — whatever narrows it down.
Never three questions. Never vague questions. Fast and purposeful.

**3. Recommend**
Based on what the shopper said, the AI surfaces one or two products.
Not a list of ten. One or two, with a reason why each fits.
If the shopper pushes back ("too expensive", "wrong colour"), the AI handles it
and offers an alternative — still within the same step.

**4. Checkout**
The shopper confirms the product and variant in chat.
The AI creates the order and shows a confirmation. Done.
The whole thing happens without leaving the chat window.

---

## Key components

**Widget**
The chat interface that lives on the merchant's storefront.
A floating button that expands into a conversation panel.
Built to eventually be embedded on any site via a single script tag.

**AI Conversation Engine**
Decides what the AI says at each step.
Knows which step the conversation is on, what the shopper has said,
and which products are relevant. Calls the LLM with tight context — not the
whole catalogue, just the few products that match what the shopper described.

**Product Catalogue**
Every merchant's products stored and indexed so the AI can search them
by meaning, not just keywords. "Something warm for winter hiking" finds
the right jacket even if the word "hiking" never appears in the product name.

**Scraper**
How we get the merchant's products. They give us their store URL.
We crawl it, pull out product data, and index it. Runs automatically on
signup and refreshes nightly. Merchant does nothing.

**Dashboard**
Where merchants go to see what's happening — conversations, products indexed,
outcomes. Minimal for now. Enough to make a pilot feel like a real product.

---

## Domain concepts

**Merchant** — the store owner using Closr. Has a catalogue, a widget, a plan.

**Shopper** — the end user having the conversation. Anonymous until checkout.

**Conversation** — one full session between a shopper and the AI.
Has a step (1–4), a history of messages, and an outcome (completed or dropped).

**Message** — a single turn in the conversation. Either from the shopper or the AI.

**Product** — an item from the merchant's catalogue. Has a name, price, description,
variants (size, colour etc), stock status, and a URL back to the real store page.

**Order** — the outcome of a completed conversation. Records which product,
which variant, and links back to the conversation that created it.

**Step** — where the conversation currently is in the funnel (1 greet → 4 checkout).
The AI engine uses this to decide how to respond next.