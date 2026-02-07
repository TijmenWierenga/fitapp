<?php

namespace App\Rules;

use App\Models\WorkoutBlock;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxBlockDepth implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = WorkoutBlock::find($value);

        if ($parent && $parent->depth() >= WorkoutBlock::maxDepth()) {
            $fail('Maximum nesting depth of '.WorkoutBlock::maxDepth().' levels exceeded.');
        }
    }
}
