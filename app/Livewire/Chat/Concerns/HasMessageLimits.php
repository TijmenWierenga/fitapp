<?php

namespace App\Livewire\Chat\Concerns;

use App\Models\AgentConversationMessage;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;

trait HasMessageLimits
{
    #[Computed]
    public function dailyWindowStart(): ?CarbonImmutable
    {
        $timestamp = AgentConversationMessage::query()
            ->where('user_id', auth()->id())
            ->where('role', 'user')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at')
            ->value('created_at');

        return $timestamp?->toImmutable();
    }

    #[Computed]
    public function monthlyWindowStart(): ?CarbonImmutable
    {
        $timestamp = AgentConversationMessage::query()
            ->where('user_id', auth()->id())
            ->where('role', 'user')
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at')
            ->value('created_at');

        return $timestamp?->toImmutable();
    }

    #[Computed]
    public function usedDailyMessages(): int
    {
        if (! $this->dailyWindowStart) {
            return 0;
        }

        return AgentConversationMessage::query()
            ->where('user_id', auth()->id())
            ->where('role', 'user')
            ->where('created_at', '>=', $this->dailyWindowStart)
            ->count();
    }

    #[Computed]
    public function usedMonthlyMessages(): int
    {
        if (! $this->monthlyWindowStart) {
            return 0;
        }

        return AgentConversationMessage::query()
            ->where('user_id', auth()->id())
            ->where('role', 'user')
            ->where('created_at', '>=', $this->monthlyWindowStart)
            ->count();
    }

    #[Computed]
    public function dailyMessageLimit(): int
    {
        return (int) config('ai.coach.daily_message_limit');
    }

    #[Computed]
    public function monthlyMessageLimit(): int
    {
        return (int) config('ai.coach.monthly_message_limit');
    }

    #[Computed]
    public function remainingDailyMessages(): int
    {
        return max(0, $this->dailyMessageLimit - $this->usedDailyMessages);
    }

    #[Computed]
    public function remainingMonthlyMessages(): int
    {
        return max(0, $this->monthlyMessageLimit - $this->usedMonthlyMessages);
    }

    #[Computed]
    public function remainingMessages(): int
    {
        return min($this->remainingDailyMessages, $this->remainingMonthlyMessages);
    }

    #[Computed]
    public function dailyResetsAt(): ?CarbonImmutable
    {
        return $this->dailyWindowStart?->addHours(24);
    }

    #[Computed]
    public function monthlyResetsAt(): ?CarbonImmutable
    {
        return $this->monthlyWindowStart?->addDays(30);
    }

    #[Computed]
    public function dailyResetIn(): ?string
    {
        if (! $this->dailyResetsAt) {
            return null;
        }

        $diff = now()->diff($this->dailyResetsAt);
        $hours = ($diff->days * 24) + $diff->h;

        if ($hours > 0) {
            return "{$hours}h {$diff->i}m";
        }

        return "{$diff->i}m";
    }

    protected function clearMessageLimitCache(): void
    {
        unset(
            $this->dailyWindowStart,
            $this->monthlyWindowStart,
            $this->usedDailyMessages,
            $this->usedMonthlyMessages,
            $this->remainingDailyMessages,
            $this->remainingMonthlyMessages,
            $this->remainingMessages,
            $this->dailyResetsAt,
            $this->monthlyResetsAt,
            $this->dailyResetIn,
        );
    }
}
