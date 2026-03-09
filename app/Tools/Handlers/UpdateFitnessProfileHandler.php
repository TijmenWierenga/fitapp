<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Enums\BiologicalSex;
use App\Enums\Equipment;
use App\Enums\ExperienceLevel;
use App\Enums\FitnessGoal;
use App\Models\User;
use App\Tools\Input\UpdateFitnessProfileInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class UpdateFitnessProfileHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'primary_goal' => $schema->string()->enum(FitnessGoal::class)->description('Primary fitness goal.'),
            'goal_details' => $schema->string()->description('Optional detailed description of specific goals (e.g., "Run a sub-4hr marathon by October")')->nullable(),
            'available_days_per_week' => $schema->integer()->description('Number of days available for training per week (1-7)'),
            'minutes_per_session' => $schema->integer()->description('Typical workout session duration in minutes (15-180)'),
            'prefer_garmin_exercises' => $schema->boolean()->description('When true, prefer exercises with Garmin FIT mapping for device compatibility. Use `garmin_compatible` filter in search-exercises tool.')->nullable(),
            'experience_level' => $schema->string()->enum(ExperienceLevel::class)->description('Training experience level: beginner (<1yr), intermediate (1-3yr), advanced (3+yr)')->nullable(),
            'date_of_birth' => $schema->string()->description('Date of birth in YYYY-MM-DD format for age-based calculations')->nullable(),
            'biological_sex' => $schema->string()->enum(BiologicalSex::class)->description('Biological sex for HR zone and strength baseline calculations')->nullable(),
            'body_weight_kg' => $schema->number()->description('Body weight in kilograms for relative strength calculations')->nullable(),
            'height_cm' => $schema->integer()->description('Height in centimeters')->nullable(),
            'has_gym_access' => $schema->boolean()->description('Whether the user has access to a gym with standard equipment (barbells, dumbbells, cables, machines)')->nullable(),
            'home_equipment' => $schema->array(
                $schema->string()->enum(Equipment::class),
            )->description('Equipment available at home for non-gym workouts')->nullable(),
        ];
    }

    public function execute(User $user, UpdateFitnessProfileInput $input): ToolResult
    {
        $data = [
            'primary_goal' => FitnessGoal::from($input->primaryGoal),
            'goal_details' => $input->goalDetails,
            'available_days_per_week' => $input->availableDaysPerWeek,
            'minutes_per_session' => $input->minutesPerSession,
        ];

        if ($input->has('prefer_garmin_exercises')) {
            $data['prefer_garmin_exercises'] = $input->preferGarminExercises ?? false;
        }

        if ($input->has('experience_level')) {
            $data['experience_level'] = $input->experienceLevel;
        }

        if ($input->has('date_of_birth')) {
            $data['date_of_birth'] = $input->dateOfBirth;
        }

        if ($input->has('biological_sex')) {
            $data['biological_sex'] = $input->biologicalSex;
        }

        if ($input->has('body_weight_kg')) {
            $data['body_weight_kg'] = $input->bodyWeightKg;
        }

        if ($input->has('height_cm')) {
            $data['height_cm'] = $input->heightCm;
        }

        if ($input->has('has_gym_access')) {
            $data['has_gym_access'] = $input->hasGymAccess ?? false;
        }

        if ($input->has('home_equipment')) {
            $data['home_equipment'] = $input->homeEquipment;
        }

        $profile = $user->fitnessProfile()->updateOrCreate(
            ['user_id' => $user->getKey()],
            $data,
        );

        return ToolResult::success([
            'profile' => [
                'id' => $profile->id,
                'primary_goal' => $profile->primary_goal->value,
                'primary_goal_label' => $profile->primary_goal->label(),
                'goal_details' => $profile->goal_details,
                'available_days_per_week' => $profile->available_days_per_week,
                'minutes_per_session' => $profile->minutes_per_session,
                'prefer_garmin_exercises' => $profile->prefer_garmin_exercises,
                'experience_level' => $profile->experience_level?->value,
                'date_of_birth' => $profile->date_of_birth?->toDateString(),
                'age' => $profile->age,
                'biological_sex' => $profile->biological_sex?->value,
                'body_weight_kg' => $profile->body_weight_kg,
                'height_cm' => $profile->height_cm,
                'has_gym_access' => $profile->has_gym_access,
                'home_equipment' => $profile->home_equipment,
            ],
            'message' => 'Fitness profile updated successfully',
        ]);
    }
}
