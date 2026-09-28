<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentService $enrollments) {}

    public function store(StoreEnrollmentRequest $request, Course $course): JsonResponse
    {
        $user = User::findOrFail($request->integer('user_id'));

        $enrollment = $this->enrollments->enroll($user, $course);

        return response()->json([
            'id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
            'course_id' => $enrollment->course_id,
            'completed_at' => $enrollment->completed_at,
        ], 201);
    }
}
