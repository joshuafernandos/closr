<?php

namespace App\Http\Responses\Concerns;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentBusiness
{
    protected function redirectPathForCurrentBusiness(Request $request, string $redirect): string
    {
        $business = $this->currentBusiness($request);

        URL::defaults(['current_business' => $business->slug]);

        return "/{$business->slug}{$redirect}";
    }

    protected function currentBusiness(Request $request): Business
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $business = $user->currentBusiness ?? $user->personalBusiness();

        abort_if(! $business, 403);

        return $business;
    }
}
