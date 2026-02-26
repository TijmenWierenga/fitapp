<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class GetStarted extends Component
{
    public int $currentStep = 1;

    public string $setupMethod = 'chat';

    public function goToStep(int $step): void
    {
        $this->currentStep = $step;
    }

    public function selectMethod(string $method): void
    {
        if (! in_array($method, ['chat', 'desktop', 'cli', 'chatgpt', 'vscode', 'other'])) {
            return;
        }

        if ($method === 'chat') {
            if (auth()->check()) {
                $this->redirect(route('coach', ['intake' => 1]));
            } else {
                $this->redirect(route('register'));
            }

            return;
        }

        $this->setupMethod = $method;
        $this->goToStep(2);
    }

    public function getMethodLabel(): string
    {
        return match ($this->setupMethod) {
            'chat' => 'In-App Chat',
            'desktop' => 'Claude Desktop',
            'cli' => 'Claude Code CLI',
            'chatgpt' => 'ChatGPT Desktop',
            'vscode' => 'VS Code / Copilot',
            'other' => 'Other MCP Client',
        };
    }

    public function getMcpEndpoint(): string
    {
        return rtrim(config('app.url'), '/').'/mcp/workout';
    }

    public function getCliCommand(): string
    {
        return sprintf('claude mcp add --transport http Traiq %s', $this->getMcpEndpoint());
    }

    public function render(): View
    {
        return view('livewire.get-started');
    }
}
