<?php

namespace App\Http\Controllers;

use App\Actions\ExportWorkoutFit;
use App\Models\Workout;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ExportWorkoutFitController
{
    public function __invoke(Workout $workout, ExportWorkoutFit $export): Response
    {
        Gate::authorize('view', $workout);

        $data = $export->execute($workout);

        $date = $workout->scheduled_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $slug = Str::slug($workout->name);
        $filename = "{$date}-{$slug}.fit";

        return new Response($data, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($data),
        ]);
    }
}
