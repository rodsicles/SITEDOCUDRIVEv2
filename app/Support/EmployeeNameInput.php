<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class EmployeeNameInput
{
    /**
     * Create-account forms use name parts; existing API callers may still send full_name.
     * EmployeeService continues to receive the validated full_name used across the app.
     */
    public static function prepare(Request $request): array
    {
        $input = $request->all();
        $hasParts = array_key_exists('first_name', $input)
            || array_key_exists('middle_name', $input)
            || array_key_exists('last_name', $input);

        if (!$hasParts) {
            return ['full_name' => 'required|string|max:45'];
        }

        $parts = [];
        $normalized = [];
        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $value = Str::squish($value);
                if ($value !== '') $parts[] = $value;
            }
            $normalized[$field] = $value;
        }

        $request->merge($normalized + ['full_name' => implode(' ', $parts)]);

        return [
            'first_name' => 'required|string|max:45',
            'middle_name' => 'nullable|string|max:45',
            'last_name' => 'required|string|max:45',
            'full_name' => 'required|string|max:45',
        ];
    }
}
