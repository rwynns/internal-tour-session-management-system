<?php

namespace App\Http\Requests;

use App\Enums\SessionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGuestAllocationRequest extends FormRequest
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
            'guest_name' => ['required', 'string', 'max:255'],
            'pax' => ['required', 'integer', 'min:1'],
            'source' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance with custom business rule checks.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $session = $this->route('session');

            if (! $session) {
                return;
            }

            if ($session->status !== SessionStatus::Active) {
                $validator->errors()->add('session', 'This session is currently inactive and cannot accept allocations.');

                return;
            }

            if ($session->start_time->isPast()) {
                $validator->errors()->add('session', 'This session has already started and cannot accept new allocations.');

                return;
            }

            $pax = (int) $this->input('pax');

            if ($pax > 0 && $session->current_pax + $pax > $session->max_capacity) {
                $remaining = $session->remainingCapacity();
                $validator->errors()->add(
                    'pax',
                    "Insufficient capacity. Requested: {$pax}, Available: {$remaining}, Maximum: {$session->max_capacity}"
                );
            }
        });
    }
}
