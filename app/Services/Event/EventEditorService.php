<?php

namespace App\Services\Event;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;

class EventEditorService
{
    public function S3ClientObject()
    {
        return new S3Client([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    public function uploadMediaFile($file, $path)
    {
        try {
            // Check if the input is a base64-encoded string
            if (strpos($file, 'data:') === 0 && preg_match('#^data:([\w/]+);base64,#i', $file, $matches)) {
                $contentType = $matches[1];
                $fileContent = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));
            } else {
                // Input is a file
                // Check if the file type is in the allowed list
                $allowedFileTypes = ['jpeg', 'jpg', 'png', 'gif', 'mp4', 'webm', 'avi', 'ogg'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedFileTypes)) {
                    throw new \InvalidArgumentException('Invalid file type.');
                }
                
                // Generate a unique file name with the same extension
                $uniqueFileName = time() . '_' . uniqid() . '.' . $extension;
                
                // Determine content type based on file extension
                $contentType = mime_content_type($file->getPathname());
                $fileContent = fopen($file->getPathname(), 'r');
            }

            $s3 = self::S3ClientObject();

            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => env('AWS_DEFAULT_FOLDER') . '/' . $path . '/' . $uniqueFileName,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

            $url = $result['ObjectURL'];
            return $url;
        } catch (\InvalidArgumentException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error: ' . $e->getMessage());
                Log::error($e->getMessage());
            }
            return null;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }
}