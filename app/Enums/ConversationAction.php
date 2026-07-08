<?php

namespace App\Enums;

/**
 * The most recent thing that happened in a shopper conversation, surfaced as a
 * status badge on the merchant's Messages dashboard.
 */
enum ConversationAction: string
{
    case Left = 'left';
    case AddedToCart = 'added_to_cart';
    case VisitedProductPage = 'visited_product_page';

    /**
     * Get the display label for the action.
     */
    public function label(): string
    {
        return match ($this) {
            self::Left => 'Left',
            self::AddedToCart => 'Added to cart',
            self::VisitedProductPage => 'Visited product page',
        };
    }
}
