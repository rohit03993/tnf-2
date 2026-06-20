<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectSubscriberFromAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === UserRole::Subscriber) {
            return redirect()
                ->route('account')
                ->with('status', 'Editorial staff use the admin panel. Members can manage their account here.');
        }

        return $next($request);
    }
}
