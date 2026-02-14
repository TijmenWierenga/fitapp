<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\UserInjuriesResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetInjuriesTool extends Tool
{
    public function __construct(
        private UserInjuriesResource $resource,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's injuries including active and past injuries with body parts, injury types, dates, notes, and recent reports.

        Use this before creating workout plans to avoid exercises that aggravate active injuries. Returns the same data as the `user://injuries` resource.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        return $this->resource->handle($request);
    }
}
