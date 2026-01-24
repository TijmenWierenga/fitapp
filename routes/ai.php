<?php

use App\Mcp\Servers\WorkoutPlannerServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/workout-planner', WorkoutPlannerServer::class)
    ->middleware('auth:sanctum');

Mcp::local('workout-planner', WorkoutPlannerServer::class);
