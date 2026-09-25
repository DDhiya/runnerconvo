<?php

namespace App\Http\Requests;

use App\Enums\BookingOptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already behind the `auth` middleware.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = BookingOptionType::from($this->route('type'));

        return [
            // Both languages are required: the public form is bilingual and these labels are
            // not in lang files, so nothing in CI can check a missing translation.
            'label_en' => [
                'required', 'string', 'max:120',
                Rule::unique('booking_options', 'label_en')
                    ->where('type', $type->value)
                    ->ignore($this->route('option')),
            ],
            'label_ms' => ['required', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['label_en' => 'English label', 'label_ms' => 'Malay label'];
    }
}
