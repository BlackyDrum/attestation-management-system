<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Semester;
use App\Models\ToDoList;
use App\Models\User;
use App\Rules\NoPipeCharacter;
use App\Rules\ValidateToDoCreator;
use App\Rules\ValidSeverity;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Predis\Command\Argument\Server\To;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $data = AttestationController::createQuery()
            ->where('users.id', '=', Auth::id())
            ->get();

        $semester = Semester::query()
            ->where('id', '=', User::query()->find(Auth::id())->dashboard_semester)
            ->first();

        $todos = ToDoList::query()
            ->where('creator_id', '=', Auth::id())
            ->orderBy('checked')
            ->orderBy('id')
            ->get();

        $privileges = HandleInertiaRequests::get_privileges();
        $canSeeUsers = false;
        foreach ($privileges as $privilege)
        {
            if ($privilege['privilege'] === 'can_send_notification') {
                $canSeeUsers = true;
                break;
            }
        }

        return Inertia::render('Dashboard', [
            'users' => Auth::user()->admin || $canSeeUsers ? User::all() : [],
            'semester' => Semester::query()->orderBy('id', 'DESC')->limit(5)->get(),
            'data' => $data,
            'selected_semester' => $semester,
            'todos' => $todos,
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

        return redirect()->back();
    }

    public function update_semester(Request $request)
    {
        $request->validate([
            'semester' => 'required|integer|exists:semester,id',
        ], [
            'semester.*' => 'The selected semester in invalid or does not exist.'
        ]);

        User::query()->find(Auth::id())->fill([
            'dashboard_semester' => $request->input('semester')
        ])->save();
    }

    public function create_to_do(Request $request)
    {
        $request->validate([
            'task' => 'required|string|max:255'
        ]);

        $maxToDo = 20;
        $todos = ToDoList::query()->where('creator_id', '=', Auth::id())->get();

        if (count($todos) >= $maxToDo) {
            return \response(['message' => "You cannot have more than {$maxToDo} tasks."],422);
        }

        $item = ToDoList::query()->create([
            'task' => $request->input('task'),
            'creator_id' => Auth::id(),
        ]);

        return \response($item);
    }

    public function check_to_do(Request $request)
    {
        $request->validate([
            'id' => ['bail','required', 'integer', 'exists:todos,id', new ValidateToDoCreator],
            'checked' => ['required', 'boolean'],
        ]);

        ToDoList::query()->find($request->input('id'))->update([
            'checked' => $request->input('checked'),
        ]);
    }

    public function delete_to_do(Request $request)
    {
        $request->validate([
            'id' => ['bail','required', 'integer', 'exists:todos,id', new ValidateToDoCreator],
        ]);

        ToDoList::query()->find($request->input('id'))->delete();
    }
}
