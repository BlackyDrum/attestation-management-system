<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->admin)
        {
            return $next($request);
        }

        $privileges = HandleInertiaRequests::get_privileges();
        $route = $request->route()->getName();
        switch ($route) {
            case 'send_notification':
                foreach ($privileges as $privilege)
                {
                    if ($privilege['privilege'] === 'can_send_notification' && $privilege['checked'])
                        return $next($request);
                }
                break;
            case 'user':
                foreach ($privileges as $privilege)
                {
                    if ($privilege['privilege'] === 'can_access_user_page' && $privilege['checked'])
                        return $next($request);
                }
                break;
            case 'delete_user':
                foreach ($privileges as $privilege)
                {
                    if ($privilege['privilege'] === 'can_delete_user' && $privilege['checked'])
                        return $next($request);
                }
                break;
            case 'edit_user':
                foreach ($privileges as $privilege)
                {
                    if ($privilege['privilege'] === 'can_edit_user' && $privilege['checked'])
                        return $next($request);
                }
                break;
            case 'create_user':
            case 'create_user_upload':
                foreach ($privileges as $privilege)
                {
                    if ($privilege['privilege'] === 'can_create_user' && $privilege['checked'])
                        return $next($request);
                }
        }

        abort(403,"Forbidden");
    }
}
