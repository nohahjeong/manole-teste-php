<?php

namespace App\Services;

use App\Models\Enrollment;

class ProgressService
{
    public function calculate(Enrollment $enrollment): array
    {
        $total = $enrollment->course->requiredActivities()->count();
        $done = $enrollment->completedActivities()->where('is_required', true)->count();

        return [
            'percentage' => $total > 0 ? round($done / $total * 100, 2) : 0.0,
            'required_completed' => $done,
            'required_total' => $total,
            'completed_at' => $enrollment->completed_at,
        ];
    }

    public function isComplete(Enrollment $enrollment): bool
    {
        $progress = $this->calculate($enrollment);

        $total = $progress['required_total'];
        $done = $progress['required_completed'];

        return $total > 0 && $done === $total;
    }
}
