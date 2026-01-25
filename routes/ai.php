<?php

use App\Mcp\Servers\WorkoutServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/workout', WorkoutServer::class);
Mcp::local('workout', WorkoutServer::class);
