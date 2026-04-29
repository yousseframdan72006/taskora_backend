<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'email'],
            'otp'                   => ['required', 'string', 'digits:6'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            // Flutter sends password_confirmation
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
