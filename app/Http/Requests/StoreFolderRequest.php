<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'folder_name' => [
                'required',
                'string',
                'max:13',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
            ],
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'parent_id' => 'nullable|integer|exists:folders,folder_id',
        ];
    }

    public function messages(): array
    {
        return [
            'folder_name.required' => 'Folder name is required',
            'folder_name.max' => 'Folder name cannot exceed 13 characters',
            'folder_name.regex' => 'Folder name can only contain letters, numbers, spaces, hyphens, and underscores',
            'folder_name.unique' => 'You already have a folder with this name',
            'color.regex' => 'Color must be a valid hex color code',
        ];
    }
}
