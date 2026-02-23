<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Actions\CalculateWorkload;
use App\Models\User;
use App\Tools\ToolResult;

class GetWorkloadHandler
{
    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    public function execute(User $user): ToolResult
    {
        $summary = $this->calculateWorkload->execute($user);

        return ToolResult::success($summary->toArray());
    }
}
