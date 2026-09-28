<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProjectRequest extends FormRequest
{
    /**
     * Authorization lives here (delegating to the Policy) as well as in the
     * controller's route-level `can:create,App\Models\Project` middleware
     * would — Form Request authorize() is the conventional place for it,
     * and keeps "can this request proceed at all" colocated with "what
     * shape must the request have" (validation), both gates a request must
     * clear before touching business logic.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            // "due_date must not be before start_date" is a cross-field
            // business rule, not a type/shape rule — `after_or_equal`
            // expresses it declaratively here because it's still purely
            // about request *shape*, not about touching the database or
            // other resources (which is where it would move to a Service
            // instead — see docs/backend-concepts/validation.md).
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
