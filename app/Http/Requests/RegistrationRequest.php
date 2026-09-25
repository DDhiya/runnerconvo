<?php

namespace App\Http\Requests;

use App\Enums\BookingOptionType;
use App\Models\Booking;
use App\Models\Runner;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class RegistrationRequest extends FormRequest
{
    /** A human cannot fill this form in under this many seconds. */
    private const MIN_SECONDS = 3;

    public function authorize(): bool
    {
        return true; // public form; abuse is handled by the throttle and after().
    }

    protected function prepareForValidation(): void
    {
        // Normalise BEFORE validating, exactly as RunnerRequest does: Rule::unique compares
        // RAW input against stored (normalised) values, so "cb 22-001" would otherwise pass
        // as not-a-duplicate of "CB22001" and then hit the partial unique index.
        if ($this->has('phone')) {
            $this->merge(['phone' => Runner::normalisePhone((string) $this->input('phone'))]);
        }

        if ($this->has('matric_no')) {
            $this->merge(['matric_no' => Booking::normaliseMatric((string) $this->input('matric_no'))]);
        }

        if ($this->has('full_name')) {
            $this->merge(['full_name' => trim((string) $this->input('full_name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'matric_no' => [
                'required', 'string',
                Booking::matricRuleFor($this->input('programme_level')),
                // Only LIVE bookings block: a cancelled one frees the number again.
                Rule::unique('bookings', 'matric_no')->where(fn ($q) => $q->where('status', '!=', 'cancelled')),
            ],
            // Malaysian mobile, same rule as RunnerRequest.
            'phone' => ['required', 'string', 'regex:/^601\d{8,9}$/'],
            // Optional; only used to email the confirmation. rfc, not dns: validation must never
            // depend on a network lookup.
            'email' => ['nullable', 'string', 'max:254', 'email:rfc'],
            // The en list is the source of truth, so the result does not depend on locale.
            'programme_level' => ['required', Rule::in(array_keys(trans('register.options.programme_level', [], 'en')))],
            'faculty_id' => ['required', $this->activeOption(BookingOptionType::Faculty)],
            'robe_size_id' => ['required', $this->activeOption(BookingOptionType::RobeSize)],
            'convocation_session_id' => ['required', $this->activeOption(BookingOptionType::ConvocationSession)],
            'delivery_method' => ['required', Rule::in(['pickup', 'cod'])],
            'delivery_address' => ['required_if:delivery_method,cod', 'nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:500'],
            'documents_ack' => ['accepted'],
            'consent' => ['accepted'],
        ];
    }

    /** An active option of the right list: a deactivated one, or one from another list, fails. */
    private function activeOption(BookingOptionType $type): Exists
    {
        return Rule::exists('booking_options', 'id')
            ->where(fn ($q) => $q->where('type', $type->value)->where('is_active', true));
    }

    /**
     * Bot checks. A rejection is a VISIBLE generic error, never a fake success page: a real
     * person whose password manager filled the honeypot has to find out it did not work.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $reason = match (true) {
                    filled($this->input('contact_me_by_fax_only')) => 'honeypot',
                    ! $this->hasValidTimer() => 'timer',
                    default => null,
                };

                if ($reason) {
                    Log::info('registration.rejected', ['reason' => $reason]);
                    $validator->errors()->add('form', __('register.errors.generic'));
                }
            },
        ];
    }

    private function hasValidTimer(): bool
    {
        try {
            $started = (int) Crypt::decryptString((string) $this->input('_started'));
        } catch (DecryptException) {
            return false;
        }

        return now()->timestamp - $started >= self::MIN_SECONDS;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(__('register.fields'))
            ->map(fn (array $field) => $field['label'])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Rule-wide, so the wording reads naturally with these labels ("Full name is
            // required.") instead of the framework's "The Full name field is required.".
            'required' => __('register.errors.required'),
            'delivery_method.required' => __('register.errors.delivery_required'),
            'matric_no.regex' => __('register.errors.matric_format'),
            'matric_no.unique' => __('register.errors.duplicate'),
            'phone.regex' => __('register.errors.phone_format'),
            'email.email' => __('register.errors.email_format'),
            'email.max' => __('register.errors.email_format'),
            'delivery_address.required_if' => __('register.errors.address_required'),
            'documents_ack.accepted' => __('register.errors.accept'),
            'consent.accepted' => __('register.errors.accept'),
        ];
    }
}
