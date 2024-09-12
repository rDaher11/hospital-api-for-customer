<?php

namespace App\Rules;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Date;

class BirthYearRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */

    const MIN_AGE = 10;     // for testing purposes

    public function passes($attribute, $value)    {
        $birth_date = Carbon::createFromFormat("Y-m-d" ,$value);
        $age = Carbon::now()->diffInYears($birth_date);
        if ($age < BirthYearRule::MIN_AGE) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()    {
        return 'your age is lower than ' . (string)BirthYearRule::MIN_AGE;
    }
}
