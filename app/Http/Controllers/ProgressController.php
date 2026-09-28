<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;

class ProgressController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
        private ProgressService $progress,
    ) {}

    public function show(User $user, Course $course): JsonResponse
    {
        return response()->json(
            $this->progress->calculate($this->enrollments->findFor($user, $course))
        );
    }
}
