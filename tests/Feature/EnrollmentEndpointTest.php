<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('retorna 201 e cria a matrícula', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $response = $this->postJson("/api/courses/{$course->id}/enrollments", [
        'user_id' => $user->id,
    ]);

    $response->assertCreated()
        ->assertJson([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'completed_at' => null,
        ]);

    expect(Enrollment::count())->toBe(1);
});

test('retorna 409 quando o usuário já está matriculado', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->postJson("/api/courses/{$course->id}/enrollments", ['user_id' => $user->id])
        ->assertStatus(409);

    expect(Enrollment::count())->toBe(1);
});

test('retorna 422 quando o usuário está inativo', function () {
    $user = User::factory()->inactive()->create();
    $course = Course::factory()->create();

    $this->postJson("/api/courses/{$course->id}/enrollments", ['user_id' => $user->id])
        ->assertStatus(422);
});

test('retorna 422 quando o curso está inativo', function () {
    $user = User::factory()->create();
    $course = Course::factory()->inactive()->create();

    $this->postJson("/api/courses/{$course->id}/enrollments", ['user_id' => $user->id])
        ->assertStatus(422);
});

test('retorna 422 quando o user_id não é informado', function () {
    $course = Course::factory()->create();

    $this->postJson("/api/courses/{$course->id}/enrollments", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('user_id');
});

test('retorna 404 quando o curso não existe', function () {
    $user = User::factory()->create();

    $inexistente = Course::max('id') + 1;

    $this->postJson("/api/courses/{$inexistente}/enrollments", ['user_id' => $user->id])
        ->assertStatus(404);
});
