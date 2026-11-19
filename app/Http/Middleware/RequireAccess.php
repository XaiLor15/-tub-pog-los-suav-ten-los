<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Route gate by access level: ->middleware('access:pending|member|admin').
 * Unauthorized visitors are sent to /unauthorized with the reason (guest|pending|role),
 * matching the design's three cases. Non-GET / JSON requests get a plain 401/403.
 */
class RequireAccess
{
    public function handle(Request $request, Closure $next, string $level)
    {
        $user = $request->user();

        $reason = match (true) {
            ! $user, ! $user->isActive() => 'guest',
            $level === 'pending' => null,
            $level === 'member' && $user->isPending() => 'pending',
            $level === 'member' => $user->isMember() ? null : 'role',
            $level === 'admin' => $user->isAdmin() ? null : ($user->isPending() ? 'pending' : 'role'),
            default => 'role',
        };

        if ($reason === null) {
            return $next($request);
        }

        if ($request->expectsJson() || ! $request->isMethod('get')) {
            abort($reason === 'guest' ? 401 : 403);
        }

        return redirect()->route('unauthorized', ['from' => '/'.ltrim($request->path(), '/'), 'reason' => $reason]);
    }
}
