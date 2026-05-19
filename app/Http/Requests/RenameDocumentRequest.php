<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenameDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'document_title' => [
                'required',
                'string',
                'max:13',
                'regex:/^[a-zA-Z0-9\s\-_\.]+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_title.required' => 'Document name is required.',
            'document_title.max' => 'Document name cannot exceed 13 characters.',
            'document_title.regex' => 'Only letters, numbers, spaces, and - _ . are allowed.',
        ];
    }
}
