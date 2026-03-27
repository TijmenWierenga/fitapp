<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

readonly class ParsedActivity
{
    /**
     * @param  list<ParsedLap>  $laps
     * @param  list<ParsedSet>  $sets
     * @param  list<ParsedExerciseTitle>  $exerciseTitles
     */
    public function __construct(
        public ParsedSession $session,
        public array $laps,
        public array $sets,
        public array $exerciseTitles,
    ) {}
}
