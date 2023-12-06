<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Utils\ApiService as API;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\Uploads\S3Service;
use Illuminate\Support\Facades\Validator;
use App\Services\Event\EventResourceService;


class ResourceController extends Controller
{
    public function eventResource(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'max:50'],
                'description' => ['nullable', 'max:255'],
                'file_type' => ['required', 'max:50'],
                // 'link' => ['required', 'max:50']
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }
            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'file_type' => $request->file_type,
                'link' => $request->link,
                // 'file' => $request->file,
            ];
            $resource = (new EventResourceService())->store($request->all(),$data);

            return API::response(API::SUCCESS, ['resource' => $resource]);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function Resourcelist(Request $request){
        try {
            return API::response(API::SUCCESS, ['ResourceList' => (new EventResourceService())->list(true)]);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }

    public function updateResource(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'max:50'],
                'description' => ['nullable', 'max:255'],
                // 'file_type' => ['required', 'max:50'],
                // 'link' => ['required', 'max:50']
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }
            $resourceRecord = (new EventResourceService())->update($id, $request->all());

            $status = !empty($resourceRecord) ? API::SUCCESS : API::FAIL;
            return API::response($status, $resourceRecord);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function deleteResource(Request $request, $id)
    {
        try {
            $company = (new EventResourceService())->delete($id);
            if($company) {
                return API::response(API::SUCCESS, ['message' => 'resource has been deleted.']);
            } else {
                return API::response(API::FAIL, ['message' => 'Error while deleting resource.']);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function uploadFileResource(Request $request)
    {
        try {
        if($request->profileImage) {
            $base64Str = $request->profileImage;
            //print_r($base64Str); exit;
            $fileName = 'test_'.uniqid('resource_image_') . '.png';

            $url = (new S3Service())->uploadFile($base64Str, $fileName, S3Service::CONTENT_TYPES['png'], 'base64');

            $data['file'] = $url;
            
        }elseif($request->file)
        {
            $extension = $request->file('file')->getClientOriginalExtension();

            if ($extension === 'pdf') {     
                    $fileName = 'test_'.uniqid('resource_file_') . '.pdf';
                    $url = (new S3Service())->uploadPdfFile($request->file, $fileName, S3Service::CONTENT_TYPES['pdf']);
                    $data['file'] = $url;
            } elseif ($extension === 'mp4') {
                        $fileName = 'test_'.uniqid('resource_video_') . '.mp4';
                        $url = (new S3Service())->uploadPdfFile($request->file, $fileName, S3Service::CONTENT_TYPES['mp4']);
                        $data['file'] = $url;
            } 
            
        }
       
            // (new EventResourceService())->store( $data);
            
            return API::response(API::SUCCESS, ['file' => $url]);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function ResourceLayoutSection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // 'section_title' => ['required', 'max:50'],
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }
            $data = [
                'section_title' => $request->section_title,
                'section_types' => $request->section_types,
            ];
            $resourceLayout = (new EventResourceService())->storeResourceLayout($data);

            return API::response(API::SUCCESS, ['resourceLayout' => $resourceLayout]);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function ResourceLayoutSectionlist(Request $request){
        try {
            return API::response(API::SUCCESS, ['ResourceLayoutList' => (new EventResourceService())->resourceLayoutList(true)]);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }

    public function updateResourceLayoutSection(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'max:50'],
                // 'description' => ['nullable', 'max:255'],
                // 'file_type' => ['required', 'max:50'],
                // 'link' => ['required', 'max:50']
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }
            $resourceRecord = (new EventResourceService())->updateResourceLayout($id, $request->all());

            $status = !empty($resourceRecord) ? API::SUCCESS : API::FAIL;
            return API::response($status, $resourceRecord);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function getUrlContent(Request $request) {
        try {
            $url = $request->srcUrl;
            if($url) {
                $content = file_get_contents($url);
                return API::response(API::SUCCESS, ['content' => $content]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

}
