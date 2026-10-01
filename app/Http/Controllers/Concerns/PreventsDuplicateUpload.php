<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

trait PreventsDuplicateUpload
{
    protected function rejectDuplicateUpload(Request $request): ?Response
    {
        $token = trim((string) $request->input('upload_client_id', ''));
        if ($token === '' || strlen($token) > 64) {
            return null;
        }

        $key = 'upload_client:'.auth()->id().':'.$token;
        if (!Cache::add($key, now()->timestamp, now()->addHour())) {
            $message = 'This upload was already submitted. Refresh the page if you need to upload again.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message, 'code' => 'duplicate_upload'], 409)
                : back()->with('error', $message);
        }

        return null;
    }
}
