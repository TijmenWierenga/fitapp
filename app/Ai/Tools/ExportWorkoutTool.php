<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\ExportWorkoutHandler;
use App\Tools\Input\ExportWorkoutInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ExportWorkoutTool implements Tool
{
    public function __construct(
        private ExportWorkoutHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Export a workout as a Garmin FIT file. Returns base64-encoded binary data that can be imported into Garmin Connect.';
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(
            auth()->user(),
            ExportWorkoutInput::fromArray($request->toArray()),
        );

        return json_encode($result->toArray());
    }
}
