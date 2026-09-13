<?php

namespace App\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'job_title' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
