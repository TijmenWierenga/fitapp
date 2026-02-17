<div>
    @if($workout)
        <flux:modal wire:model.self="showModal" :closable="false" @close="closeModal" class="w-full max-w-4xl">
            <div class="max-h-[80vh] overflow-y-auto space-y-6">
                @include('partials.workout-detail', [
                    'showBackButton' => false,
                    'showViewFullPage' => true,
                    'inModal' => true,
                ])
            </div>
        </flux:modal>

        @include('partials.workout-evaluation-modal', ['modalName' => 'preview-evaluation'])
    @endif
</div>
