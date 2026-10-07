<?php

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::post('/agent/pair', [AgentController::class, 'pair'])->name('agent.pair');
Route::post('/agent/servers/{server}/heartbeat', [AgentController::class, 'heartbeat'])->name('agent.heartbeat');
