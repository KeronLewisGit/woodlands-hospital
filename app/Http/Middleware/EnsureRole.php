<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** role:front_desk,lab → the signed-in staff member must hold one of those roles. Admins always pass. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user('web');
        $allowed = array_map(fn (string $r) => Role::from($r), $roles);

        abort_unless($user instanceof User && $user->hasRole(...$allowed), 403, 'Your staff role does not include this section.');

        return $next($request);
    }
}
