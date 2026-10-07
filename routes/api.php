<?php

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::post('/agent/servers/{server}/heartbeat', [AgentController::class, 'heartbeat'])->name('agent.heartbeat');
