<?php

namespace App\Rules;

use App\Models\ToDoList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class ValidateToDoCreator implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (ToDoList::query()->find($value)->creator_id !== Auth::id())
            $fail('You don\'t have the rights to read this task.');
    }
}
