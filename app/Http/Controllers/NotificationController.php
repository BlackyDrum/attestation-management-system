<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Dashboard');
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
}
