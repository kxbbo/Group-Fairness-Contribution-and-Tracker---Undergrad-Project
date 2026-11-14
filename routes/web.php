<?php

use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
})->name('home');

// Simulated user switch for testing role and task rules
Route::post('/switch-user', [TaskController::class, 'switchUser'])->name('user.switch');

// Feature 11: Evidence Vault
Route::prefix('evidence')->name('evidence.')->group(function () {
    Route::get('/', [EvidenceController::class, 'tasks'])->name('tasks');
    Route::get('/{taskId}', [EvidenceController::class, 'index'])->name('index');
    Route::get('/{taskId}/create', [EvidenceController::class, 'create'])->name('create');
    Route::post('/{taskId}', [EvidenceController::class, 'store'])->name('store');
    Route::delete('/{taskId}/{id}', [EvidenceController::class, 'destroy'])->name('destroy');
    Route::get('/{taskId}/{id}/download', [EvidenceController::class, 'download'])->name('download');
});

// Feature 6: Weighted Task Management
Route::get('/tasks', [TaskController::class, 'teams'])->name('tasks.teams');
Route::get('/teams/{teamId}/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/teams/{teamId}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
Route::post('/teams/{teamId}/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
