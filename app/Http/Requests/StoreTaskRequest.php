<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Task::class, $this->route('project')]);
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(TaskStatus::class)],
            'priority' => ['required', new Enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
            // A task can only be assigned to someone who is actually a
            // member of the project it belongs to — assigning it to an
            // outsider would create a task visible in the project but
            // owned by someone who (per ProjectPolicy) can't even see
            // that project. Closure rule (not just exists:users,id)
            // because this check is cross-table, not a simple existence
            // check.
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
