<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class GetStarted extends Component
{
    public int $currentStep = 1;

    #[Locked]
    public ?string $newToken = null;

    #[Validate('required|string|max:255')]
    public string $tokenName = 'Claude Code';

    public bool $tokenCreated = false;

    public Collection $tokens;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->loadTokens();
        } else {
            $this->tokens = collect();
        }
    }

    public function loadTokens(): void
    {
        $this->tokens = auth()->user()->tokens()
            ->select(['id', 'name', 'created_at', 'last_used_at'])
            ->latest()
            ->get();
    }

    public function createToken(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->validate();

        if (auth()->user()->tokens()->count() >= 5) {
            $this->addError('tokenLimit', 'Maximum of 5 API keys reached. Manage your keys in Settings.');

            return;
        }

        $token = auth()->user()->createToken($this->tokenName);
        $this->newToken = $token->plainTextToken;
        $this->tokenCreated = true;
        $this->tokenName = '';

        $this->loadTokens();

        $this->goToStep(2);
    }

    public function goToStep(int $step): void
    {
        $this->currentStep = $step;
    }

    public function getMcpEndpoint(): string
    {
        return rtrim(config('app.url'), '/').'/mcp/workout';
    }

    public function getCliCommand(): string
    {
        $token = $this->newToken ?? 'YOUR_API_KEY_HERE';

        return sprintf(
            'claude mcp add --transport http traiq %s --header "Authorization: Bearer %s"',
            $this->getMcpEndpoint(),
            $token
        );
    }

    public function render(): View
    {
        return view('livewire.get-started');
    }
}
