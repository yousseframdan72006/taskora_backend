<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Handled by policies
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role' => ['nullable', 'in:admin,member'],
        ];
    }
}
