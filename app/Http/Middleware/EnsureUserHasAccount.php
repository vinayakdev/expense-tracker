<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->accounts()->doesntExist()) {
            return redirect()->route('account.setup');
        }

        return $next($request);
    }
}
