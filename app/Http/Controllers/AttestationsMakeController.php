<?php

namespace App\Http\Controllers;

use App\Events\NotificationEvent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Attestation;
use App\Models\AttestationTasks;
use App\Models\UserCanAccessAdditionalAttestation;
use App\Models\UserHasCheckedTask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $attestations = AttestationController::createQuery($id)->get();

        AttestationController::checkIncludedUser(Attestation::query()->find($id));

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
        ], [
            'tasks.*.user_id.*' => 'The selected user is invalid or does not exist.',
            'tasks.*.task_id.*' => 'The selected task is invalid or does not exist.',
        ]);

        DB::beginTransaction();

        foreach ($request->input('tasks') as $task) {
            if (UserHasCheckedTask::query()->where('user_id', '=', $task['user_id'])
                    ->where('task_id', '=', $task['task_id'])->first()->checked == $task['checked'])
                continue;

            $privileges = HandleInertiaRequests::get_privileges();
            $canRevokeAttestation = Auth::user()->admin;
            foreach ($privileges as $privilege)
            {
                if ($privilege['privilege'] === 'can_revoke_attestation' && $privilege['checked'])
                    $canRevokeAttestation = true;
            }

            if (!$canRevokeAttestation && !$task['checked']) {
                DB::rollBack();
                abort(403,"Forbidden");
            }


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

        DB::commit();
    }
}
