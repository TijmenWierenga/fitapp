<?php

use App\Mcp\Servers\WorkoutServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/workout', WorkoutServer::class)
    ->middleware(['auth:api']);
