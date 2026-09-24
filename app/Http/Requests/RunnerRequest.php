<?php

namespace App\Http\Requests;

use App\Models\Runner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // the route is already behind the `auth` middleware.
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            // Normalise BEFORE validating: Rule::unique compares RAW input against
            // stored (normalised) values, so "+60 14-533 2637" would otherwise sail
            // past uniqueness and blow up on the DB constraint at save time.
            $this->merge(['phone' => Runner::normalisePhone((string) $this->input('phone'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'phone' => [
                'required', 'string',
                // Malaysian mobile: 60 + 1 + 8-9 subscriber digits. Covers both the
                // 8-digit numbers and the 9-digit ones (e.g. Ezdie, Jue). Mobile-only
                // on purpose since these are WhatsApp numbers — a landline or WhatsApp
                // Business number would need /^60\d{8,11}$/ instead.
                'regex:/^601\d{8,9}$/',
                Rule::unique('runners', 'phone')->ignore($this->route('runner')),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Malaysian mobile number, e.g. 012-345 6789 or +60123456789.',
        ];
    }
}
