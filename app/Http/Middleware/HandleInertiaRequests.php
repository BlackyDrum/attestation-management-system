<?php

namespace App\Http\Middleware;

use App\Models\RoleHasPrivilege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $notifications = Auth::check() ?  Redis::command('LRANGE', ["users:{$request->user()->id}:notifications", 0, -1]): [];

        $privileges = self::get_privileges();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'notifications' => $notifications,
                'privileges' => $privileges,
            ],
        ]);
    }

    public static function get_privileges()
    {
        return Auth::check() ? RoleHasPrivilege::query()
            ->join('privileges', 'privileges.id', '=', 'role_has_privilege.privilege_id')
            ->join('roles', 'roles.id', '=', 'role_has_privilege.role_id')
            ->where('roles.id', '=', Auth::user()->role_id)
            ->where('role_has_privilege.checked', '=', 'true')
            ->select([
                'privileges.privilege',
                'roles.role',
                'role_has_privilege.checked'
            ])
            ->get() : [];
    }
}
