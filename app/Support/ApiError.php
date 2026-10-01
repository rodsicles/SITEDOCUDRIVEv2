<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiError
{
    public static function reference(): string
    {
        return strtoupper(Str::substr(str_replace('-', '', (string) Str::uuid()), 0, 10));
    }

    public static function json(
        Request $request,
        string $message,
        int $status = 500,
        ?string $code = null,
        array $extra = [],
    ): JsonResponse {
        $reference = self::reference();

        Log::warning('API error response', [
            'reference' => $reference,
            'status' => $status,
            'code' => $code,
            'user_id' => $request->user()?->id,
            'path' => $request->path(),
        ]);

        return response()->json(array_merge([
            'ok' => false,
            'message' => $message,
            'code' => $code,
            'reference' => $reference,
        ], $extra), $status);
    }
}
