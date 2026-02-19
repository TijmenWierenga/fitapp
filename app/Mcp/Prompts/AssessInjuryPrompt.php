<?php

namespace App\Mcp\Prompts;

use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Prompt;

class AssessInjuryPrompt extends Prompt
{
    protected string $name = 'assess-injury';

    protected string $description = 'Guide through the 5-step injury assessment protocol before adding an injury record.';

    public function handle(Request $request): ResponseFactory
    {
        /** @var User $user */
        $user = $request->user();

        $context = $this->buildContext($user);

        return Response::make([
            Response::text("I'll guide you through an injury assessment. This helps me understand the issue before adding it to your records.")->asAssistant(),
            Response::text($context),
        ]);
    }

    private function buildContext(User $user): string
    {
        $sections = [];

        $sections[] = $this->buildExistingInjurySection($user);
        $sections[] = $this->buildRecentWorkoutSection($user);
        $sections[] = $this->buildAssessmentProtocol();
        $sections[] = $this->buildRedFlags();

        return implode("\n\n", array_filter($sections));
    }

    private function buildExistingInjurySection(User $user): string
    {
        $injuries = $user->injuries()->latest('started_at')->limit(10)->get();

        if ($injuries->isEmpty()) {
            return "## Injury History\n\n*No previous injuries recorded.*";
        }

        $content = "## Injury History\n\n";
        $content .= "Review these for patterns or related issues:\n\n";
        foreach ($injuries as $injury) {
            $status = $injury->is_active ? 'ACTIVE' : "resolved {$injury->ended_at->toDateString()}";
            $content .= "- **{$injury->body_part->value}** ({$injury->injury_type->value}) — started {$injury->started_at->toDateString()} [{$status}]";

            if ($injury->notes) {
                $content .= " — {$injury->notes}";
            }

            $content .= "\n";
        }

        return $content;
    }

    private function buildRecentWorkoutSection(User $user): string
    {
        $now = $user->currentTimeInTimezone();
        $recent = $user->workouts()
            ->completed()
            ->where('completed_at', '>=', $now->subDays(7)->startOfDay())
            ->limit(10)
            ->get();

        if ($recent->isEmpty()) {
            return "## Recent Workouts\n\n*No completed workouts in the last 7 days.*";
        }

        $content = "## Recent Workouts (potential cause context)\n\n";
        foreach ($recent as $workout) {
            $completedAt = $user->toUserTimezone(CarbonImmutable::instance($workout->completed_at));
            $rpeLabel = $workout->rpe ? "RPE {$workout->rpe}" : 'no RPE';
            $content .= "- {$completedAt->format('D M j')}: {$workout->name} ({$workout->activity->label()}) — {$rpeLabel}\n";
        }

        return $content;
    }

    private function buildAssessmentProtocol(): string
    {
        return <<<'MARKDOWN'
## Assessment Protocol

Follow these 5 steps IN ORDER before using the `add-injury` tool:

### Step 1: Location
Ask: "Where are you experiencing the issue?"
- Map to a supported body part
- Ask follow-up questions if the location is unclear

### Step 2: Duration
Ask: "How long have you been experiencing this issue?"
- Determine the `started_at` date
- For acute injuries, get the specific date

### Step 3: Progression
Ask: "Are your symptoms getting better, worse, or staying the same?"
- Helps determine injury type (acute, chronic, recurring)
- Worsening symptoms may indicate a red flag

### Step 4: Pain Characteristics
Ask: "What type of pain or discomfort do you feel?"
- Sharp: Often acute injury or nerve involvement
- Dull: May suggest chronic condition or muscle fatigue
- Aching: Common with overuse or inflammation
- Burning: Could indicate nerve irritation or inflammation

### Step 5: Professional Consultation
Ask: "Have you consulted a healthcare professional about this issue?"
- Document their diagnosis/recommendations in notes
- If not consulted and symptoms are concerning, recommend seeking professional advice

### After Assessment
1. Summarize the injury details back to the user for confirmation
2. Use `add-injury` with appropriate values
3. Include relevant assessment notes in the `notes` field
MARKDOWN;
    }

    private function buildRedFlags(): string
    {
        return <<<'MARKDOWN'
## Red Flags — DO NOT PROCEED

If ANY of these are present, strongly advise the user to seek immediate medical attention instead of adding the injury:

- Severe pain that is unbearable or prevents sleep
- Numbness, tingling, or loss of sensation
- Visible deformity or significant swelling
- Inability to bear weight or move the affected area
- Pain following a traumatic incident (fall, collision, accident)
- Symptoms accompanied by fever, chills, or feeling unwell
- Rapidly worsening symptoms despite rest
- Pain that radiates down arms or legs
- Chest pain or difficulty breathing

**Response template:** "Based on what you've described, I strongly recommend consulting a healthcare professional before continuing. [Specific symptom] can indicate a condition that requires proper medical evaluation. Please see a doctor or physiotherapist before we proceed with your training plan."
MARKDOWN;
    }
}
