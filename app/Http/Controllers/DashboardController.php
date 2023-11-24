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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $todos = ToDoList::query()
            ->where('creator_id', '=', Auth::id())
            ->orderBy('checked')
            ->orderBy('id')
            ->get();

        return Inertia::render('Dashboard', [
            'semester' => Semester::query()->orderBy('id', 'DESC')->limit(5)->get(),
            'todos' => $todos,
        ]);
    }

    public function get_users(Request $request)
    {
        $users = User::all();

        return response()->json($users);
    }

    public function get_data(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|integer|exists:semester,id'
        ]);

        $data = AttestationController::createQuery()
            ->where('users.id', '=', Auth::id())
            ->where('semester.id', '=', $request->input('semester_id'))
            ->get();

        return response()->json($data);
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
