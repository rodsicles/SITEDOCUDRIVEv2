<?php

namespace App\Http\Controllers\Concerns;

use App\Support\UploadStorageException;
use Illuminate\Http\Request;
use League\Flysystem\FilesystemException;
use Throwable;

trait HandlesUploadExceptions
{
    protected function uploadFailedResponse(Request $request, Throwable $e)
    {
        report($e);

        $message = $this->uploadErrorMessage($e);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 502);
        }

        return back()->with('error', $message)->withInput();
    }

    protected function uploadErrorMessage(Throwable $e): string
    {
        if ($e instanceof UploadStorageException) {
            return $e->getMessage();
        }

        if ($e instanceof FilesystemException) {
            return 'File storage is unavailable. Verify Object Storage is attached on Laravel Cloud and FILESYSTEM_UPLOAD_DISK is set to your bucket disk (usually s3).';
        }

        return 'File upload failed. Please try again or contact support if the problem continues.';
    }
}
