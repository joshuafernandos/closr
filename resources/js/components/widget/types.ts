export type Step =
    | 'greet'
    | 'qualify'
    | 'recommend'
    | 'checkout'
    | 'done'
    | 'out_of_scope';

/** A selectable product attribute (e.g. Size) and its available options. */
export type Variation = { name: string; options: string[] };

export type Product = {
    id: number;
    title: string;
    price: number;
    thumbnail: string;
    category?: string;
    // The agent's reason this product fits the shopper. Absent when the shopper
    // browsed a category directly (no agent involved), so the card hides it.
    reason?: string;
    // When the store has a real storefront (e.g. WooCommerce), the URL that
    // adds this product to the cart and opens the cart page with it already in
    // it. Null for the demo catalogue, where the bag is local-only.
    cartUrl?: string | null;
    // The product's storefront page, opened by "View details". Null for demo
    // origins without a real storefront.
    url?: string | null;
    // The product's selectable attributes (size, colour, …). Empty/absent for
    // simple products, which add to the cart as-is.
    variations?: Variation[];
};

export type ChatResponse = {
    reply: string;
    step: Step;
    products: Product[];
};

export type Message =
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
      }
    | {
          id: number;
          role: 'assistant';
          kind: 'variation';
          product: Product;
          // Locked once the shopper has confirmed their choice, so the picker
          // can't be re-submitted.
          resolved: boolean;
      };

export type Turn = { role: 'user' | 'assistant'; content: string };

/** A stored turn returned when restoring a prior conversation. */
export type StoredMessage = {
    role: 'user' | 'assistant';
    content: string;
    step?: Step | null;
    products: Product[];
};
