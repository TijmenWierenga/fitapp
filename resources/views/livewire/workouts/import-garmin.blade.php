<div class="max-w-2xl mx-auto py-6">
    <div class="flex items-center gap-2.5 px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" size="sm" />
        <span class="text-base font-semibold text-zinc-900 dark:text-white">Import from Garmin</span>
    </div>

    {{-- Step 1: Upload --}}
    @if($step === 'upload')
        <div class="px-6 py-6 space-y-4">
            <div class="space-y-1">
                <flux:heading size="sm">Upload your .FIT file</flux:heading>
                <flux:text size="sm">Drop a Garmin activity file below to import it as a workout. You will be able to review and edit the structure before saving.</flux:text>
            </div>

            <flux:file-upload wire:model="fitFile" label="Upload .FIT file" accept=".fit">
                <flux:file-upload.dropzone
                    heading="Drop your .FIT file here or click to browse"
                    text="Garmin activity files (.FIT) up to 10MB"
                />
            </flux:file-upload>

            @if($fitFile)
                <flux:file-item
                    :heading="$fitFile->getClientOriginalName()"
                    :size="$fitFile->getSize()"
                />
            @endif

            @if($parseError)
                <flux:callout variant="danger">
                    <flux:callout.heading>Invalid file</flux:callout.heading>
                    <flux:callout.text>{{ $parseError }}</flux:callout.text>
                </flux:callout>
            @endif

            <flux:callout variant="info">
                <flux:callout.text>After uploading, you will be taken to the workout builder where you can review the imported structure, map exercises, and make adjustments before saving.</flux:callout.text>
            </flux:callout>
        </div>
    @endif

    {{-- Step 2: Duplicate Warning --}}
    @if($step === 'duplicate_warning')
        <div class="px-6 py-6 space-y-6">
            <flux:callout variant="warning">
                <flux:callout.heading>Possible duplicate</flux:callout.heading>
                <flux:callout.text>{{ $duplicateWarning }}</flux:callout.text>
            </flux:callout>

            <div class="flex items-center gap-3">
                <flux:button variant="primary" wire:click="confirmImportAnyway">Import Anyway</flux:button>
                <flux:button variant="ghost" wire:click="resetImport">Cancel</flux:button>
            </div>
        </div>
    @endif
</div>
