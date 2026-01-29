<?php

use App\Mcp\Servers\WorkoutServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/workout', WorkoutServer::class)
    ->middleware(['auth:sanctum']);

Mcp::local('workout', WorkoutServer::class);
