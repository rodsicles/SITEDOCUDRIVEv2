<?php

namespace App\Http\Controllers\Concerns;

use App\Support\UploadStorageException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;

trait HandlesUploadExceptions
{
    protected function uploadFailedResponse(Request $request, Throwable $e)
    {
        Log::error('Document upload failed', [
            'user_id' => auth()->id(),
            'folder_id' => $request->input('folder_id'),
            'document_type' => $request->input('document_type'),
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        report($e);

        $message = $this->uploadErrorMessage($e);

        if ($request->expectsJson()) {
            $payload = [
                'success' => false,
                'message' => $message,
            ];

            if (config('app.debug')) {
                $payload['debug'] = $e->getMessage();
            }

            return response()->json($payload, 502);
        }

        return back()->with('error', $message)->withInput();
    }

    protected function uploadValidationResponse(Request $request, ValidationException $e)
    {
        $message = collect($e->errors())->flatten()->first() ?? 'Validation failed.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $e->errors(),
            ], 422);
        }

        return back()->withErrors($e->errors())->withInput();
    }

    protected function uploadErrorMessage(Throwable $e): string
    {
        if ($e instanceof UploadStorageException) {
            return $e->getMessage();
        }

        if ($e instanceof FilesystemException) {
            return 'File storage is unavailable. Verify Object Storage is attached on Laravel Cloud and FILESYSTEM_UPLOAD_DISK is set to your bucket disk (usually s3).';
        }

        if ($e instanceof QueryException) {
            if (str_contains($e->getMessage(), 'category')) {
                return 'Could not save the document category for this folder. Run database migrations or contact support.';
            }

            return 'Database error while saving the upload. Please try again or contact support.';
        }

        return 'File upload failed. Please try again or contact support if the problem continues.';
    }
}
