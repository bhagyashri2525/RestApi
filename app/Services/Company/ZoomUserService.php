<?php

namespace App\Services\Company;

use App\Models\PreConnection;
use App\Services\Utils\CommonService as UtilsCommonService;
use GuzzleHttp\Client;

class ZoomUserService
{

    public function getAuthUrl(){
        $zoomOauthUrl = 'https://zoom.us/oauth/authorize?response_type=code&client_id=' . env('ZOOM_USER_CLIENT_ID') . '&redirect_uri=' . env('ZOOM_USER_REDIRECT_URL');
        return $zoomOauthUrl;
    }

    public function fetchToken($code){

        // Instantiate Guzzle HTTP client
        $client = new Client();

        // Make a POST request to the Zoom OAuth token endpoint
        $response = $client->post('https://zoom.us/oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => env('ZOOM_USER_CLIENT_ID'),
                'client_secret' => env('ZOOM_USER_SECRET'),
                'redirect_uri' => env('ZOOM_USER_REDIRECT_URL'),
            ],
        ]);

        // Get the response body
        $body = $response->getBody();

        // Convert the response to JSON
        $data = json_decode($body, true);
        //print_r($data);
        return $data;
    }

    public function fetchUserDetails($token){

        $client = new Client();
        $zoomUrl = 'https://api.zoom.us/v2/users/me';
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" =>
                    "Bearer " . $token,
            ],
        ]);
        //print_r($response->getBody()->getContents());
        return json_decode($response->getBody()->getContents(), true);
    }

    public function addPreConnection($data){
        $data['id'] = (new UtilsCommonService)->generatePrimaryKey();
        $model = new PreConnection($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }
}