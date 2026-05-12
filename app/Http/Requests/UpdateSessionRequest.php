<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attraction_id' => ['required', 'exists:attractions,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'max_capacity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $session = $this->route('session');

            if ($this->max_capacity && $session && $this->max_capacity < $session->current_pax) {
                $validator->errors()->add('max_capacity', 'Capacity cannot be less than the current number of allocated guests.');
            }
        });
    }
}
