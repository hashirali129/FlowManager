<?php

namespace App\Listeners;

use App\Events\DocumentsUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadDocumentsToS3 implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DocumentsUploaded $event): void
    {
        $request = $event->request;

        foreach ($event->files as $fileData) {
            try {
                $tmpPath = $fileData['tmp_path'];

                // Ensure the file exists locally
                if (!Storage::disk('local')->exists($tmpPath)) {
                    Log::error("Local temporary file not found: $tmpPath");
                    continue;
                }

                // Streaming upload to S3 for efficiency
                $s3Path = 'request-documents/' . basename($tmpPath);
                $stream = Storage::disk('local')->readStream($tmpPath);

                Storage::disk('s3')->writeStream($s3Path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                // Create document record
                $request->documents()->create([
                    'file_path' => $s3Path,
                    'file_name' => $fileData['original_name'],
                    'file_type' => $fileData['mime_type'],
                    'file_size' => $fileData['size'],
                ]);

                // Delete local temporary file
                Storage::disk('local')->delete($tmpPath);

                Log::info("Successfully uploaded $tmpPath to S3 and cleaned up local copy.");
            } catch (\Exception $e) {
                Log::error("Failed to upload document in background: " . $e->getMessage());
                // In a real app, you might want to re-throw or handle retries
            }
        }
    }
}
