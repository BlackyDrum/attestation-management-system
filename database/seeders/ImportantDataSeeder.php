<?php

namespace Database\Seeders;

use App\Models\Privileges;
use App\Models\Role;
use App\Models\RoleHasPrivilege;
use App\Models\Semester;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImportantDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // NOTE: IT IS IMPORTANT TO HAVE A 'student' ROLE IN THIS ARRAY
        // THIS ROLE HAS THE LOWEST PRIVILEGES WITHIN THE APPLICATION
        $roles = ['Scientific Assistant', 'Student', 'Tutor', 'Professor'];

        foreach ($roles as $role) {
            Role::query()->create([
                'role' => $role,
            ]);
        }

        $date = date("Y");

        $semesters = ["Summersemester {$date}", "Wintersemester {$date}"];

        foreach ($semesters as $semester) {
            Semester::query()->create([
                'semester' => $semester
            ]);
        }


        // Roles and privileges
        $roles = Role::all();
        $privileges = [
            "can_send_notification",
            "can_create_subject",
            "can_edit_subject",
            "can_delete_subject",
            "can_upload_file",
            "can_make_attestation",
            "can_edit_user",
            "can_edit_role",
        ];

        foreach ($privileges as $privilege) {
            $p = Privileges::query()->create([
                'privilege' => $privilege
            ]);

            foreach ($roles as $role) {
                RoleHasPrivilege::query()->create([
                    'role_id' => $role->id,
                    'privilege_id' => $p->id,
                ]);
            }
        }

    }
}
