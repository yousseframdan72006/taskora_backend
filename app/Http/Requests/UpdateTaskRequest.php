<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:todo,in_progress,review,done'],
            'priority' => ['sometimes', 'required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'report_text' => ['nullable', 'string'],
            'report_file_url' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $task = $this->route('task');
            $newStatus = $this->input('status');

            if ($newStatus && $task && $task->status !== $newStatus) {
                $validTransitions = [
                    'todo' => ['in_progress'],
                    'in_progress' => ['review', 'todo', 'done'],
                    'review' => ['done', 'in_progress'],
                    'done' => ['review', 'in_progress'],
                ];

                $allowed = $validTransitions[$task->status] ?? [];

                if (!in_array($newStatus, $allowed)) {
                    $validator->errors()->add('status', "Invalid status transition. You cannot jump from {$task->status} to {$newStatus} directly.");
                }
            }
        });
    }
}
