<?php

namespace App\Services;

use App\Exceptions\DuplicateEnrollmentException;
use App\Exceptions\InactiveCourseException;
use App\Exceptions\InactiveUserException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class EnrollmentService
{
    public function enroll(User $user, Course $course): Enrollment
    {
        if (! $user->is_active) {
            throw new InactiveUserException;
        }

        if (! $course->is_active) {
            throw new InactiveCourseException;
        }

        try {
            return Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // O índice único (user_id, course_id) é a garantia contra duplicidade.
            // Em duas requisições simultâneas, ambas passam pela validação e só uma é inserida.
            throw new DuplicateEnrollmentException;
        }
    }
}
