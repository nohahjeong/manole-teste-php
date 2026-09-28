<?php

use App\Http\Controllers\ActivityCompletionController;
use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::post('courses/{course}/enrollments', [EnrollmentController::class, 'store']);
Route::post('courses/{course}/activities/{activity}/completion', [ActivityCompletionController::class, 'store']);
