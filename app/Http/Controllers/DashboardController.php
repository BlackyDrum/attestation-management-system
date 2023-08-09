<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Models\Semester;
use App\Models\User;
use App\Rules\NoPipeCharacter;
use App\Rules\ValidSeverity;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Dashboard', [
            'users' => Auth::user()->admin ? User::all() : [],
            'semester' => Semester::all(),
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'index' => 'required|integer',
            'clearAll' => 'required|boolean',
        ]);

        $id = Auth::id();

        if ($request->input('clearAll')) {
            Redis::command('DEL', ["users:{$id}:notifications"]);
            return response("Notifications removed");
        }

        $message = Redis::command('LINDEX', ["users:{$id}:notifications", $request->input('index')]);
        Redis::command('LREM', ["users:{$id}:notifications", 1, $message]);

        return response("Notification removed");
    }

    public function send(Request $request)
    {
        $request->validate([
            'users' => 'required|array|min:1',
            'users.*.id' => 'required|exists:users,id',
            'message' => ['required', 'string', 'max:500', new NoPipeCharacter],
            'severity' => ['required', new ValidSeverity],
        ],[
            'users.*.id.exists' => "The selected user is invalid or does not exist"
        ]);

        foreach ($request->input('users') as $user) {
            event(new NotificationEvent($user['id']));

            Redis::command('LPUSH', ["users:{$user['id']}:notifications", "{$request->input('severity')}|{$request->input('message')}|" . date('Y-m-d') . ' ' . date('h:i:sa')]);
        }

        return to_route('dashboard');
    }
}
