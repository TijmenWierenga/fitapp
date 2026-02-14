<?php

namespace App\Mcp\Resources;

use App\Models\MuscleGroup;
use Illuminate\Support\Collection;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.6)]
class MuscleGroupsResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'exercise://muscle-groups';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Complete list of available muscle groups with their names, labels, and body regions.

        Use the `name` value when filtering exercises with the `search-exercises` tool's `muscle_group` parameter.
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $muscleGroups = MuscleGroup::query()
            ->orderBy('body_part')
            ->orderBy('name')
            ->get();

        return Response::text($this->buildContent($muscleGroups));
    }

    /**
     * @param  Collection<int, MuscleGroup>  $muscleGroups
     */
    protected function buildContent(Collection $muscleGroups): string
    {
        $content = "# Available Muscle Groups\n\n";

        if ($muscleGroups->isEmpty()) {
            $content .= "*No muscle groups available.*\n";

            return $content;
        }

        $content .= "Use the `name` column value as the `muscle_group` parameter in `search-exercises`.\n\n";

        $grouped = $muscleGroups->groupBy(fn (MuscleGroup $mg): string => $mg->body_part->region());

        foreach ($grouped as $region => $regionMuscleGroups) {
            $content .= "## {$region}\n\n";
            $content .= "| Name | Label | Body Part |\n";
            $content .= "|---|---|---|\n";

            foreach ($regionMuscleGroups as $muscleGroup) {
                $content .= "| {$muscleGroup->name} | {$muscleGroup->label} | {$muscleGroup->body_part->label()} |\n";
            }

            $content .= "\n";
        }

        return $content;
    }
}
