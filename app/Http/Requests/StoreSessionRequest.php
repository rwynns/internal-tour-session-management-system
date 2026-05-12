<?php

namespace App\Http\Requests;

use App\Models\Attraction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSessionRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
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
            if ($this->attraction_id) {
                $attraction = Attraction::find($this->attraction_id);

                if ($attraction && ! $attraction->is_active) {
                    $validator->errors()->add('attraction_id', 'Sessions can only be created for active attractions.');
                }
            }
        });
    }
}
