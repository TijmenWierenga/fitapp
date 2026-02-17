<div class="max-w-4xl mx-auto p-6">
    @include('partials.workout-detail', ['showBackButton' => true])

    @include('partials.workout-evaluation-modal', ['modalName' => 'evaluation-modal'])

    <livewire:workout.duplicate />

    <livewire:exercise.detail />
</div>
