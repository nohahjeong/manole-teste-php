<?php

namespace App\Services;

use App\Exceptions\ActivityNotInCourseException;
use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\Enrollment;
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
            $completion = ActivityCompletion::firstOrNew([
                'enrollment_id' => $enrollment->id,
                'activity_id' => $activity->id,
            ]);

            if (! $completion->exists) {
                $completion->completed_at = now();
                $completion->save();
            }

            if ($enrollment->completed_at === null && $this->progress->isComplete($enrollment)) {
                $enrollment->completed_at = now();
                $enrollment->save();
            }

            return $completion;
        });
    }
}
