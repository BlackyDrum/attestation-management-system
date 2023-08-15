<?php

namespace Database\Seeders;

use App\Models\Role;
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
        // NOTE: IT IS IMPORTANT TO HAVE A 'user' ROLE IN THIS ARRAY
        // THIS ROLE HAS THE LOWEST PRIVILEGES WITHIN THE APPLICATION
        $roles = ['User', 'Admin', 'Student', 'Tutor', 'Professor'];

        foreach ($roles as $role) {
            Role::query()->create([
                'role' => $role,
            ]);
        }

        Semester::query()->create([
            'semester' => "Summersemester " . date("Y")
        ]);
    }
}
