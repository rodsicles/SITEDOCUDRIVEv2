<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => 'nullable|exists:folders,folder_id',
        ];
    }

    public function messages(): array
    {
        return [
            'folder_id.exists' => 'The selected folder does not exist',
        ];
    }
}
