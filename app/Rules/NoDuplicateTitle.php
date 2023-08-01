<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoDuplicateTitle implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $visited = [];
        foreach ($value as $attestation) {
            if (in_array($attestation['title'],$visited))
                $fail('The :attribute title\'s have duplicate values');

            $visited[] = $attestation['title'];
        }
    }
}
