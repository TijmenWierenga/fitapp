<?php

namespace App\Ai\Tools;

use App\Tools\Handlers\GetInjuriesHandler;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetInjuriesTool implements Tool
{
    public function __construct(
        private GetInjuriesHandler $handler,
    ) {}

    public function description(): string
    {
        return 'Get the user\'s injuries including active and past injuries with body parts, injury types, dates, and notes. Use before creating workout plans to avoid exercises that aggravate active injuries.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $result = $this->handler->execute(auth()->user());

        return json_encode($result->toArray());
    }
}
