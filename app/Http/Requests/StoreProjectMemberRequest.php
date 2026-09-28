<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('project'));
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            // Looking up by email (not user_id) matches how the UI form
            // works — a project manager knows a colleague's email
            // address, not their internal numeric ID.
            'email' => [
                'required',
                'email',
                'exists:users,email',
                // Closure rule (not just the project_user unique DB
                // constraint) so a duplicate-add attempt produces a clear
                // "already a member" validation message instead of a raw
                // QueryException bubbling up as a 500 — see
                // docs/backend-concepts/validation.md on why the database
                // constraint alone is the wrong layer for user-facing
                // feedback.
                function (string $attribute, mixed $value, Closure $fail) use ($project) {
                    $user = User::where('email', $value)->first();
                    if ($user && $project->hasMember($user)) {
                        $fail('This user is already a member of the project.');
                    }
                },
            ],
            'role' => ['required', new Enum(ProjectRole::class)],
        ];
    }
}
