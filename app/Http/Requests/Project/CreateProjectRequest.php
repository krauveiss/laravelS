<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'subject' => 'required|string|max:255',
            'deadline' => 'required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required',
            'name.min' => 'Project name must be at least 2 characters',
            'deadline.after' => 'Deadline must be in the future',
        ];
    }
}
