<?php

namespace App\Services\Uploads;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class S3Service
{
    public const CONTENT_TYPES = ['png' => 'image/png', 'jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'html' => 'text/html', 'pdf' => 'application/pdf', 'mp4' => 'video/mp4'];

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

    public function uploadFile($file, $filename, $contentType, $fileType = '')
    {
        try {
            if ($fileType == 'base64') {
                $fileContent = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));
            } else {
                $fileContent = fopen($file->getPathname(), 'r');
            }

            $s3 = self::S3ClientObject();

            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => env('AWS_DEFAULT_FOLDER') . '/' . $filename,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

            $url = $result['ObjectURL'];
            return $url;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }

    public function uploadHTML($htmlContent, $filename, $contentType, $fileType = '')
    {
        try {
            $s3 = self::S3ClientObject();

            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => env('AWS_DEFAULT_FOLDER') . '/' . $filename,
                'Body' => $htmlContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

            $url = $result['ObjectURL'];
            return $url;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }

    public function checkForAWSObject($bucketName, $folderPath) {
        try {
            if($folderPath) {
                $s3 = self::S3ClientObject();

                // Check if the nested folder exists
                $folderExists = $s3->doesObjectExist($bucketName, $folderPath);

                if (!$folderExists) {
                    // Create the nested folder as an empty object
                    $s3->putObject([
                        'Bucket' => $bucketName,
                        'Key' => $folderPath,
                        'Body' => '', // Empty content
                    ]);
                    return false;
                } else {
                    return true;
                }
            }
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }

    public function uploadEmailTemplateHTMLToBucket($eventId, $subEventId, $htmlContent, $filename, $contentType)
    {
        try {
            $s3 = self::S3ClientObject();

            $key = NULL;
            if($subEventId) {
                $awsObjPath = env('AWS_DEFAULT_FOLDER') . '/' . $eventId . '/' . $subEventId . '/';
                $this->checkForAWSObject(env('AWS_BUCKET'), $awsObjPath);
                $key = $awsObjPath . $filename;
            } else {
                $awsObjPath = env('AWS_DEFAULT_FOLDER') . '/' . $eventId . '/';
                $this->checkForAWSObject(env('AWS_BUCKET'), $awsObjPath);
                $key = $awsObjPath . $filename;
            }

            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => $key,
                'Body' => $htmlContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

            $url = $result['ObjectURL'];
            return $url;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }

    public function uploadMailGunMailReport($companyId, $eventId, $emailLogId, $jsonFileContent)
    {
        try {
            $s3 = self::S3ClientObject();

            $key = NULL;
            $bucketName = env('AWS_BUCKET');
            if($companyId && $eventId) {
                $fileCount = 1;
                $awsObjPath = env('AWS_DEFAULT_FOLDER') . '/'. 'email_logs/' . $companyId . '/' . $eventId . '/'. $emailLogId . '/';
                if($this->checkForAWSObject($bucketName, $awsObjPath)) {
                    $filesObj = $s3->listObjects([
                        'Bucket' => $bucketName,
                        'Prefix' => $awsObjPath,
                    ]);
                    
                    // Count the number of objects in the list
                    $fileCount = count($filesObj['Contents']) + 1;
                }

                $fileName = $fileCount.".json";
                $key = $awsObjPath . $fileName;
            }

            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key' => $key,
                'Body' => $jsonFileContent,
                'ContentType' => 'application/json',
                'ACL' => 'public-read'
            ]);
            if($result) {
                return $result;
            }
            return false;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }

    public function getFile($filename)
    {
        try {
            $s3 = self::S3ClientObject();
            $bucket = env('AWS_BUCKET');
            $folder = env('AWS_DEFAULT_FOLDER');
            $result = $s3->doesObjectExist($bucket, $folder . '/' . $filename);

            if ($result) {
                $uploadedFilePath = $folder . '/' . $filename;
                $url = $s3->getObjectUrl($bucket, $uploadedFilePath);
            } else {
                $url = '';
            }

            return $url;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }
    public function uploadPdfFile($file, $filename, $contentType, $fileType = '')
    {
        try {
            $fileContent = fopen($file->getPathname(), 'r');

            $s3 = self::S3ClientObject();

            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => env('AWS_DEFAULT_FOLDER') . '/' . $filename,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

            $url = $result['ObjectURL'];
            return $url;
        } catch (AwsException $e) {
            if (env('APP_DEBUG')) {
                print_r('Error uploading file to S3:' . $e->getMessage());
                print_r('LINE NO::' . strval($e->getLine()));
                Log::error($e->getMessage());
            }
            return null;
        }
    }

    public function uploadEventFile($companyId, $eventId, $filename, $contentType, $file, $fileContent = null,$isUndo = false)
    {
        try {
            if(!empty($file)){

                $fileContent = fopen($file->getPathname(), 'r');
            }

            $s3 = self::S3ClientObject();

            if($isUndo){
                $key = env('AWS_DEFAULT_FOLDER') . '/' . $companyId . '/' . $eventId . '/' . $filename;
            }else{
                $key = env('AWS_DEFAULT_FOLDER') . '/' . $companyId . '/' . $eventId . '/undo/' . $filename;
            }

            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => $key,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'ACL' => 'public-read',
            ]);

            $url = $result['ObjectURL'];
            return $url;
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