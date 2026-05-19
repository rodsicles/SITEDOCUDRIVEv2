<?php

namespace App\Http\Requests;

use App\Support\DocumentNaming;
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
                'max:'.DocumentNaming::TITLE_MAX_LENGTH,
                'regex:/^[a-zA-Z0-9\s\-_\.]+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_title.required' => 'Document name is required.',
            'document_title.max' => 'Document name cannot exceed '.DocumentNaming::TITLE_MAX_LENGTH.' characters.',
            'document_title.regex' => 'Only letters, numbers, spaces, and - _ . are allowed.',
        ];
    }
}
