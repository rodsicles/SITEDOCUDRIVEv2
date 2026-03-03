<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $folderId = $this->route('folder');
        
        return [
            'folder_name' => [
                'required',
                'string',
                'max:13',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('folders')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($folderId, 'folder_id'),
            ],
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'folder_name.required' => 'Folder name is required',
            'folder_name.max' => 'Folder name cannot exceed 100 characters',
            'folder_name.regex' => 'Folder name can only contain letters, numbers, spaces, hyphens, and underscores',
            'folder_name.unique' => 'You already have a folder with this name',
            'color.regex' => 'Color must be a valid hex color code',
        ];
    }
}
