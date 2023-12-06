<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use App\Services\Utils\CommonService;
use Exception;
use GuzzleHttp\Client;
use BaoPham\DynamoDb\Facades\DynamoDb;
use DateTime;
use DateTimeZone;

class ZoomService
{

    public function fetchTokenX($code){
        $client = new Client();
        $response = $client->post('https://zoom.us/oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' =>  $code,
                'redirect_uri' => env('ZOOM_USER_REDIRECT_URL'),
                'client_id' => env('ZOOM_CLIENT_ID'),
                'client_secret' => env('ZOOM_SECRET'),
            ],
        ]);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        //print_r($data); exit;
        return $data;
    }
    
    public function fetchToken($code){

        // Instantiate Guzzle HTTP client
        $client = new Client();

        // Make a POST request to the Zoom OAuth token endpoint
        $response = $client->post('https://zoom.us/oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => env('ZOOM_REDIRECT_URL'),
                'client_id' => env('ZOOM_CLIENT_ID'),
                'client_secret' => env('ZOOM_SECRET'),
            ],
        ]);

        // Get the response body
        $body = $response->getBody();

        // Convert the response to JSON
        $data = json_decode($body, true);
        //print_r($data);
        return $data;
    }
    public function fetchTokenY($code){

        $client = new Client();
        $zoomUrl = 'https://zoom.us/oauth/token';
        
        $response = $client->request('POST', $zoomUrl, [
            "headers" => [
                "Authorization" =>
                    "Basic " . base64_encode(env('ZOOM_CLIENT_ID') . ":" . env('ZOOM_SECRET')),
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => env('ZOOM_USER_REDIRECT_URL'),
                //'client_secret' => env('ZOOM_USER_SECRET'),
                //'code_verifier' => 'streamon_12345',
            ]
        ]);
        //print_r($response->getBody()->getContents());
        return json_decode($response->getBody()->getContents(), true);
    }

    public function fetchTokenUsingRefreshToken($refreshToken){
        $client = new Client();
        $zoomUrl = 'https://zoom.us/oauth/token';
        
        $response = $client->request('POST', $zoomUrl, [
            "headers" => [
                "Authorization" =>
                    "Basic " . base64_encode(env('ZOOM_CLIENT_ID') . ":" . env('ZOOM_SECRET')),
            ],
            'form_params' => [
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createZoomMeeting($accessToken,$email,$event,$approvalType = 2){

        $client = new Client();
        $zoomUrl = "https://api.zoom.us/v2/users/$email/meetings";
        
        $response = $client->request('POST', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ],
            'json' => [
                "topic" => $event->name, // event name
                "agenda" => $event->description,
                "type" => 2, // scheduled meeting
                "timezone" => $event->timezone,
                "start_time" => (new CommonService)->dateTimeIntoZoomDateTimeFormat($event->timezone_start_datetime),
                "duration" => $event->duration, // in minutes
                "password" => (new CommonService)->generateZoomPassword(),
                "settings" => [
                    "registrants_confirmation_email" => false,
                    //"approval_type" => $approvalType, // 2 => No Registration Required (public), 0 => Automatically Approve (private) 
                    "approval_type" => 0, // 2 => No Registration Required (public), 0 => Automatically Approve (private) 
                    "registrants_email_notification" => false,
                    'registration_type' => 1,
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function updateZoomMeeting($accessToken,$event,$approvalType = 0){
        $integration = $event->integration;
        #$startTime = 

        $client = new Client();
        $zoomUrl = 'https://zoom.us/v2/meetings/'.$integration['id'];
        
        $response = $client->request('PATCH', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ],
            'json' => [
                "topic" => $event->name,// event name
                "agenda" => $event->description,
                "type" => 2, // // scheduled meeting
                "start_time" => (new CommonService)->dateTimeIntoZoomDateTimeFormat($event->timezone_start_datetime),
                "timezone" => $event->timezone,
                "duration" => $event->duration, // in minutes
                "settings" => [
                    "registrants_confirmation_email" => false,
                    "approval_type" => $approvalType,
                    "registrants_email_notification" => false,
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
    
    public function fetchMeeting($accessToken,$meetingId){
        $client = new Client();
        $zoomUrl = 'https://zoom.us/v2/meetings/'.$meetingId;
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }

    public function addRegistrant($accessToken,$meetingId,$user){
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/meetings/$meetingId/registrants";
        
        $response = $client->request('POST', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ],
            'json' => [
                "first_name" => "Mayur",// event name
                "last_name" => "Patil",
                "email" => "mayur.patil@streamontech.com", // 30 mins
                "auto_approve" => true, // password
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
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

    public function saveToken($companyId,$tokenObject){

        $company = Company::where(['id' => $companyId])->first();

        if(!empty($company)){
            $integrations = $company->zoom_integrations;
            if(!empty($integrations)){

                $index = count($integrations);
                foreach($integrations as $i => $integration){
                    if($integration['email'] == $tokenObject['email']){
                        $index = $i;
                    }
                } 

                $updateExpression = "SET #col[{$index}] = :data";
                DynamoDb::table(Company::TABLE)
                    ->setKey(DynamoDb::marshalItem(['id' => $companyId]))
                    ->setUpdateExpression($updateExpression)
                    ->setExpressionAttributeName('#col', 'zoom_integrations')
                    ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($tokenObject))
                    ->prepare()
                    ->updateItem();

            }else{
                $integrations = [];
                $integrations[] = $tokenObject;
                $company->update(['zoom_integrations' => $integrations]);
            }

            return $company->refresh();
        }
        return false;
    }
    public function saveUserToken($userId, $companyId,$tokenObject){
        $user = User::where(['id' => $userId])->first();
        if(!empty($user)){
            $integrations = [];
            $companyIndex = 0;
            $companyMapp = $user->company_mapping;
            // array_push($companyMapp, ['id'=> 745347534, 'is_active'=> false, 'department'=>[], 'integration'=>['integration_type'=>['zoom']]]);
            foreach($companyMapp as $i => $comp) {
                if($comp['id'] == $companyId) {
                    $companyIndex = $i;
                    if(array_key_exists('integrations', $comp)) {
                        //check email
                        foreach($comp['integrations'] as $key => $integration) {
                            if($integration['integration_obj']['email'] == $tokenObject['email']) {
                                $integration['integration_obj'] = $tokenObject;
                                $comp['integrations'][$key] = $integration;
                                $integrations = $comp['integrations'];
                            }
                        }
                    } else {
                        $integrations[] = [
                            "integration_type" => "zoom",
                            "integration_obj" => $tokenObject
                        ];
                    }
                    if(!empty($integrations)) {
                        $comp['integrations'] = $integrations;
                        $updateExpression = "SET #col[{$companyIndex}] = :data";
                        DynamoDb::table(User::TABLE)
                            ->setKey(DynamoDb::marshalItem(['id' => $userId]))
                            ->setUpdateExpression($updateExpression)
                            ->setExpressionAttributeName('#col', 'company_mapping')
                            ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($comp))
                            ->prepare()
                            ->updateItem();
                            break;
                    }
                }
            }

            return $user->refresh();
        }
        return false;
    }

    public function saveAdminTokens($userId, $tokenObject) {
        $user = User::where(['id' => $userId])->first();
        $toUpdateInt = [];
        if(!empty($user)){
            if($user->zoom_integrations) {
                $toUpdateInt = $user->zoom_integrations;
                array_push($toUpdateInt, $tokenObject);
            } else {
                $toUpdateInt[] = $tokenObject;
            }
            $user->update(['zoom_integrations' => $toUpdateInt]);
            return $user->refresh();
        }
        return false;
    }

    public function fetchMeetingPollReports($accessToken,$meetingId){
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/past_meetings/$meetingId/polls";
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }

    public function fetchPastMeetingPolls($accessToken,$meetingId){
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/meetings/$meetingId/polls";
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }

    public function fetchMeetingParticipantsReport($accessToken,$meetingId, $type = "live", $nextPageToken = NULL){
        $client = new Client();
        if($nextPageToken) {
            $zoomUrl = "https://zoom.us/v2/metrics/meetings/$meetingId/participants?type=$type&next_page_token=$nextPageToken";
        } else {
            $zoomUrl = "https://zoom.us/v2/metrics/meetings/$meetingId/participants?type=$type";
        }
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }

    public function fetchMeetingQnAReport($accessToken,$meetingId){
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/report/meetings/$meetingId/qa";
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }

    public function fetchMeetingOperationLogReport($accessToken,$meetingId){
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/report/operationlogs";
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }
    
    public function fetchMeetingMessagesReport($accessToken,$meetingId){
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/chat/users/me/meetings/$meetingId/messages";
        
        $response = $client->request('GET', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);  
    }

    public function getPastMeetingChats($accessToken, $meetingId) {
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/past_meetings/$meetingId/qa";

        $response = $client->get($zoomUrl, [
            'headers' => [
                "Authorization" => "Bearer $accessToken"
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function checkAccountIsGenuine($accessToken, $parentAccountId, $parentAcc, $requestedAcc) {
        if(!$parentAcc || !$requestedAcc) {
            return false;
        }
        
        if($parentAcc === $requestedAcc) {
            return true;
        }
        
        $client = new Client([
            'base_uri' => 'https://api.zoom.us/v2/',
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Accept' => 'application/json',
            ],
        ]);
    
        $response = $client->request('GET', 'users');
    
        $childAccounts = json_decode($response->getBody(), true)['users'];
    
        // Check if the email exists in the child accounts
        foreach ($childAccounts as $childAccount) {
            if ($childAccount['email'] === $requestedAcc) {
                return true;
            }
        }
        return false;
    }

    public function addRegistrantUser($accessToken,$meetingId,$user) { 
        $client = new Client();
        $zoomUrl = "https://zoom.us/v2/meetings/$meetingId/registrants";
        
        $response = $client->request('POST', $zoomUrl, [
            "headers" => [
                "Authorization" => "Bearer $accessToken",
                "Content-Type" => "application/json",
            ],
            'json' => [
                "first_name" => $user->first_name,// event name
                "last_name" => $user->last_name,
                "email" => $user->email, // 30 mins
                "auto_approve" => true, // password
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}