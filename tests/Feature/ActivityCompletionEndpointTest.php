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
    $this->enrollment = Enrollment::factory()->for($this->user)->for($this->course)->create();
});

test('retorna 200 e o progresso atualizado', function () {
    $this->postJson("/api/courses/{$this->course->id}/activities/{$this->required[0]->id}/completion", ['user_id' => $this->user->id])
        ->assertOk()
        ->assertJson([
            'percentage' => 50.0,
            'required_completed' => 1,
            'required_total' => 2,
            'completed_at' => null,
        ]);
});

test('retorna 200 sem duplicar quando a mesma atividade é concluída duas vezes', function () {
    $url = "/api/courses/{$this->course->id}/activities/{$this->required[0]->id}/completion";

    $this->postJson($url, ['user_id' => $this->user->id])->assertOk();
    $this->postJson($url, ['user_id' => $this->user->id])
        ->assertOk()
        ->assertJson(['required_completed' => 1]);

    expect($this->enrollment->completions()->count())->toBe(1);
});

test('retorna 100% e a data de conclusão ao concluir a última atividade obrigatória', function () {
    $this->postJson("/api/courses/{$this->course->id}/activities/{$this->required[0]->id}/completion", ['user_id' => $this->user->id]);

    $response = $this->postJson("/api/courses/{$this->course->id}/activities/{$this->required[1]->id}/completion", ['user_id' => $this->user->id]);

    $response->assertOk()->assertJson(['percentage' => 100.0]);
    expect($response->json('completed_at'))->not->toBeNull();
});

test('retorna 404 quando o usuário não está matriculado no curso', function () {
    $outro = User::factory()->create();

    $this->postJson("/api/courses/{$this->course->id}/activities/{$this->required[0]->id}/completion", ['user_id' => $outro->id])
        ->assertStatus(404);
});

test('retorna 422 quando a atividade é de outro curso', function () {
    $outraAtividade = Activity::factory()->create();

    $this->postJson("/api/courses/{$this->course->id}/activities/{$outraAtividade->id}/completion", ['user_id' => $this->user->id])
        ->assertStatus(422);
});

test('retorna 404 quando a atividade não existe', function () {
    $inexistente = Activity::max('id') + 1;

    $this->postJson("/api/courses/{$this->course->id}/activities/{$inexistente}/completion", ['user_id' => $this->user->id])
        ->assertStatus(404);
});

test('retorna 422 quando o user_id não é informado', function () {
    $this->postJson("/api/courses/{$this->course->id}/activities/{$this->required[0]->id}/completion", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('user_id');
});
