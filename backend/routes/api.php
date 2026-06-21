<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InheritorController;
use App\Http\Controllers\MaterialPackageController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\WorkController;

Route::get('/inheritors/all', [InheritorController::class, 'all']);
Route::apiResource('inheritors', InheritorController::class);

Route::get('/material-packages/all', [MaterialPackageController::class, 'all']);
Route::post('/material-packages/{id}/stock-in', [MaterialPackageController::class, 'stockIn']);
Route::post('/material-packages/{id}/stock-out', [MaterialPackageController::class, 'stockOut']);
Route::apiResource('material-packages', MaterialPackageController::class);

Route::get('/activities/all', [ActivityController::class, 'all']);
Route::post('/activities/{id}/publish', [ActivityController::class, 'publish']);
Route::post('/activities/{id}/cancel', [ActivityController::class, 'cancel']);
Route::apiResource('activities', ActivityController::class);

Route::post('/schedules/{id}/confirm', [ScheduleController::class, 'confirm']);
Route::post('/schedules/{id}/open-registration', [ScheduleController::class, 'openRegistration']);
Route::post('/schedules/{id}/close-registration', [ScheduleController::class, 'closeRegistration']);
Route::post('/schedules/{id}/start', [ScheduleController::class, 'start']);
Route::post('/schedules/{id}/end', [ScheduleController::class, 'end']);
Route::post('/schedules/{id}/cancel', [ScheduleController::class, 'cancel']);
Route::apiResource('schedules', ScheduleController::class);

Route::get('/students/all', [StudentController::class, 'all']);
Route::apiResource('students', StudentController::class);

Route::post('/registrations/{id}/pay', [RegistrationController::class, 'pay']);
Route::post('/registrations/{id}/checkin', [RegistrationController::class, 'checkin']);
Route::post('/registrations/{id}/refund', [RegistrationController::class, 'refund']);
Route::get('/registrations/{id}/can-submit-work', [RegistrationController::class, 'canSubmitWork']);
Route::apiResource('registrations', RegistrationController::class);

Route::post('/works/{id}/review', [WorkController::class, 'review']);
Route::post('/works/{id}/set-public', [WorkController::class, 'setPublic']);
Route::post('/works/{id}/set-private', [WorkController::class, 'setPrivate']);
Route::post('/works/{id}/set-excellent', [WorkController::class, 'setExcellent']);
Route::post('/works/{id}/cancel-excellent', [WorkController::class, 'cancelExcellent']);
Route::apiResource('works', WorkController::class);
