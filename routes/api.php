<?php

use App\Http\Controllers\ActivityCompletionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;

Route::post('courses/{course}/enrollments', [EnrollmentController::class, 'store']);
Route::post('courses/{course}/activities/{activity}/completion', [ActivityCompletionController::class, 'store']);
Route::get('users/{user}/courses/{course}/progress', [ProgressController::class, 'show']);
