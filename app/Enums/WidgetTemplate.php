<?php

namespace App\Enums;

/**
 * How a widget renders on the merchant's storefront: a floating launcher card
 * (the classic chat bubble) or a wide, inline panel embedded in the page.
 */
enum WidgetTemplate: string
{
    case Chat = 'chat';
    case Component = 'component';

    /**
     * Get the display label for the template.
     */
    public function label(): string
    {
        return match ($this) {
            self::Chat => 'Chat widget',
            self::Component => 'Inline component',
        };
    }
}
