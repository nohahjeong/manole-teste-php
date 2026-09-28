<?php

use App\Exceptions\DuplicateEnrollmentException;
use App\Exceptions\EnrollmentNotFoundException;
use App\Exceptions\InactiveCourseException;
use App\Exceptions\InactiveUserException;
use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(EnrollmentService::class);
});

test('matricula um usuário ativo em um curso ativo', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $enrollment = $this->service->enroll($user, $course);

    expect($enrollment->user_id)->toBe($user->id)
        ->and($enrollment->course_id)->toBe($course->id)
        ->and($enrollment->completed_at)->toBeNull();
});

test('não cria matrícula duplicada para o mesmo usuário e curso', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $this->service->enroll($user, $course);

    expect(fn () => $this->service->enroll($user, $course))
        ->toThrow(DuplicateEnrollmentException::class);

    expect($course->enrollments()->count())->toBe(1);
});

test('não matricula usuário inativo', function () {
    $user = User::factory()->inactive()->create();
    $course = Course::factory()->create();

    expect(fn () => $this->service->enroll($user, $course))
        ->toThrow(InactiveUserException::class);
});

test('não matricula em curso inativo', function () {
    $user = User::factory()->create();
    $course = Course::factory()->inactive()->create();

    expect(fn () => $this->service->enroll($user, $course))
        ->toThrow(InactiveCourseException::class);
});

test('encontra a matrícula do usuário no curso', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $enrollment = $this->service->enroll($user, $course);

    expect($this->service->findFor($user, $course)->id)->toBe($enrollment->id);
});

test('não encontra matrícula quando o usuário não está matriculado no curso', function () {
    expect(fn () => $this->service->findFor(User::factory()->create(), Course::factory()->create()))
        ->toThrow(EnrollmentNotFoundException::class);
});
