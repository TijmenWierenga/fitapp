<div>
    @if($workout)
        <flux:modal wire:model.self="showModal" :closable="false" @close="closeModal" class="w-full max-w-lg !p-0">
            <div class="max-h-[80vh] overflow-y-auto">
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
