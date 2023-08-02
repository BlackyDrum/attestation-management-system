<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Models\Attestation;
use App\Models\AttestationTasks;
use App\Models\UserHasCheckedTask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class AttestationsMakeController extends Controller
{
    public function show(Request $request, int $id)
    {
        try {
            Attestation::query()->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            abort(404);
        }

        $order = config('database.default') === 'pgsql' ?
            "SPLIT_PART(users.name,' ', -1)" :
            "SUBSTRING_INDEX(users.name, ' ', -1)";

        $attestations = Attestation::query()
            ->where('attestation.id', '=', $id)
            ->join('semester', 'attestation.semester_id', '=', 'semester.id')
            ->join('attestation_tasks', 'attestation.id', '=', 'attestation_tasks.attestation_id')
            ->join('user_has_checked_task', 'user_has_checked_task.task_id', '=', 'attestation_tasks.id')
            ->join('user_has_attestation', function (JoinClause $join) {
                $join->on('user_has_attestation.user_id', '=', 'user_has_checked_task.user_id')
                    ->on('user_has_attestation.attestation_id', '=', 'attestation.id');
            })
            ->join('users', 'users.id', '=', 'user_has_attestation.user_id')
            ->leftJoin('users AS editor', 'editor.id', '=', 'user_has_checked_task.editor_id')
            ->orderByRaw($order)
            ->select([
                'attestation.id',
                'attestation.subject_name',
                'attestation.subject_number',
                'attestation.creator_id',
                'semester.semester',
                'attestation_tasks.title',
                'attestation_tasks.description',
                'user_has_attestation.user_id',
                'users.name',
                'users.matriculation_number',
                'user_has_checked_task.checked',
                'attestation_tasks.id AS task_id',
                'user_has_checked_task.id AS checked_id',
                'user_has_checked_task.editor_id',
                'editor.name AS editor_name',
                'user_has_checked_task.updated_at',
            ])
            ->orderBy('attestation_tasks.id')
            ->get();

        return Inertia::render('AttestationsMake', [
            'attestations' => $attestations,
            'id' => $id,
        ]);
    }

    public function make(Request $request)
    {
        $request->validate([
            'tasks' => 'required|array|min:1',
            'tasks.*.user_id' => 'required|integer|exists:users,id',
            'tasks.*.checked' => 'required|boolean',
            'tasks.*.task_id' => 'required|integer|exists:attestation_tasks,id'
        ]);

        foreach ($request->input('tasks') as $task) {
            if (UserHasCheckedTask::query()->where('user_id', '=', $task['user_id'])
                    ->where('task_id', '=', $task['task_id'])->first()->checked === $task['checked'])
                continue;

            UserHasCheckedTask::query()->where('user_id', '=', $task['user_id'])
                ->where('task_id', '=', $task['task_id'])->update([
                    'checked' => $task['checked'],
                    'editor_id' => Auth::id(),
                ]);

            $attestation = AttestationTasks::query()->where('attestation_tasks.id', '=', $task['task_id'])
                ->join('attestation', 'attestation.id', '=', 'attestation_tasks.attestation_id')
                ->join('semester', 'semester.id', '=', 'attestation.semester_id')
                ->select([
                    'attestation_tasks.title',
                    'attestation.subject_name',
                    'attestation.subject_number',
                    'semester.semester'
                ])
                ->first();

            $status = $task['checked'] ? "SUCCESS|You received the attestation '{$attestation->title}' for the subject '{$attestation->subject_name}'({$attestation->subject_number}) for the {$attestation->semester}.|" :
                "WARN|Your attestation '{$attestation->title}' for the subject '{$attestation->subject_name}'({$attestation->subject_number}) for the {$attestation->semester} has been revoked.|";

            event(new NotificationEvent($task['user_id']));

            Redis::command('LPUSH',
                ["users:{$task['user_id']}:notifications", $status . date('Y-m-d') . ' ' . date('h:i:sa')]);

        }
    }
}
