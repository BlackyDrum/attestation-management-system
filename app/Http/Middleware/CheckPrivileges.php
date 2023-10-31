<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPrivileges
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->admin)
            return $next($request);

        $privileges = HandleInertiaRequests::get_privileges();
        $route = $request->route()->getName();

        $privilegeMap = [
            'send_notification' => 'can_send_notification',
            'user' => 'can_access_user_page',
            'delete_user' => 'can_delete_user',
            'edit_user' => 'can_edit_user',
            'create_user' => 'can_create_user',
            'create_user_upload' => 'can_create_user',
            'roles' => 'can_access_role_page',
            'edit_roles' => 'can_edit_role',
            'attestations' => 'can_access_subject_page',
            'show_make_attestation' => 'can_access_attestation_page',
            'make_attestation' => 'can_make_attestation',
            'create_subject' => 'can_create_subject',
            'edit_subject' => 'can_edit_subject',
            'upload_user_subject' => 'can_edit_subject',
            'delete_subject' => 'can_delete_subject',
            'create_to_do' => 'can_create_todo',
            'update_comment' => 'can_update_comments',
        ];

        if (array_key_exists($route, $privilegeMap)) {
            $requiredPrivilege = $privilegeMap[$route];

            foreach ($privileges as $privilege) {
                if ($privilege['privilege'] === $requiredPrivilege && $privilege['checked']) {
                    return $next($request);
                }
            }
        }

        abort(403, "Forbidden");
    }
}
