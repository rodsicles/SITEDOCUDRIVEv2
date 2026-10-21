<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class CourseInput
{
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z][A-Za-z0-9 -]*$/'],
            'title' => 'required|string|max:150',
            'year_level' => 'sometimes|nullable|integer|min:1|max:6',
            'semester' => ['sometimes', 'nullable', Rule::in(SchoolTerm::options())],
            'lecture_units' => 'sometimes|nullable|numeric|min:0|max:99|decimal:0,2',
            'lab_units' => 'sometimes|nullable|numeric|min:0|max:99|decimal:0,2',
            'updated_at' => 'sometimes|nullable|string',
            'lock_version' => 'sometimes|integer|min:1',
        ];
    }
}
