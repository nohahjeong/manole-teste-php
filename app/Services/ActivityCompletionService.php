<?php

namespace App\Services;

use App\Exceptions\ActivityNotInCourseException;
use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\Enrollment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ActivityCompletionService
{
    public function __construct(private ProgressService $progress) {}

    public function complete(Enrollment $enrollment, Activity $activity): ActivityCompletion
    {
        if ($activity->course_id !== $enrollment->course_id) {
            throw new ActivityNotInCourseException;
        }

        return DB::transaction(function () use ($enrollment, $activity) {
            // Trava a matrícula para serializar conclusões simultâneas. Sem isso, duas
            // requisições concluindo as últimas obrigatórias não enxergam a inserção uma
            // da outra e nenhuma das duas grava a data de conclusão.
            $enrollment = Enrollment::whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

            $completion = ActivityCompletion::firstOrNew([
                'enrollment_id' => $enrollment->id,
                'activity_id' => $activity->id,
            ]);

            if (! $completion->exists) {
                $completion->completed_at = now();
                try {
                    $completion->save();
                } catch (UniqueConstraintViolationException) {
                    $completion = ActivityCompletion::where('enrollment_id', $enrollment->id)
                        ->where('activity_id', $activity->id)
                        ->firstOrFail();
                }
            }

            if ($enrollment->completed_at === null && $this->progress->isComplete($enrollment)) {
                $enrollment->completed_at = now();
                $enrollment->save();
            }

            return $completion;
        });
    }
}
