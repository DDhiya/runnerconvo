<?php

namespace App\Http\Requests;

use App\Enums\BookingOptionType;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Runner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class BookingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already behind the `auth` middleware.
    }

    protected function prepareForValidation(): void
    {
        // Same normalise-before-unique reasoning as RegistrationRequest / RunnerRequest.
        if ($this->has('phone')) {
            $this->merge(['phone' => Runner::normalisePhone((string) $this->input('phone'))]);
        }

        if ($this->has('matric_no')) {
            $this->merge(['matric_no' => Booking::normaliseMatric((string) $this->input('matric_no'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        $matric = ['required', 'string', Booking::matricRuleFor($this->input('programme_level'))];
        // A cancelled booking may share a matric number, so only enforce uniqueness when the
        // booking is (or is becoming) live. This is also what stops un-cancelling onto a
        // number another live booking now holds.
        if ($this->input('status') !== BookingStatus::Cancelled->value) {
            $matric[] = Rule::unique('bookings', 'matric_no')
                ->ignore($booking->id)
                ->where(fn ($q) => $q->where('status', '!=', BookingStatus::Cancelled->value));
        }

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'matric_no' => $matric,
            'phone' => ['required', 'string', 'regex:/^601\d{8,9}$/'],
            'email' => ['nullable', 'string', 'max:254', 'email:rfc'],
            'programme_level' => ['required', Rule::in(array_keys(trans('register.options.programme_level', [], 'en')))],
            'faculty_id' => ['required', $this->option(BookingOptionType::Faculty, $booking)],
            'robe_size_id' => ['required', $this->option(BookingOptionType::RobeSize, $booking)],
            'convocation_session_id' => ['required', $this->option(BookingOptionType::ConvocationSession, $booking)],
            'delivery_method' => ['required', Rule::in(['pickup', 'cod'])],
            'delivery_address' => ['required_if:delivery_method,cod', 'nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'runner_id' => ['nullable', 'exists:runners,id'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0', 'max:10000'],
            'paid' => ['boolean'],
            'payment_method' => ['nullable', Rule::in(['transfer', 'duitnow', 'cash', 'other'])],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** An active option of the right list, or the one the booking already has. */
    private function option(BookingOptionType $type, Booking $booking): Exists
    {
        return Rule::exists('booking_options', 'id')->where(fn ($q) => $q
            ->where('type', $type->value)
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $booking->{$type->column()})));
    }

    /**
     * Payment guards. Payment is a flag beside the status line (COD pays at handover), so
     * the rules are about combinations, not about a "paid" stage.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $status = $this->input('status');
                $paid = $this->boolean('paid');

                if ($status === BookingStatus::HandedOver->value && ! $paid) {
                    $validator->errors()->add('status', 'Tick "Paid" before marking this booking as handed over.');
                }

                if ($status === BookingStatus::Collected->value && $this->input('delivery_method') === 'pickup' && ! $paid) {
                    $validator->errors()->add('status', 'A self-pickup booking must be paid before it is collected.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'faculty_id' => 'faculty',
            'robe_size_id' => 'robe size',
            'convocation_session_id' => 'convocation session',
            'runner_id' => 'runner',
            'matric_no' => 'matric number',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'matric_no.regex' => 'Use the format CB22001 (Diploma/Bachelor).',
            'matric_no.unique' => 'Another live booking already has this matric number.',
            'phone.regex' => 'Enter a Malaysian mobile number, e.g. 012-345 6789 or +60123456789.',
        ];
    }
}
