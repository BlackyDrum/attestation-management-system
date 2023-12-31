<?php

namespace Database\Seeders;

use App\Models\Attestation;
use App\Models\AttestationTasks;
use App\Models\Semester;
use App\Models\User;
use App\Models\UserHasAttestation;
use App\Models\UserHasCheckedTask;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory(100)->create();

        $date = date("Y");

        $semesters = ["Summersemester {$date}", "Wintersemester {$date}"];

        foreach ($semesters as $semester) {
            Semester::query()->create([
                'semester' => $semester
            ]);
        }

        $semesters = Semester::all();
        $users = User::all();

        Attestation::query()->create([
            'subject_number' => 25106,
            'subject_name' => 'Data Structures and Algorithms',
            'acronym' => 'DSA',
            'semester_id' => $semesters[0]->id,
            'creator_id' => $users[0]->id,
        ]);

        Attestation::query()->create([
            'subject_number' => 25107,
            'subject_name' => 'Data Communication',
            'acronym' => 'DC',
            'semester_id' => $semesters[0]->id,
            'creator_id' => $users[0]->id,
        ]);

        Attestation::query()->create([
            'subject_number' => 25108,
            'subject_name' => 'Introduction to Computer Engineering',
            'acronym' => 'ICE',
            'semester_id' => $semesters[0]->id,
            'creator_id' => $users[0]->id,
        ]);

        Attestation::query()->create([
            'subject_number' => 25109,
            'subject_name' => 'Computability and Complexity',
            'acronym' => 'CC',
            'semester_id' => $semesters[1]->id,
            'creator_id' => $users[0]->id,
        ]);

        Attestation::query()->create([
            'subject_number' => 25110,
            'subject_name' => 'Data Bases and Information Systems',
            'acronym' => 'DBIS',
            'semester_id' => $semesters[1]->id,
            'creator_id' => $users[0]->id,
        ]);

        $subjects = Attestation::all();
        $users = User::all();

        $taskTitles = ["T1", "T2", "T3", "T4", "T5"];

        foreach ($subjects as $subject) {
            foreach ($taskTitles as $title) {
                AttestationTasks::query()->create([
                    'attestation_id' => $subject->id,
                    'title' => $title,
                    'description' => null,
                ]);
            }

            foreach ($users as $user) {
                UserHasAttestation::query()->create([
                    'user_id' => $user->id,
                    'attestation_id' => $subject->id,
                ]);
            }
        }

        foreach ($subjects as $subject) {
            AttestationTasks::query()->create([
                'attestation_id' => $subject->id,
                'title' => env('FINAL_ATTESTATION_NAME'),
                'description' => null,
            ]);
        }

        $tasks = AttestationTasks::all();

        foreach ($tasks as $task) {
            foreach ($users as $user) {
                UserHasCheckedTask::query()->create([
                    'user_id' => $user->id,
                    'task_id' => $task->id,
                    'checked' => rand(1,10) < 3,
                    'editor_id' => null,
                ]);
            }
        }

    }
}
