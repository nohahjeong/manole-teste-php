<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompletionRequest;
use App\Models\Activity;
use App\Models\Course;
use App\Models\User;
use App\Services\ActivityCompletionService;
use App\Services\EnrollmentService;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;

class ActivityCompletionController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
        private ActivityCompletionService $completions,
        private ProgressService $progress,
    ) {}

    public function store(StoreCompletionRequest $request, Course $course, Activity $activity): JsonResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        $enrollment = $this->enrollments->findFor($user, $course);

        $this->completions->complete($enrollment, $activity);

        return response()->json($this->progress->calculate($enrollment->fresh()), 200);
    }
}
