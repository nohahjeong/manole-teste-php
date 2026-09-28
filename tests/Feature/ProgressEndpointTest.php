<?php

use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->course = Course::factory()->create();
    $this->required = Activity::factory()->count(2)->for($this->course)->create();
    Activity::factory()->optional()->for($this->course)->create();
    $this->enrollment = Enrollment::factory()->for($this->user)->for($this->course)->create();
});

test('retorna 200 com o progresso da matrícula', function () {
    $this->getJson("/api/users/{$this->user->id}/courses/{$this->course->id}/progress")
        ->assertOk()
        ->assertJson([
            'percentage' => 0.0,
            'required_completed' => 0,
            'required_total' => 2,
            'completed_at' => null,
        ]);
});

test('retorna o percentual parcial depois de concluir uma atividade obrigatória', function () {
    $this->postJson("/api/courses/{$this->course->id}/activities/{$this->required[0]->id}/completion", ['user_id' => $this->user->id]);

    $this->getJson("/api/users/{$this->user->id}/courses/{$this->course->id}/progress")
        ->assertOk()
        ->assertJson(['percentage' => 50.0, 'required_completed' => 1]);
});

test('retorna 100% e a data de conclusão quando o curso está concluído', function () {
    foreach ($this->required as $activity) {
        $this->postJson("/api/courses/{$this->course->id}/activities/{$activity->id}/completion", ['user_id' => $this->user->id]);
    }

    $response = $this->getJson("/api/users/{$this->user->id}/courses/{$this->course->id}/progress")
        ->assertOk()
        ->assertJson(['percentage' => 100.0, 'required_completed' => 2]);

    expect($response->json('completed_at'))->not->toBeNull();
});

test('retorna 404 quando o usuário não está matriculado no curso', function () {
    $outro = User::factory()->create();

    $this->getJson("/api/users/{$outro->id}/courses/{$this->course->id}/progress")
        ->assertStatus(404);
});

test('retorna 404 quando o usuário não existe', function () {
    $inexistente = User::max('id') + 1;

    $this->getJson("/api/users/{$inexistente}/courses/{$this->course->id}/progress")
        ->assertStatus(404);
});

test('retorna 404 quando o curso não existe', function () {
    $inexistente = Course::max('id') + 1;

    $this->getJson("/api/users/{$this->user->id}/courses/{$inexistente}/progress")
        ->assertStatus(404);
});
