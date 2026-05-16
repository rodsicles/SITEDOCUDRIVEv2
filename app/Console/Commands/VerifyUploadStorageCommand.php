<?php

namespace App\Console\Commands;

use App\Support\UploadStorage;
use Illuminate\Console\Command;

class VerifyUploadStorageCommand extends Command
{
    protected $signature = 'storage:verify-uploads';

    protected $description = 'Verify the configured upload disk (local or S3/R2) can write and read files';

    public function handle(): int
    {
        $diskName = UploadStorage::diskName();
        $this->info('Upload disk: ' . $diskName);

        $testPath = '_healthcheck/' . uniqid('ping_', true) . '.txt';
        $payload = 'ok-' . now()->toIso8601String();

        try {
            UploadStorage::disk()->put($testPath, $payload, ['visibility' => 'private']);

            if (!UploadStorage::exists($testPath)) {
                $this->error('Write succeeded but file not found on read.');

                return self::FAILURE;
            }

            $read = UploadStorage::disk()->get($testPath);
            UploadStorage::delete($testPath);

            if ($read !== $payload) {
                $this->error('Read content mismatch.');

                return self::FAILURE;
            }

            $this->info('Upload storage is working. Files will persist across deploys.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Upload storage failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
