<?php

use App\Console\Commands\CreateSchoolYearFolders;
use App\Console\Commands\EnsureAcademicYearFolders;
use App\Console\Commands\VerifyUploadStorageCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        CreateSchoolYearFolders::class,
        EnsureAcademicYearFolders::class,
        VerifyUploadStorageCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'                  => \App\Http\Middleware\RoleMiddleware::class,
            'no.back'               => \App\Http\Middleware\PreventBackHistory::class,
            'password.changed'      => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        // Apply the password-change gate to every web request. Inside the
        // middleware, unauthenticated requests pass through untouched.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsurePasswordChanged::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Support\UploadStorageException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 502);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        });

        $exceptions->render(function (\League\Flysystem\FilesystemException $e, \Illuminate\Http\Request $request) {
            report($e);

            $message = 'File storage is unavailable. Verify Object Storage is attached on Laravel Cloud and FILESYSTEM_UPLOAD_DISK is set to your bucket disk (usually s3).';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 502);
            }

            return redirect()->back()->with('error', $message)->withInput();
        });
    })->create();
