<?php

namespace App\Http\Controllers;

use App\Http\Responses\Concerns\RedirectsToCurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use RedirectsToCurrentBusiness;

    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        return redirect($this->redirectPathForCurrentBusiness($request, '/dashboard'));
    }
}
