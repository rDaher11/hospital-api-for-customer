<?php

namespace App\Http\Requests;

use App\Rules\DoctorSpecRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepartementForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {   // we'll use policies
        return true;
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ( $this->isMethod('POST') ) {
            return [
                "name" => "required|string|max:255|min:2",
                "description" => "required|string|max:255|min:2",
                "specialization" => [
                    "required",
                    "integer",
                    new DoctorSpecRule(),
                ]
            ];
        } else if ( $this->isMethod('PUT') ) {
            return [
                "name" => 'string|max:255|min:2',
                "description" => 'string|max:255|min:2',
                "specialization" => [
                    "integer",
                    new DoctorSpecRule(),
                ]
            ];
        }
        
    }
}
