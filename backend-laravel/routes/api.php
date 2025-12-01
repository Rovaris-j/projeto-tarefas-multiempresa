<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->get('me', [AuthController::class, 'me']);
Route::middleware('auth:api')->group(function () {
    Route::apiResource('tasks', TaskController::class);

    Route::get('admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('admin/tasks', [AdminController::class, 'tasks']);
    Route::get('admin/users', [AdminController::class, 'users']);
    Route::post('admin/users', [AdminController::class, 'storeUser']);
});
