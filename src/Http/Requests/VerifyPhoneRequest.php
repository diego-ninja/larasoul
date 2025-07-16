<?php

namespace Ninja\Larasoul\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'phone_number' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'Phone number is required',
            'phone_number.string' => 'Phone number must be a valid string',
            'phone_number.min' => 'Phone number must be at least 10 characters',
            'phone_number.max' => 'Phone number must not exceed 15 characters',
            'phone_number.regex' => 'Phone number format is invalid',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'phone_number' => 'phone number',
        ];
    }

    /**
     * Get the phone number from the request.
     */
    public function getPhoneNumber(): string
    {
        return $this->input('phone_number');
    }

    /**
     * Magic getter for phone number.
     */
    public function __get($key)
    {
        if ($key === 'phoneNumber') {
            return $this->getPhoneNumber();
        }

        return parent::__get($key);
    }
}
