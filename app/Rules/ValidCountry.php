<?php

namespace App\Rules;

use App\Support\Countries;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An assigned ISO-3166-1 alpha-2 country code.
 *
 * A rule rather than `Rule::in(Countries::CODES)` for two reasons that both
 * show up in front of a viewer. It accepts lower case, because a client
 * sending "ug" means Uganda and refusing that helps nobody. And it produces a
 * sentence a person can act on instead of Laravel's "The selected country is
 * invalid", which tells somebody staring at a country picker nothing at all.
 */
class ValidCountry implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Absence is the job of `nullable`, not of this rule. Failing here on
        // an empty value would make every country effectively required.
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! Countries::isValid($value)) {
            $fail('Choose a country from the list.');
        }
    }
}
