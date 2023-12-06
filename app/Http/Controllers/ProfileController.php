<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\Utils\ApiService as API;
use App\Models\User;
use App\Data\UserData;
use App\Services\Auth\UserService;
use App\Services\Uploads\S3Service;


class ProfileController extends Controller
{
    
    //profile Information
    public function personlInfo(Request $request)
    {
        try {
            $user = Auth::user();
            $user = UserData::from($user);
 
            $alert = API::alert('success', ' information was saved successfully.');       
            return API::response(API::SUCCESS, ['user' => $user, 'alert' => $alert]);

        } catch (Exception $e) {

            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                echo "Line no: ". $e->getLine();
            }
            return API::response(API::ERROR);
        }
    }

    public function saveProfileDetails(Request $request) {
        $details = [];
        $user = [];
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'display_name' => ['required', 'max:50'],
                'userbio' => ['nullable', 'max:255'],
                'companyname' => ['nullable', 'max:50'],
                'cwebsite' => ['nullable', 'url'],
                'social-tw' => ['nullable'],
                'social-lin' => ['nullable']
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            if(isset($request->userbio) && !empty($request->userbio)) {
                $details['bio'] = $request->userbio;
            }
            
            if(isset($request->companyname) && !empty($request->companyname)) {
                $details['companyName'] = $request->companyname;
            }

            if(isset($request->cwebsite) && !empty($request->cwebsite)) {
                $details['companySite'] = $request->cwebsite;
            }
            
            if($request->input('social-tw') !== NULL) {
                $details['social']['twitter'] = $request->input('social-tw');
            }
            
            if($request->input('social-lin') !== NULL) {
                $details['social']['linkedIn'] = $request->input('social-lin');
            }

            $userId = $request->user()->id;
            $user = User::find($userId);
            if(!empty($user)) {
                $user->update([
                    'display_name' => $request->input('display_name'),
                    'details' => $details
                ]);
                $user->save();
                $alert = API::alert('success', ' User details updated.');       
                return API::response(API::SUCCESS, ['user' => $user, 'alert' => $alert]);
            } else {
                $alert = API::alert('error', ' User not found.');       
                return API::response(API::SUCCESS, ['user' => $user, 'alert' => $alert]);
            }

        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }

    public function uploadFile(Request $request){
        $authUser = $request->user();

        $base64Str = $request->profileImage;
        // $url = $request->url;


        //print_r($base64Str); exit;
        $fileName = 'test_'.uniqid('profile_image_') . '.png';
        $url = (new S3Service())->uploadFile($base64Str, $fileName,S3Service::CONTENT_TYPES['png'],'base64');
        $data['display_profile_url'] = $url;
        (new UserService())->update($authUser->id, $data);
        
        return API::response(API::SUCCESS, ['display_profile_url' => $url]);
    }  



}