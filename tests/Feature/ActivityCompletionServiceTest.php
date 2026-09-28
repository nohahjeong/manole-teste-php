<?php

use App\Exceptions\ActivityNotInCourseException;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\ActivityCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ActivityCompletionService::class);
    $this->course = Course::factory()->create();
    $this->required = Activity::factory()->count(2)->for($this->course)->create();
    $this->optional = Activity::factory()->optional()->for($this->course)->create();
    $this->enrollment = Enrollment::factory()->for($this->course)->create();
});

test('registra a conclusão de uma atividade', function () {
    $completion = $this->service->complete($this->enrollment, $this->required[0]);

    expect($completion->exists)->toBeTrue()
        ->and($completion->completed_at)->not->toBeNull()
        ->and($this->enrollment->completions()->count())->toBe(1);
});

test('concluir a mesma atividade duas vezes não duplica nem altera a data', function () {
    $first = $this->service->complete($this->enrollment, $this->required[0]);
    $second = $this->service->complete($this->enrollment, $this->required[0]);

    expect($second->id)->toBe($first->id)
        ->and($second->completed_at->equalTo($first->completed_at))->toBeTrue()
        ->and($this->enrollment->completions()->count())->toBe(1);
});

test('atividade opcional não conclui o curso', function () {
    $this->service->complete($this->enrollment, $this->optional);

    expect($this->enrollment->fresh()->completed_at)->toBeNull();
});

test('concluir a última atividade obrigatória registra a data de conclusão', function () {
    $this->service->complete($this->enrollment, $this->required[0]);
    expect($this->enrollment->fresh()->completed_at)->toBeNull();

    $this->service->complete($this->enrollment, $this->required[1]);
    expect($this->enrollment->fresh()->completed_at)->not->toBeNull();
});

test('não aceita atividade de outro curso', function () {
    $outra = Activity::factory()->create();

    expect(fn () => $this->service->complete($this->enrollment, $outra))
        ->toThrow(ActivityNotInCourseException::class);
});

test('não sobrescreve a data de conclusão já registrada', function () {
    foreach ($this->required as $activity) {
        $this->service->complete($this->enrollment, $activity);
    }
    $completedAt = $this->enrollment->fresh()->completed_at;

    $this->service->complete($this->enrollment, $this->optional);

    expect($this->enrollment->fresh()->completed_at->equalTo($completedAt))->toBeTrue();
});
