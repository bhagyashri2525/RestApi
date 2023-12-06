<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\Utils\ApiService as API;
use App\Models\Company;
use App\Services\Company\CompanyService;
use App\Services\Company\ZoomService;
use App\Services\Event\EventService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event = Event::all();
        if($event->count() > 0) {
            return response()->json([
                'status' => 200,
                'event list' => $event
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'event list' => 'record not found'
            ], 404);
        }
    }

    public function store(Request $request, $companyId)
    {
        try {
            
            $cleanupData = [];
            $eventType = $request->input('type');
            $eventName = $request->input('name');

            $authUser = $request->user();
            $company = (new CompanyService())->details($companyId);
            
            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }
               
            $compType = strtolower($company->type);
            if (isset($compType) && $compType == Company::COMPANY_TYPES['trial']) {
                $events = Event::where(['company_id' => $company->id])
                    ->whereNull('parent_id')
                    ->get();
                if (isset($events) && count($events) >= Event::MAX_DUMMY_EVENTS_COUNT) {
                    return API::response(API::FAIL, [], 'Record creation limit reached!');
                }
            }

            // $request->transformData();
            $data = (new EventService())->prepareCreateEventData($request->all(), $companyId, $authUser->id);
            // $cleanupData = $request->eventTypeWiseDataFilter($eventType);
            $data = array_merge($cleanupData, $data);

            if (!empty($data)) {
                //zoom individual account setting
                $createMethod = $data['create_method'];
                $intData = null;
                $zoomEvent = null;

                if($eventType == Event::TYPE_SINGLE &&  (($data['category'] == 'RTD' && $createMethod == Event::INDIVIDUAL_ZOOM_CREATE_METHOD) || $data['category'] == "ZOOM_ONLY")){

                    $paramData = (object)['start_datetime' => $data['start_datetime'], 'end_datetime' => $data['end_datetime'],'company_id' => $companyId,'create_method' => $createMethod, 'user_id' => $authUser->id];

                    if($createMethod == Event::INDIVIDUAL_ZOOM_CREATE_METHOD){
                        $paramData->user_id = null;
                    }else if($createMethod == Event::STREAMON_CREATE_METHOD){
                        $paramData->company_id = null;
                    }

                    $zoomEmail = (new EventService)->getValidZoomPullEmail($paramData);
                    if(!empty($zoomEmail)){
                        $refreshToken = (new CompanyService)->getCompanyRefreshToken($createMethod,$companyId);
                        try {
                            if ($refreshToken) {
                                $accessTokenRes = (new ZoomService())->fetchTokenUsingRefreshToken($refreshToken);
                                if (!empty($accessTokenRes)) {
                                    $approvalType = 2;
                                    $zoomEvent = (new ZoomService())->createZoomMeeting($accessTokenRes['access_token'], $zoomEmail, (object)$data, $approvalType);
                                }
                            }
                        } catch (Exception $e) {
                            return API::response(API::FAIL, ['message' => '<p>
                                Please contact the administrator regarding the following issues:
                                    <ul>
                                        <li>The Zoom pull accounts may have been exhausted.</li>
                                        <li>There is a scheduling conflict with another event.</li>
                                    </ul>
                                </p>'
                            ]);
                        }
                    }else{
                        return API::response(API::FAIL, ['message' => '<p>
                        Please contact the administrator regarding the following issues:
                            <ul>
                                <li>The Zoom pull accounts may have been exhausted.</li>
                                <li>There is a scheduling conflict with another event.</li>
                            </ul>
                        </p>'
                    ]);
                    }
                }
                 
                $data['global_partition_key'] = 1;
                $event = (new EventService())->createNewEvent($data);
                if(!empty($event) && !empty($zoomEvent)){
                    $zoomInt = (new EventService())->saveZoomIntegration($event->id, $zoomEvent);
                    if($zoomInt) {
                        $intData = [
                            'integration_type' => 'zoom',
                            'integration_account' => $zoomEmail
                        ];
                        
                        (new EventService)->updateEvent($intData, $event->id);
                    }
                }

                // $redirectTo = $request->redirectTo;
                // if($redirectTo == "update") {
                //     $eventDashboardUrl = route('update-event-view', ['companyId' => $companyId, 'eventId' => $event->id]);
                // } elseif($redirectTo == "next") {
                //     $routeName = "event-integration-view";
                //     $category = isset($event->category) ? $event->category : false;
                //     if($category && $category == "ZOOM_ONLY") {
                //         $routeName = "email-log-view";
                //     }
                    // $eventDashboardUrl = route($routeName, ['companyId' => $companyId, 'eventId' => $event->id]);
                // }
                $alert = API::alert('success', 'Record created successfully.');
                return API::response(API::SUCCESS, ['alert' => $alert]);
            } else {
                return API::response(API::FAIL, [], 'Something went wrong.');
            }
            
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }

    public function update(Request $request, $companyId, $eventId)
    {
        try {
            if (empty($companyId)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            $authUser = $request->user();
            $isAdmin = false;
            $role = null;

            $company = (new CompanyService())->details($companyId);

            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            } else {
                $data = "";
                $eventType = $request->input('type');
                $eventCountValidationStr = 'required_if:type,' . Event::TYPE_MULTIPLE;
                $validator = Validator::make($request->all(), [
                    'name' => ['required', 'max:256'],
                    'description' => ['nullable', 'max:3000'],
                    'department_id' => ['required'],
                    'timezone' => [
                        Rule::requiredIf(function () use ($request) {
                            return $request->input('type') == Event::TYPE_SINGLE;
                        }),
                    ],
                    'start_datetime' => [
                        Rule::requiredIf(function () use ($request) {
                            return $request->input('type') == Event::TYPE_SINGLE;
                        }),
                    ],
                    'duration' => [
                        Rule::requiredIf(function () use ($request) {
                            return $request->input('type') == Event::TYPE_SINGLE;
                        }),
                    ],
                    'event_count' => [$eventCountValidationStr],
                    'publish' => ['required'],
                    'create_method' => ["required"],
                ]);

                if ($validator->fails()) {
                    return API::response(API::FAIL, [], $validator->messages()->first());
                }

                // need to add role wise validations

                // need to add integration validations

                //$request->request->add(['is_active' => $request->is_active == 1 ? true : false])
                $request->request->add(['is_active' => true, 'publish' => ($request->publish == 1 ? true : false)]);
                if ($eventType == Event::TYPE_SINGLE) {
                    $request->request->remove('event_count');
                    $data = (new EventService())->prepareUpdateEventData($request->all());
                } elseif ($eventType == Event::TYPE_MULTIPLE) {
                    $data = $request->only(['name', 'description', 'department_id', 'type', 'event_count', 'is_active', 'publish']);
                } else {
                    $request->request->remove('event_count');
                    $data = $request->only(['name', 'description', 'department_id', 'type', 'category', 'is_active', 'publish']);
                }

                $event = (new EventService())->updateEvent($data, $eventId);

                if (!empty($event)) {
                    if (!empty($event->category) && ($event->category == 'RTD' || $event->category == "ZOOM_ONLY") && 
                    !empty($event->integration_type) && $event->integration_type == 'zoom' &&
                    !empty($event->integration_account) &&
                    !empty($event->integration)
                    ) {
                        $zoomEmail = $event->integration_account;
                        
                        // $createMethod = $request->create_method;
                        $createMethod = $event->create_method;
                        if($createMethod == 'stream-on'){
                            $refreshToken = (new CompanyService)->getCompanyRefreshToken($createMethod);
                        }else{
                            $refreshToken = (new CompanyService)->getCompanyRefreshToken($createMethod,$companyId);
                        }
                        if ($refreshToken) {
                            $accessTokenRes = (new ZoomService())->fetchTokenUsingRefreshToken($refreshToken);
                            if (!empty($accessTokenRes)) {
                                $approvalType = 2; //temp
                                //$zoomEvent = (new ZoomService)->createZoomMeeting($accessTokenRes['access_token'],$zoomEmail,$event,$approvalType);
                                (new ZoomService())->updateZoomMeeting($accessTokenRes['access_token'], $event, $approvalType);
                                // $zoomEvent = (new ZoomService())->fetchMeeting($accessTokenRes['access_token'], $event->integration['id']);
                                // (new EventService())->saveZoomIntegration($event->id, $zoomEvent);
                            }
                        }
                    }
                    $eventDashboardUrl = "";
                    $redirectTo = $request->redirectTo;
                    if($redirectTo == "update") {
                        $eventDashboardUrl = route('update-event-view', ['companyId' => $companyId, 'eventId' => $event->id]);
                    } elseif($redirectTo == "next") {
                        $routeName = "event-integration-view";
                        $category = isset($event->category) ? $event->category : false;
                        if($category && $category == "ZOOM_ONLY") {
                            $routeName = "email-log-view";
                        }
                        $eventDashboardUrl = route($routeName, ['companyId' => $companyId, 'eventId' => $event->id]);
                    }
                    $alert = API::alert('success', 'Record updated successfully.');
                    return API::response(API::SUCCESS, ['alert' => $alert, 'url' => $eventDashboardUrl]);
                } else {
                    return API::response(API::FAIL, [], 'Something went wrong.');
                }
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }

  
    public function show($eventId)
    {
        $event = Event::find($eventId);
        if($event) {
            return response()->json([
                'status' => 200,
                'event' => $event
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "no such event found!"
            ], 404);
        }
    }

    public function destroy( $eventId)
    {
        $event = Event::find($eventId);
        if($event) {
            $event->delete();
            return response()->json([
                'status' => 200,
                'message' => "event deleted."
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "event not deleted."
            ], 404);
        }
    }
}
