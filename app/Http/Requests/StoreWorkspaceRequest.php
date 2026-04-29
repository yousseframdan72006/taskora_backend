<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isIndependent();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:workspaces,slug'],
        ];
    }
}
