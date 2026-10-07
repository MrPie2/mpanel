<?php

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::post('/agent/pair', [AgentController::class, 'pair'])->name('agent.pair');
Route::post('/agent/servers/{server}/heartbeat', [AgentController::class, 'heartbeat'])->name('agent.heartbeat');

Route::middleware('agent.auth')->group(function () {
    Route::get('/agent/servers/{server}/jobs', [AgentController::class, 'jobs'])->name('agent.jobs');
    Route::post('/agent/jobs/{job}/complete', [AgentController::class, 'completeJob'])->name('agent.jobs.complete');
});
