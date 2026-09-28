<?php

use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ProgressService::class);
    $this->course = Course::factory()->create();
    $this->required = Activity::factory()->count(3)->for($this->course)->create();
    $this->optional = Activity::factory()->count(2)->optional()->for($this->course)->create();
    $this->enrollment = Enrollment::factory()->for($this->course)->create();
});

test('retorna 0% quando nenhuma atividade obrigatória foi concluída', function () {
    expect($this->service->calculate($this->enrollment))
        ->percentage->toBe(0.0)
        ->required_completed->toBe(0)
        ->required_total->toBe(3);
});

test('calcula o percentual parcial das atividades obrigatórias', function () {
    ActivityCompletion::factory()->for($this->enrollment)->for($this->required[0])->create();
    ActivityCompletion::factory()->for($this->enrollment)->for($this->required[1])->create();

    expect($this->service->calculate($this->enrollment)['percentage'])->toBe(66.67);
});

test('não considera atividades opcionais no progresso', function () {
    ActivityCompletion::factory()->for($this->enrollment)->for($this->optional[0])->create();

    expect($this->service->calculate($this->enrollment))
        ->percentage->toBe(0.0)
        ->required_completed->toBe(0);
});

test('retorna 100% quando todas as atividades obrigatórias são concluídas', function () {
    foreach ($this->required as $activity) {
        ActivityCompletion::factory()->for($this->enrollment)->for($activity)->create();
    }

    expect($this->service->calculate($this->enrollment)['percentage'])->toBe(100.0);
    expect($this->service->isComplete($this->enrollment))->toBeTrue();
});

test('curso sem atividades obrigatórias fica em 0% e não é concluído', function () {
    $course = Course::factory()->create();
    Activity::factory()->count(2)->optional()->for($course)->create();
    $enrollment = Enrollment::factory()->for($course)->create();

    expect($this->service->calculate($enrollment)['percentage'])->toBe(0.0);
    expect($this->service->isComplete($enrollment))->toBeFalse();
});
