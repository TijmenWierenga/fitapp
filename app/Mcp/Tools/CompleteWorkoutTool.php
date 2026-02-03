<?php

namespace App\Mcp\Tools;

use App\Data\CompleteWorkoutData;
use App\Data\InjuryEvaluationData;
use App\Models\Workout;
use App\Services\Workout\WorkoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CompleteWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Mark a workout as completed with RPE and feeling ratings, plus optional notes and injury feedback.

        **RPE (Rate of Perceived Exertion):** 1-10 scale
        - 1-2: Very Easy
        - 3-4: Easy
        - 5-6: Moderate
        - 7-8: Hard
        - 9-10: Maximum Effort

        **Feeling:** 1-5 scale (post-workout feeling)
        - 1: Terrible
        - 2: Poor
        - 3: Average
        - 4: Good
        - 5: Great

        **Completion Notes:** Optional free-text for general workout feedback (what went well, challenges, etc.)

        **Injury Evaluations:** If the user has active injuries, use `list-injuries` first to get injury IDs,
        then include injury feedback for each relevant injury. This helps track how injuries respond to training.
    MARKDOWN;

    public function __construct(
        protected WorkoutService $workoutService
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'rpe' => 'required|integer|min:1|max:10',
            'feeling' => 'required|integer|min:1|max:5',
            'completion_notes' => 'nullable|string|max:5000',
            'injury_evaluations' => 'nullable|array',
            'injury_evaluations.*.injury_id' => 'required|integer',
            'injury_evaluations.*.discomfort_score' => 'nullable|integer|min:1|max:10',
            'injury_evaluations.*.notes' => 'nullable|string|max:1000',
        ], [
            'rpe.min' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'rpe.max' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'feeling.min' => 'Feeling must be between 1 and 5',
            'feeling.max' => 'Feeling must be between 1 and 5',
            'injury_evaluations.*.discomfort_score.min' => 'Discomfort score must be between 1 and 10',
            'injury_evaluations.*.discomfort_score.max' => 'Discomfort score must be between 1 and 10',
        ]);

        $user = $request->user();

        $workout = $this->workoutService->find($user, $validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        $injuryEvaluations = [];
        foreach ($validated['injury_evaluations'] ?? [] as $evaluation) {
            $injuryEvaluations[] = new InjuryEvaluationData(
                injuryId: $evaluation['injury_id'],
                discomfortScore: $evaluation['discomfort_score'] ?? null,
                notes: $evaluation['notes'] ?? null,
            );
        }

        $data = new CompleteWorkoutData(
            rpe: $validated['rpe'],
            feeling: $validated['feeling'],
            completionNotes: $validated['completion_notes'] ?? null,
            injuryEvaluations: $injuryEvaluations,
        );

        try {
            $workout = $this->workoutService->complete($user, $workout, $data);
        } catch (AuthorizationException) {
            return Response::error('Workout is already completed');
        }

        $injuryEvaluationsResponse = $workout->injuryEvaluations->map(fn ($eval): array => [
            'injury_id' => $eval->injury_id,
            'body_part' => $eval->injury->body_part->value,
            'body_part_label' => $eval->injury->body_part->label(),
            'discomfort_score' => $eval->discomfort_score,
            'notes' => $eval->notes,
        ])->all();

        return Response::text(json_encode([
            'success' => true,
            'workout' => [
                'id' => $workout->id,
                'name' => $workout->name,
                'completed_at' => $user->toUserTimezone($workout->completed_at)->toIso8601String(),
                'rpe' => $workout->rpe,
                'rpe_label' => Workout::getRpeLabel($workout->rpe),
                'feeling' => $workout->feeling,
                'completion_notes' => $workout->completion_notes,
                'injury_evaluations' => $injuryEvaluationsResponse,
            ],
            'message' => 'Workout completed successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to complete'),
            'rpe' => $schema->integer()->description('Rate of Perceived Exertion (1-10): 1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum'),
            'feeling' => $schema->integer()->description('Post-workout feeling (1-5): 1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great'),
            'completion_notes' => $schema->string()->description('Optional general notes about the workout (max 5000 chars)')->nullable(),
            'injury_evaluations' => $schema->array()->description('Optional array of injury feedback. Get injury IDs from list-injuries tool first.')->items(
                $schema->object()->properties([
                    'injury_id' => $schema->integer()->description('The ID of the injury being evaluated'),
                    'discomfort_score' => $schema->integer()->description('Pain/discomfort level during workout (1=minimal, 10=severe)')->nullable(),
                    'notes' => $schema->string()->description('Optional notes about how this injury felt during the workout (max 1000 chars)')->nullable(),
                ])->required(['injury_id'])
            )->nullable(),
        ];
    }
}
