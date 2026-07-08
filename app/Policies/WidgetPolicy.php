<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Auth\Access\Response;

class WidgetPolicy
{
    /**
     * Determine whether the user can view their business's widgets.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the widget. Denied as "not found" so
     * a merchant can't probe for widgets outside their business.
     */
    public function view(User $user, Widget $widget): Response
    {
        return $user->belongsToBusiness($widget->business)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create widgets.
     */
    public function create(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can update the widget.
     */
    public function update(User $user, Widget $widget): Response
    {
        return $user->belongsToBusiness($widget->business)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the widget.
     */
    public function delete(User $user, Widget $widget): Response
    {
        return $user->belongsToBusiness($widget->business)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
