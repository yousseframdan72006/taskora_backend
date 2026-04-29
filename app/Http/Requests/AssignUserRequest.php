<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'role' => ['nullable', 'string'],
        ];
    }
}
