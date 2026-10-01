<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $courseActive = Course::factory()->create(['title' => 'Curso Ativo']);
        Activity::factory()->count(3)->for($courseActive)->create();              // obrigatórias
        Activity::factory()->count(2)->optional()->for($courseActive)->create();  // opcionais

        $courseInactive = Course::factory()->inactive()->create(['title' => 'Curso Inativo']);

        $courseOther = Course::factory()->create(['title' => 'Outro Curso']);
        Activity::factory()->for($courseOther)->create();                         // atividade de outro curso

        $userActive = User::factory()->create(['name' => 'Aluno Ativo']);
        $userEnrolled = User::factory()->create(['name' => 'Aluno Já Matriculado']);
        $userInactive = User::factory()->inactive()->create(['name' => 'Aluno Inativo']);

        Enrollment::factory()->for($userEnrolled)->for($courseActive)->create();
    }
}
