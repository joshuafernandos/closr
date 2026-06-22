<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentBusiness;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use RedirectsToCurrentBusiness;

    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($this->redirectPathForCurrentBusiness($request, Fortify::redirects('login')));
    }
}
