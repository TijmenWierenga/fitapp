<div class="max-w-2xl mx-auto py-6">
    @include('partials.workout-detail', ['showBackButton' => true])

    @include('partials.workout-evaluation-modal', ['modalName' => 'evaluation-modal'])

    <livewire:workout.duplicate />

    <livewire:exercise.detail />
</div>
