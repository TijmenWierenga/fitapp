<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

class Timezone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (! in_array($value, DateTimeZone::listIdentifiers(), true)) {
            $fail('The :attribute must be a valid timezone identifier.');
        }
    }
}
