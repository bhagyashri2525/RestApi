<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Utils\ApiService as API;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Event;
use App\Models\CompanyTokens;
use App\Models\EventUser;
use App\Models\User;
use App\Services\Event\EventService;
use App\Services\Event\EventEmailLogService;
use DateTime;
use App\Services\Company\ZoomService;
use App\Services\Email\MailgunApiService;

class ReportController extends Controller
{
    public function getParticipants(Request $request)
    {
        try {
        
            $validator = Validator::make($request->all(), [
                'token' => ['required', 'max:50'],
                'event_id' => ['required', 'max:50'],
                // 'is_active' => ['required', 'integer', 'min:0', 'max:1'],
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }
            $token =$request->token;
            $event_id=$request->event_id;
            
            echo($token);exit();

            $tokencheck = CompanyTokens::where(['token' => $token])->first();
            $eventcheck = Event::where(['id' => $event_id])->first();

            $status =$tokencheck->is_active;

            if ($status==false) {
                $alert = API::alert('warning', 'Token is not Active.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            if (empty($tokencheck)) {
                $alert = API::alert('warning', 'token not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            if (empty($eventcheck)) {
                $alert = API::alert('warning', 'event not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            $eventcheck = EventUser::where(['event_id' => $event_id])->all();
            return API::response(API::SUCCESS, ['event_user' => $eventcheck]);
        
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function getEventDetails(Request $request)
    {
        try {
            $token =$request->token;
            $tokencheck = CompanyTokens::where(['token' => $token])->first();
            $tokencheck->is_active=0;
            $status =$tokencheck->is_active;
    
            if ($status==false) {
                $alert = API::alert('warning', 'Token is not Active.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }
            if (empty($tokencheck)) {
                $alert = API::alert('warning', 'token not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }
            $event_id=$request->event_id;
            $eventList = Event::where(['id' => $event_id])->first();
            return API::response(API::SUCCESS, ['eventList' => $eventList]);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }

    public function getMailLogs(Request $request)
    {
        try {
        $date = $request->date;
        $result = (new MailgunApiService())->getLogs($date);
        return API::response(API::SUCCESS, ['list' => $result], 'get logs');

        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }

    public function eventUserEngagement(Request $request)
    {
        try {
            $eventUsers = $socialMediaGroups = $userRegData = $userLocations = null;
            $event_id=$request->event_id;
            $user_id=$request->user_id;
            $company_id=$request->company_id;
            
            $usercheck = User::where(['id' => $user_id])->first();
            $company = Company::where(['id' => $company_id])->first();
            $eventId = Event::where(['id' => $event_id])->first();

            if (empty($usercheck)) {
                $alert = API::alert('warning', 'Unauthorized User.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }
            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }
            if (empty($eventId)) {
                $alert = API::alert('warning', 'Record not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            $eventUsers = (new EventService())->listEventUsers($eventId);
            if (!$eventUsers) {
                return API::response(API::FAIL, ['alert' => 'Users not found.']);
            }

            foreach ($eventUsers as $user) {
                if (isset($user->details['source'])) {
                    if (isset($socialMediaGroups[$user->details['source']])) {
                        $socialMediaGroups[$user->details['source']] += 1;
                    } else {
                        $socialMediaGroups[$user->details['source']] = 1;
                    }
                }
                if (isset($user->details['country']) && (!empty($user->details['country']) || $user->details['country'] != null)) {
                    if (isset($userLocations[$user->details['country']])) {
                        $userLocations[$user->details['country']] += 1;
                    } else {
                        $userLocations[$user->details['country']] = 1;
                    }
                }
                $createdAt = new DateTime($user->created_at);
                $monthYear = $createdAt->format('M Y');

                if (!isset($userRegData[$monthYear])) {
                    $userRegData[$monthYear] = 1;
                } else {
                    $userRegData[$monthYear]++;
                }
            }
            if ($socialMediaGroups && is_array($socialMediaGroups) && count($socialMediaGroups)) {
                $socialMediaGroups = [
                    'media' => array_keys($socialMediaGroups),
                    'mediaCounter' => array_values($socialMediaGroups),
                ];
            }

            return API::response(API::SUCCESS, ['socialMediaGroups' => $socialMediaGroups, 'userRegData' => $userRegData, 'userLocations' => $userLocations]);

        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }

    public function eventLiveUsers(Request $request)
    {
        try {
            $event_id=$request->event_id;
            $user_id=$request->user_id;
            $company_id=$request->company_id;
                        
            $usercheck = User::where(['id' => $user_id])->first();
            $company = Company::where(['id' => $company_id])->first();
            $event = Event::where(['id' => $event_id])->first();

            if (empty($usercheck)) {
                $alert = API::alert('warning', 'Unauthorized User.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }
            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            if (empty($event)) {
                $alert = API::alert('warning', 'Record not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }


            if ($event && $company) {
                $refreshToken = @$company->zoom_integrations[0]['refresh_token'];
                if($refreshToken){
                    $accessToken = (new ZoomService())->fetchTokenUsingRefreshToken($refreshToken);
                    $meetingId = $event->integration['id'];
                    $result = (new ZoomService())->fetchMeetingParticipantsReport($accessToken['access_token'],$meetingId);
                    return API::response(API::SUCCESS, ['participantsReports' => $result]);
                }
            }

            return API::response(API::SUCCESS, ['result' => []]);
          
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }
}
