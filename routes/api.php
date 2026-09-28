<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::post('courses/{course}/enrollments', [EnrollmentController::class, 'store']);
