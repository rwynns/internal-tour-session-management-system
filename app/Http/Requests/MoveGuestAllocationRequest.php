<?php

namespace App\Http\Requests;

use App\Enums\SessionStatus;
use App\Models\Session;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MoveGuestAllocationRequest extends FormRequest
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
            'target_session_id' => ['required', 'exists:tour_sessions,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allocation = $this->route('allocation');
            $targetSession = Session::find($this->target_session_id);

            if (! $allocation || ! $targetSession) {
                return;
            }

            if ($allocation->session_id === $targetSession->id) {
                $validator->errors()->add('target_session_id', 'Cannot move allocation to the same session.');

                return;
            }

            if ($targetSession->status !== SessionStatus::Active) {
                $validator->errors()->add('target_session_id', 'This session is currently inactive and cannot accept allocations.');

                return;
            }

            if ($targetSession->start_time->isPast()) {
                $validator->errors()->add('target_session_id', 'This session has already started and cannot accept new allocations.');

                return;
            }

            $remaining = $targetSession->max_capacity - $targetSession->current_pax;

            if ($targetSession->current_pax + $allocation->pax > $targetSession->max_capacity) {
                $validator->errors()->add(
                    'target_session_id',
                    "Insufficient capacity. Requested: {$allocation->pax}, Available: {$remaining}, Maximum: {$targetSession->max_capacity}"
                );
            }
        });
    }
}
