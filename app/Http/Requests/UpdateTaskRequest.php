<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        $project = $this->route('task')->project;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(TaskStatus::class)],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($project) {
                    if ($value === null) {
                        return;
                    }
                    $user = User::find($value);
                    if (! $user || ! $project->hasMember($user)) {
                        $fail('The assignee must be a member of this project.');
                    }
                },
            ],
        ];
    }
}
