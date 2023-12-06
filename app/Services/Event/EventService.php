<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventUser;
use App\Services\Auth\UserService;
use App\Services\Company\ZoomIndividualService;
use App\Services\Company\ZoomService;
use App\Services\Email\MailgunApiService;
use App\Services\Utils\CommonService;
use Aws\DynamoDb\DynamoDbClient;
use BaoPham\DynamoDb\Facades\DynamoDb;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class EventService
{
    public function getEventTypeOnEventId($eventId)
    {
        $event = Event::where(['id' => $eventId])->first();
        $eventObj = null;
        if (!empty($event) && isset($event->parent_id)) {
            $eventObj = Event::where('id', $event->parent_id)->first();
        } else {
            $eventObj = $event;
        }

        return !empty($eventObj) ? $eventObj->type : null;
    }

    function list($all = false)
    {
        if ($all) {
            return Event::whereNull('parent_id')
                ->where(['is_deleted' => false])
                ->all();
        } else {
            return Event::where(['is_deleted' => false])->all();
        }
    }

    function listForCompany($companyId)
    {
        return Event::where(['company_id' => $companyId])->get();
    }

    public function fetchUsersEvents($userId)
    {
        return Event::where(['creator_id' => $userId])->get();
    }

    public function prepareCreateEventData($data, $companyId, $creatorId)
    {
        $eventSlug = (new CommonService())->generateUuidForEvent($companyId);

        if ($data['type'] == Event::TYPE_SINGLE) {
            $duration = $data['duration'];
            $startDateTime = "{$data['start_datetime']}:00";
            $endDateTime = (new CommonService())->addMinutes(new DateTime($startDateTime), $duration)->format('Y-m-d\TH:i:00');

            // convert start datetime convert into UTC format
            $timezoneStartDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
            $timezoneStartDateTime = $timezoneStartDateTime->format('Y-m-d\TH:i:sO');

            $startDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
            $startDateTime->setTimezone(new DateTimeZone('UTC'));
            $eventTimeStamp = $startDateTime->getTimestamp();
            $startDateTime = $startDateTime->format('Y-m-d\TH:i:sO');

            // convert end datetime convert into UTC format
            $timezoneEndDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
            $timezoneEndDateTime = $timezoneEndDateTime->format('Y-m-d\TH:i:sO');

            $endDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
            $endDateTime->setTimezone(new DateTimeZone('UTC'));
            $endDateTime = $endDateTime->format('Y-m-d\TH:i:sO');

            $currentTimeUTC = new DateTime('now', new DateTimeZone('UTC'));

            $data['start_datetime'] = $startDateTime;
            $data['asc_event_timestamp'] = $eventTimeStamp;
            $data['asc_creation_timestamp'] = $currentTimeUTC->getTimestamp();
            $data['desc_event_timestamp'] = -($eventTimeStamp);
            $data['desc_creation_timestamp'] = -($currentTimeUTC->getTimestamp());
            $data['end_datetime'] = $endDateTime;
            $data['duration'] = $duration;

            $data['timezone_start_datetime'] = $timezoneStartDateTime;
            $data['timezone_end_datetime'] = $timezoneEndDateTime;
        } else {
            $currentTimeUTC = new DateTime('now', new DateTimeZone('UTC'));
            $data['asc_event_timestamp'] = 0;
            $data['asc_creation_timestamp'] = $currentTimeUTC->getTimestamp();
            $data['desc_event_timestamp'] = 0;
            $data['desc_creation_timestamp'] = -($currentTimeUTC->getTimestamp());
        }

        $data['creator_id'] = $creatorId;
        $data['company_id'] = $companyId;
        $data['slug'] = $eventSlug;
        return $data;
    }

    public function prepareCreateSubEventData($data, $companyId, $creatorId)
    {
        $eventSlug = (new CommonService())->generateUuidForEvent($companyId);

        $duration = $data['duration'];
        $startDateTime = "{$data['start_datetime']}:00";
        $endDateTime = (new CommonService())->addMinutes(new DateTime($startDateTime), $duration)->format('Y-m-d H:i:00');

        // convert start datetime convert into UTC format
        $timezoneStartDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $timezoneStartDateTime = $timezoneStartDateTime->format('Y-m-d\TH:i:sO');

        $startDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $startDateTime->setTimezone(new DateTimeZone('UTC'));
        $eventTimeStamp = $startDateTime->getTimestamp();
        $startDateTime = $startDateTime->format('Y-m-d\TH:i:sO');

        // convert end datetime convert into UTC format
        $timezoneEndDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
        $timezoneEndDateTime = $timezoneEndDateTime->format('Y-m-d\TH:i:sO');

        $endDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
        $endDateTime->setTimezone(new DateTimeZone('UTC'));
        $endDateTime = $endDateTime->format('Y-m-d\TH:i:sO');

        $currentTimeUTC = new DateTime('now', new DateTimeZone('UTC'));

        $data['start_datetime'] = $startDateTime;
        $data['end_datetime'] = $endDateTime;
        $data['duration'] = $duration;
        $data['asc_event_timestamp'] = $eventTimeStamp;
        $data['asc_creation_timestamp'] = $currentTimeUTC->getTimestamp();
        $data['desc_event_timestamp'] = -($eventTimeStamp);
        $data['desc_creation_timestamp'] = -($currentTimeUTC->getTimestamp());

        $data['timezone_start_datetime'] = $timezoneStartDateTime;
        $data['timezone_end_datetime'] = $timezoneEndDateTime;

        $data['creator_id'] = $creatorId;
        $data['company_id'] = $companyId;
        $data['slug'] = $eventSlug;

        return $data;
    }

    public function prepareCreateEventDataOld($data, $companyId, $creatorId)
    {
        $eventSlug = (new CommonService())->generateUuidForEvent($companyId);

        $duration = $data['duration'];
        $startDateTime = "{$data['start_datetime']}:00";
        $endDateTime = (new CommonService())->addMinutes(new DateTime($startDateTime), $duration)->format('Y-m-d H:i:00');

        // convert start datetime convert into UTC format
        $timezoneStartDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $timezoneStartDateTime = $timezoneStartDateTime->format('Y-m-d\TH:i:sO');

        $startDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $startDateTime->setTimezone(new DateTimeZone('UTC'));
        $startDateTime = $startDateTime->format('Y-m-d\TH:i:sO');

        // convert end datetime convert into UTC format
        $timezoneEndDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
        $timezoneEndDateTime = $timezoneEndDateTime->format('Y-m-d\TH:i:sO');

        $endDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
        $endDateTime->setTimezone(new DateTimeZone('UTC'));
        $endDateTime = $endDateTime->format('Y-m-d\TH:i:sO');

        $data['start_datetime'] = $startDateTime;
        $data['end_datetime'] = $endDateTime;
        $data['duration'] = $duration;

        $data['timezone_start_datetime'] = $timezoneStartDateTime;
        $data['timezone_end_datetime'] = $timezoneEndDateTime;

        $data['creator_id'] = $creatorId;
        $data['company_id'] = $companyId;
        $data['slug'] = $eventSlug;

        return $data;
    }

    public function prepareCreateEventDataForProfile($data, $companyId, $creatorId)
    {
        $eventSlug = (new CommonService())->generateUuidForEvent($companyId);
        $startDateTime = "{$data['start_datetime']}:00";

        // convert start datetime convert into UTC format
        $timezoneStartDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $timezoneStartDateTime = $timezoneStartDateTime->format('Y-m-d\TH:i:sO');

        $startDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $startDateTime->setTimezone(new DateTimeZone('UTC'));
        $startDateTime = $startDateTime->format('Y-m-d\TH:i:sO');

        $data['start_datetime'] = $startDateTime;
        $data['timezone_start_datetime'] = $timezoneStartDateTime;
        $data['creator_id'] = $creatorId;
        $data['company_id'] = $companyId;
        $data['slug'] = $eventSlug;

        return $data;
    }

    public function createNewEvent($data)
    {
        $data['id'] = (new CommonService())->generatePrimaryKey();
        $model = new Event($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database

        //self::generateEventFiles($companyNameSlug, $model->name, $model->slug);
        //return $model;
    }

    public function prepareUpdateEventData($data)
    {
        //$eventSlug = (new CommonService)->generateUuidForEvent($companyId);

        $duration = $data['duration'];
        $startDateTime = "{$data['start_datetime']}:00";
        $endDateTime = (new CommonService())->addMinutes(new DateTime($startDateTime), $duration)->format('Y-m-d H:i:00');

        // convert start datetime convert into UTC format
        $timezoneStartDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $timezoneStartDateTime = $timezoneStartDateTime->format('Y-m-d\TH:i:sO');

        $startDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone']));
        $startDateTime->setTimezone(new DateTimeZone('UTC'));
        $eventTimeStamp = $startDateTime->getTimestamp();
        $startDateTime = $startDateTime->format('Y-m-d\TH:i:sO');

        // convert end datetime convert into UTC format
        $timezoneEndDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
        $timezoneEndDateTime = $timezoneEndDateTime->format('Y-m-d\TH:i:sO');

        $endDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone']));
        $endDateTime->setTimezone(new DateTimeZone('UTC'));
        $endDateTime = $endDateTime->format('Y-m-d\TH:i:sO');

        $currentTimeUTC = new DateTime('now', new DateTimeZone('UTC'));

        $data['start_datetime'] = $startDateTime;
        $data['end_datetime'] = $endDateTime;
        $data['duration'] = $duration;
        $data['asc_event_timestamp'] = $eventTimeStamp;
        $data['desc_event_timestamp'] = -($eventTimeStamp);

        $data['timezone_start_datetime'] = $timezoneStartDateTime;
        $data['timezone_end_datetime'] = $timezoneEndDateTime;

        //$data['creator_id'] = $authId;
        //$data['company_id'] = $companyId;
        //$data['slug'] = $eventSlug;
        return $data;
    }

    public function updateEvent($data, $eventId)
    {
        $event = Event::where(['id' => $eventId])->first();
        $event->update($data);
        return $event->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function update($id, $data): Event
    {
        $event = Event::where('id', $id)->first();
        if ($event) {
            $event->update($data);
            return $event->refresh();
        }
        return null;
    }

    public function delete($id): bool
    {
        $event = Event::where('id', $id)->first();
        if ($event) {
            if (!empty($event->integration_type) && $event->integration_type == 'zoom' && !empty($event->integration_account) && $event->integration) {
                // $deleteEvent = (new ZoomService())->
            }
            $event->is_deleted = true;
            $event->save();
            $childEvents = $this->getChildEvents($id);
            $promises = [];
            if ($childEvents) {
                foreach ($childEvents as $i => $childEvent) {
                    $currEvent = Event::find($childEvent->id);
                    $currEvent->is_deleted = true;
                    $currEvent->saveAsync()->wait();
                }
            }
            return true;
        }
        return false;
    }

    public function getChildEvents($eventId)
    {
        $childEvents = [];
        if ($eventId) {
            $childEvents = Event::where(['parent_id' => $eventId])->get();
        }
        return $childEvents;
    }

    public function saveZoomIntegration($eventId, $integration)
    {
        $event = Event::where(['id' => $eventId])->first();
        if ($event) {
            $event->integration = $integration;
            $event->save();
            return true;
        }
        return null;
    }

    public function saveCustomLinkIntegration(string $eventId, string $link)
    {
        $event = Event::where(['id' => $eventId])->first();
        if ($event) {
            $integration = ['link' => $link];
            $updateExpression = 'SET #col = :data';
            DynamoDb::table(Event::TABLE)
                ->setKey(DynamoDb::marshalItem(['id' => $eventId]))
                ->setUpdateExpression($updateExpression)
                ->setExpressionAttributeName('#col', 'integration')
                ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($integration))
                ->prepare()
                ->updateItem();
            return true;
        }
        return false;
    }

    public function saveIntegrationsData($eventId, $data)
    {
        $event = Event::where(['id' => $eventId])->first();
        if ($event) {
            $event->update($data);
            return true;
        }
        return false;
    }

    public function registerUser($eventId, $data, $userId = null)
    {
        $password = @$data['password'] ? Hash::make($data['password']) : Hash::make('12345678');
        
        $first_name = "Registered";
        $last_name = "User";

        if(!empty($data['full_name'])) {
            $words = explode(" ", $data['full_name']);
            $first_name = implode(" ", array_slice($words, 0, -1));
            $last_name = end($words);
            unset($data['full_name']);
        } 
        if(!empty($data['first_name'])) {
            $first_name = $data['first_name'];
            unset($data['first_name']);
        }
        if(!empty($data['last_name'])) {
            $last_name = $data['last_name'];
            unset($data['last_name']);
        }        

        // $name = @$data['full_name'];
        $email = @$data['email'];
        $data['password'] = $password;

        unset($data['email']);

        $now = new DateTime('now');
        $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sO');

        $data = array_merge(
            ['id' => (new CommonService())->generatePrimaryKey()],
            [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'event_id' => $eventId,
                'password' => $password,
                'details' => $data,
                'is_active' => true,
                'is_deleted' => false,
                'created_at' => $now,
                'status' => 1,
            ],
        );
        if ($userId) {
            $model = EventUser::where(['id' => $userId])->first();
            if ($model) {
                unset($data['email']);
                $model->update($data);
            }
        } else {
            $model = new EventUser($data);
        }

        $model->save();
        return $model->refresh();
    }

    public function getEventGoogleCalendarLink($event)
    {
        $eventTitle = 'My Event';
        $eventDescription = isset($event->description) ? $event->description : "";
        $eventLocation = 'Event Location';
        $startDate = date("Ymd\THis\Z", strtotime($event->start_datetime)); // Format: YYYY-MM-DDTHH:MM:SS
        $endDate = date("Ymd\THis\Z", strtotime($event->end_datetime)); // Format: YYYY-MM-DDTHH:MM:SS
        $baseUrl = 'https://www.google.com/calendar/render';
        $params = [
            'action' => 'TEMPLATE',
            'text' => $event->name,
            'details' => $eventDescription,
            'location' => "",
            'dates' => "{$startDate}/{$endDate}",
        ];
        $queryString = http_build_query($params);
        $eventUrl = "{$baseUrl}?{$queryString}";
        return $eventUrl;
    }

    public function prepareRegisterUserEmailData($requestPar,$event, $companySlug, $companyId){
        $date = $time = $eventPageUrl = $outLookUrl = null;
        $name = '';
        if(isset($event->start_datetime)) {
            [$date, $time] = (new CommonService)->getDateAndTimeFromString($event->timezone_start_datetime);
        }

        $googleCalendarLink = $this->getEventGoogleCalendarLink($event);
        
        // isset($event->page_publish_data) && !empty($event->page_publish_data) &&
        if(isset($event->slug) && !empty($event->slug)) {
            $eventPageUrl = route("event-site", ['companySlug' => $companySlug, 'eventSlug' => $event->slug]);
        }

        if(!empty(@$requestPar['full_name'])){
            $name = $requestPar['full_name'];
        }else if(!empty(@$requestPar['first_name']) && !empty(@$requestPar['last_name'])){
            $name = $requestPar['first_name'] ." " .$requestPar['last_name'];
        }else if(!empty(@$requestPar['first_name'])){
            $name = $requestPar['first_name'];
        }else if(!empty(@$requestPar['last_name'])){
            $name = $requestPar['last_name'];
        }

        $name = explode(" ", $name);
            foreach($name as $i=>$value) {
                $name[$i] = ucfirst($value);
            }

        $name = implode(" ", $name);
        $eventName = isset($event->name) ? $event->name : "";
        $eventDesc = isset($event->description) ? $event->description : "";
        $encryptedUnsubscribedata = Crypt::encrypt("$companyId|$event->id|{$requestPar['email']}|$eventName");

        $emailTemplateData = [
            'userName' => $name,
            'eventName' => $eventName,
            'eventDesc' => $eventDesc,
            'eventDate' => $date,
            'eventTime' => $time,
            'timezone' => @$event->timezone,
            'email' => $requestPar['email'],
            'eventPageUrl' => $eventPageUrl,
            'googleCalendarLink' => $googleCalendarLink,
            'outLookUrl' => route("download.ics", ['eventId' => $event->id, 'companySlug' => $companySlug]),
            'unsubscribedLink' => route("mailer.unsubscribe.view", ['encrypted_data' => $encryptedUnsubscribedata])
        ];

        return $emailTemplateData;
    }

    public function sendMailToRegistrant($data) {

        try{
            $subject = "Welcome to {$data['eventName']} - Registration Confirmation 🎉";
            $body = view("emails.defaults.userRegistrationConfirmation", $data)->render();
            (new MailgunApiService())->sendMail($data['email'], $subject, $body, null, null, [], null);
            return true;
        }catch(Exception $e){
            return false;
        }
    }

    public function updateUser($userId,$data){
        $event = EventUser::where(['id' => $userId])->first();
        if($event){
            $event->update($data);
            return $event->refresh();
        }
        return false;
    }

    public function login($email, $password, $eventId)
    {
        $data = [];
        $user = null;
        $expiry = null;
        $token = null;

        if ($password) {
            $user = EventUser::where(['email' => $email, 'event_id' => $eventId, 'is_active' => true, 'status' => 1])->first();
            if ($user) {
                $credentials = ['email' => $email, 'password' => $password, 'event_id' => $eventId, 'is_active' => true, 'status' => 1];
                $token = auth()
                    ->guard('event_user')
                    ->attempt($credentials);
                $expiry = (new UserService())->getAuthExpiry();
                if ($token) {
                    $data['expiry'] = $expiry;
                    $data['token'] = $token;
                    $data['user'] = $user;
                }
            }
        } else {
            $user = EventUser::where(['email' => $email, 'is_active' => true, 'status' => 1])->first();
            if (!empty($user)) {
                $data['user'] = $user;
                $token = auth()
                    ->guard('event_user')
                    ->login($user);
                $expiry = (new UserService())->getAuthExpiry();
                if ($token) {
                    $data['expiry'] = $expiry;
                    $data['token'] = $token;
                    $data['user'] = $user;
                }
            }
        }
        return $data;
    }

    public function getEventLandingPageUrl($eventId,$userId = null)
    {
        $event = Event::where('id', $eventId)->first();
        $url = '';
        if (!empty($event)) {

            if ($event->integration_type == 'zoom' && $event->integration_account == 'custom_url') {
                $url = $event->meeting_url;
            } elseif ($event->integration_type == 'zoom' && $event->integration_account != 'custom_url') {
                if($userId){
                    $eventUser = EventUser::where(['id' => $userId])->first();
                    if($eventUser && isset($eventUser->zoom_details)){
                        $url = $eventUser->zoom_details['join_url'];
                        $url = $url ? $url : $event->integration['join_url'];
                    }
                }
                $url = (null != $url) ? $url : $event->integration['join_url'];
            }
        }
        return $url;
    }

    public function getDetailUsingSlug($slug)
    {
        return Event::where(['slug' => $slug])->first();
    }

    public function generateEventFilesX($companyNameSlug, $eventName, $eventSlug)
    {
        try {
            $eventBladeFolderPath = '/resources/views/event/' . $companyNameSlug . '/' . $eventSlug;
            $eventJsFolderPath = '/public/js/event/' . $companyNameSlug . '/' . $eventSlug;

            if (!Storage::disk('disk_path')->exists($eventBladeFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventBladeFolderPath, 0775, true); //creates directory
            }

            if (!Storage::disk('disk_path')->exists($eventJsFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventJsFolderPath, 0775, true); //creates directory
            }

            $loginFileContent = Storage::disk('disk_path')->get('/resources/views/event/default/login.blade.php');
            $registerFileContent = Storage::disk('disk_path')->get('/resources/views/event/default/register.blade.php');
            $errorFileContent = Storage::disk('disk_path')->get('/resources/views/event/default/error.blade.php');
            $landingFileContent = Storage::disk('disk_path')->get('/resources/views/event/default/landing.blade.php');

            $commonJsPagePublicUrl = 'js/event/' . $companyNameSlug . '/' . $eventSlug . '/common.js';
            $landingJsPagePublicUrl = 'js/event/' . $companyNameSlug . '/' . $eventSlug . '/landing.js';

            $loginFileContent = str_replace('js/event/default/common.js', $commonJsPagePublicUrl, $loginFileContent);
            $registerFileContent = str_replace('js/event/default/common.js', $commonJsPagePublicUrl, $registerFileContent);
            $errorFileContent = str_replace('js/event/default/common.js', $commonJsPagePublicUrl, $errorFileContent);

            $landingFileContent = str_replace('js/event/default/common.js', $commonJsPagePublicUrl, $landingFileContent);
            $landingFileContent = str_replace('js/event/default/landing.js', $landingJsPagePublicUrl, $landingFileContent);

            $landingJsFileContent = Storage::disk('disk_path')->get('/public/js/event/default/landing.js');
            $commonJsFileContent = Storage::disk('disk_path')->get('/public/js/event/default/common.js');

            Storage::disk('disk_path')->put($eventBladeFolderPath . '/login.blade.php', $loginFileContent);
            Storage::disk('disk_path')->put($eventBladeFolderPath . '/register.blade.php', $registerFileContent);
            Storage::disk('disk_path')->put($eventBladeFolderPath . '/error.blade.php', $errorFileContent);
            Storage::disk('disk_path')->put($eventBladeFolderPath . '/landing.blade.php', $landingFileContent);

            Storage::disk('disk_path')->put($eventJsFolderPath . '/landing.js', $landingJsFileContent);
            Storage::disk('disk_path')->put($eventJsFolderPath . '/common.js', $commonJsFileContent);

            return true;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
                echo '  LINE NO:: ' . $e->getLine();
            }
            return false;
        }
    }

    public function cloneDefaultFilesWithOperationFiles()
    {
        try {
            $loginFileContent = Storage::disk('disk_path')->get('/resources/views/default/login.blade.php');
            $registerFileContent = Storage::disk('disk_path')->get('/resources/views/default/register.blade.php');
            $errorFileContent = Storage::disk('disk_path')->get('/resources/views/default/error.blade.php');
            $landingFileContent = Storage::disk('disk_path')->get('/resources/views/default/landing.blade.php');

            $eventBladeFolderPath = '/resources/views/event/default';
            $eventJsFolderPath = '/public/js/event/default';

            if (!Storage::disk('disk_path')->exists($eventBladeFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventBladeFolderPath, 0775, true); //creates directory
            }

            if (!Storage::disk('disk_path')->exists($eventJsFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventJsFolderPath, 0775, true); //creates directory
            }

            $landingJsFileContent = Storage::disk('disk_path')->get('/public/js/default/landing.js');
            $commonJsFileContent = Storage::disk('disk_path')->get('/public/js/default/common.js');

            Storage::disk('disk_path')->put($eventBladeFolderPath . '/login.blade.php', $loginFileContent);
            Storage::disk('disk_path')->put($eventBladeFolderPath . '/register.blade.php', $registerFileContent);
            Storage::disk('disk_path')->put($eventBladeFolderPath . '/error.blade.php', $errorFileContent);
            Storage::disk('disk_path')->put($eventBladeFolderPath . '/landing.blade.php', $landingFileContent);

            Storage::disk('disk_path')->put($eventJsFolderPath . '/landing.js', $landingJsFileContent);
            Storage::disk('disk_path')->put($eventJsFolderPath . '/common.js', $commonJsFileContent);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
                echo '  LINE NO:: ' . $e->getLine();
            }
            return false;
        }
    }

    public function details($eventId, $companyId)
    {
        $event = Event::where('id', $eventId)->first();
        return $event;
    }

    public function prepareSubEventData($data)
    {
        $duration = $data['duration'];
        $startDateTime = "{$data['start_datetime']}:00";
        $userSelectedDateTime = new DateTime($startDateTime, new DateTimeZone($data['timezone'])); // timezone india

        $data['start_datetime'] = $userSelectedDateTime->format('Y-m-d\TH:i:sO');

        $utcStartDateTime = $userSelectedDateTime->setTimezone(new DateTimeZone('UTC')); // utc
        $data['timezone_start_datetime'] = $utcStartDateTime->format('Y-m-d\TH:i:sO');

        $endDateTime = (new CommonService())->addMinutes(new DateTime($startDateTime), $duration)->format('Y-m-d H:i:00');
        $userSelectedEndDateTime = new DateTime($endDateTime, new DateTimeZone($data['timezone'])); // timezone india

        $data['end_datetime'] = $userSelectedEndDateTime->format('Y-m-d\TH:i:sO');

        $utcEndDateTime = $userSelectedEndDateTime->setTimezone(new DateTimeZone('UTC')); // utc
        $data['timezone_end_datetime'] = $utcEndDateTime->format('Y-m-d\TH:i:sO');
        return $data;
    }

    public function removeSubEvent($eventId)
    {
        $event = Event::find($eventId);
        if ($event) {
            return $event->deleteAsync()->wait();
        }
    }

    public function generateEventFiles($companyId, $eventId, $template, $companyNameSlug, $eventSlug)
    {
        try {
            $event = Event::where('id', $eventId)->first();
            $eventBladeFolderPath = '/resources/views/event/' . $companyNameSlug . '/' . $eventSlug;
            $eventJsFolderPath = '/public/js/event/' . $companyNameSlug . '/' . $eventSlug;
            $eventPublicJsFolderPath = '/js/event/' . $companyNameSlug . '/' . $eventSlug;

            if (!Storage::disk('disk_path')->exists($eventBladeFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventBladeFolderPath, 0775, true); //creates directory
            }

            if (!Storage::disk('disk_path')->exists($eventJsFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventJsFolderPath, 0775, true); //creates directory
            }

            $templatePath = 'eventPages.templates.' . $template . '.output';

            $htmlContent = View::make($templatePath, [
                'data' => $event->page_publish_data,
                'eventSlug' => $event->slug,
                'companySlug' => $companyNameSlug,
            ])->render();

            $landingJsFileContent = Storage::disk('disk_path')->get('/public/js/event/default/index.js');
            Storage::disk('disk_path')->put($eventJsFolderPath . '/index.js', $landingJsFileContent);

            $assetUrl = asset($eventPublicJsFolderPath . '/index.js');
            $jsTag = '<script src="' . $assetUrl . '"></script>';
            $htmlContent = str_replace('###EVENT_REGISTER_JS###', $jsTag, $htmlContent);

            Storage::disk('disk_path')->put($eventBladeFolderPath . '/index.blade.php', $htmlContent);

            return true;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
                echo '  LINE NO:: ' . $e->getLine();
            }
            return false;
        }
    }

    public function generateEventFilesUsingJson($htmlContent, $companyNameSlug, $eventSlug)
    {
        try {
            $eventBladeFolderPath = '/resources/views/event/' . $companyNameSlug . '/' . $eventSlug;
            $eventJsFolderPath = '/public/js/event/' . $companyNameSlug . '/' . $eventSlug;
            $eventPublicJsFolderPath = '/js/event/' . $companyNameSlug . '/' . $eventSlug;

            if (!Storage::disk('disk_path')->exists($eventBladeFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventBladeFolderPath, 0775, true); //creates directory
            }

            if (!Storage::disk('disk_path')->exists($eventJsFolderPath)) {
                Storage::disk('disk_path')->makeDirectory($eventJsFolderPath, 0775, true); //creates directory
            }

            $landingJsFileContent = Storage::disk('disk_path')->get('/public/js/event/default/index.js');
            Storage::disk('disk_path')->put($eventJsFolderPath . '/index.js', $landingJsFileContent);

            $assetUrl = asset($eventPublicJsFolderPath . '/index.js');
            // $jsTag = '<script src="' . $assetUrl . '"></script>';
            // $htmlContent = str_replace('###EVENT_REGISTER_JS###', $jsTag, $htmlContent);

            Storage::disk('disk_path')->put($eventBladeFolderPath . '/index.blade.php', $htmlContent);

            return true;
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
                echo '  LINE NO:: ' . $e->getLine();
            }
            return false;
        }
    }

    public function prepareEventShortLinksData($data)
    {
        if ($data) {
            $id = (new CommonService())->generatePrimaryKey();
            $data['id'] = $id;
            return $data;
        } else {
            return false;
        }
    }

    public function listEventUsers($eventId)
    {
        if ($eventId) {
            return EventUser::where(['event_id' => $eventId, 'is_active' => true, 'is_deleted' => false])->get();
        } else {
            return [];
        }
    }

    public function manageUser($userId, $status)
    {
        if ($userId) {
            $user = EventUser::find($userId);
            if (!$user) {
                return false;
            }
            if ($status == 1) {
                $user->update(['status' => 1]);
            } elseif ($status == 2) {
                $user->update(['status' => 2]);
            }
            return $user->refresh();
        }
        return false;
    }

    public function zoomSync($accessToken, $email, $approvalType, $eventData, $type = 'create')
    {
        if ($type == 'create') {
            $zoomEvent = (new ZoomService())->createZoomMeeting($accessToken, $email, $eventData, $approvalType);
        } else {
            (new ZoomService())->updateZoomMeeting($accessToken, $eventData, $approvalType);
            $zoomEvent = (new ZoomService())->fetchMeeting($accessToken, $eventData->integration['id']);
        }
        return $zoomEvent;
    }

    public function saveEmailCommunicationPref($data, $eventId)
    {
        if (!$eventId) {
            return false;
        }
        $event = Event::find($eventId);
        if ($event) {
            $event->update($data);
            return true;
        }
        return false;
    }

    public function getEmailCommunicationPref($eventId)
    {
        if (!$eventId) {
            return [];
        }
        $event = Event::find($eventId, ['is_registartion_confrimation', 'is_consent', 'is_mute_notification']);
        if ($event) {
            return $event;
        }
        return [];
    }

    public function fetchEventsOnDateTime($startDateTime, $endDateTime, $createdMethod, $companyId = null, $userId = null)
    {
        // $results = DynamoDb::table('events')
        //     ->setFilterExpression('#start_datetime BETWEEN :lower1 AND :upper1 OR #end_datetime BETWEEN :lower1 AND :upper1')
        //     ->setProjectionExpression('id, start_datetime, end_datetime, integration_account')
        //     ->setExpressionAttributeNames(['#start_datetime' => 'start_datetime', '#end_datetime' => 'end_datetime'])
        //     ->setExpressionAttributeValues([':lower1' => DynamoDb::marshalValue($startDateTime), ':upper1' => DynamoDb::marshalValue($endDateTime)])
        //     ->prepare()
        //     ->scan();

        $query = DynamoDb::table('events')
            ->setFilterExpression('#start_datetime BETWEEN :lower1 AND :upper1 OR #end_datetime BETWEEN :lower1 AND :upper1')
            ->setProjectionExpression('id, start_datetime, end_datetime, integration_account')
            ->setExpressionAttributeNames(['#start_datetime' => 'start_datetime', '#end_datetime' => 'end_datetime'])
            ->setExpressionAttributeValues([':lower1' => DynamoDb::marshalValue($startDateTime), ':upper1' => DynamoDb::marshalValue($endDateTime)]);

        if ($createdMethod == Event::SELF_CREATE_METHOD) {
            $query->setFilterExpression('#creator_id = :creator_id AND #company_id = :company_id');
            $query->setExpressionAttributeNames(['#creator_id' => 'creator_id', '#company_id' => 'company_id']);
            $query->setExpressionAttributeValues([':creator_id' => DynamoDb::marshalValue($userId), ':company_id' => DynamoDb::marshalValue($companyId)]);
        } elseif ($createdMethod == Event::STREAMON_CREATE_METHOD) {
            $query->setFilterExpression('#creator_id = :creator_id');
            $query->setExpressionAttributeNames(['#creator_id' => 'creator_id']);
            $query->setExpressionAttributeValues([':creator_id' => DynamoDb::marshalValue($userId)]);
        } elseif ($createdMethod == Event::INDIVIDUAL_ZOOM_CREATE_METHOD) {
            $query->setFilterExpression('#company_id = :company_id');
            $query->setExpressionAttributeNames(['#company_id' => 'company_id']);
            $query->setExpressionAttributeValues([':company_id' => DynamoDb::marshalValue($companyId)]);
        }

        $results = $query->prepare()->scan();

        return isset($results['Items']) ? $results['Items'] : [];
    }

    public function fetchEventsInDateRange($startDateTime, $endDateTime)
    {
        $query = DynamoDb::table('events')
            ->setFilterExpression('#start_datetime BETWEEN :lower1 AND :upper1')
            // ->setIndexName("desc_global_events")
            // ->setProjectionExpression('id, start_datetime, end_datetime, integration_account')
            ->setExpressionAttributeNames(['#start_datetime' => 'start_datetime'])
            ->setExpressionAttributeValues([':lower1' => DynamoDb::marshalValue($startDateTime), ':upper1' => DynamoDb::marshalValue($endDateTime)])
            ->setScanIndexForward(false);

        $results = $query->prepare()->scan();

        $processedItems = [];

        if(null !== $results['Items']) {
            foreach ($results['Items'] as $item) {
                $processedItem = [];
                foreach ($item as $key => $value) {
                    $processedItem[$key] = reset($value);
                }
                $processedItems[] = $processedItem;
            }
            $processedItems = collect($processedItems)
                ->sortByDesc('start_datetime')
                ->values()
                ->all();
        }

        return $processedItems;
    }

    public function selectEmails($finalArr, $eventStartDateTime, $eventEndDateTime)
    {
        $selectedEmails = [];
        $eventStartDateTime = strtotime($eventStartDateTime);
        $eventEndDateTime = strtotime($eventEndDateTime);
        foreach ($finalArr as $acc => $event) {
            if (empty($event)) {
                $selectedEmails[] = $acc;
            } else {
                $isAvailable = true;
                foreach ($event as $dateTime) {
                    if (isset($dateTime['start_datetime']) && isset($dateTime['end_datetime'])) {
                        $startDateTime = strtotime($dateTime['start_datetime']);
                        $endDateTime = strtotime('+55 minutes', strtotime($dateTime['end_datetime']));
                        if (($eventStartDateTime >= $startDateTime && $eventStartDateTime <= $endDateTime) || ($eventEndDateTime >= $startDateTime && $eventEndDateTime <= $endDateTime)) {
                            $isAvailable = false;
                            break;
                        }
                    }
                }
                if ($isAvailable) {
                    $selectedEmails[] = $acc;
                }
            }
        }
        return $selectedEmails;
    }

    public function getValidZoomPullEmail(object $data)
    {
        try {
            $createdMethod = $data->create_method;
            $zoomEmail = null;
            $eventStartDateTime = new DateTime($data->start_datetime);
            $eventEndDateTime = new DateTime($data->end_datetime);
            $oneDayBeforeStart = clone $eventStartDateTime;
            $oneDayBeforeStart->modify('-1 day');

            // One day after
            $oneDayAfterEnd = clone $eventEndDateTime;
            $oneDayAfterEnd->modify('+1 day');

            $oneDayBeforeStart = $oneDayBeforeStart->format('Y-m-d\TH:i:sO');
            $oneDayAfterEnd = $oneDayAfterEnd->format('Y-m-d\TH:i:sO');

            $createdEvents = (new EventService())->fetchEventsOnDateTime($oneDayBeforeStart, $oneDayAfterEnd, $createdMethod, $data->company_id, $data->user_id);

            $processedItems = [];
            $finalArr = $availableAccs = [];

            foreach ($createdEvents as $item) {
                $processedItem = [];
                foreach ($item as $key => $value) {
                    $processedItem[$key] = reset($value);
                }
                $processedItems[] = $processedItem;
            }
            $sortedEvents = collect($processedItems)
                ->sortBy('start_datetime')
                ->values()
                ->all();

            foreach ($sortedEvents as $value) {
                if (isset($value['integration_account'])) {
                    if (isset($finalArr[$value['integration_account']])) {
                        array_push($finalArr[$value['integration_account']], $value);
                    } else {
                        $finalArr[$value['integration_account']] = [$value];
                    }
                }
            }

            $allIndividualAccs = (new ZoomIndividualService())->getZoomAccountsOnCreateMethod($createdMethod, $data->company_id, $data->user_id);

            if ($createdMethod == Event::STREAMON_CREATE_METHOD) {
                $integrations = $allIndividualAccs->zoom_integrations;
                foreach ($integrations as $integration) {
                    if ($integration['email'] && !in_array($integration['email'], array_keys($finalArr))) {
                        array_push($availableAccs, $integration['email']);
                    }
                }
            } elseif ($createdMethod == Event::INDIVIDUAL_ZOOM_CREATE_METHOD) {
                foreach ($allIndividualAccs as $acc) {
                    if ($acc->email && !in_array($acc->email, array_keys($finalArr))) {
                        array_push($availableAccs, $acc->email);
                    }
                }
            } elseif ($createdMethod == Event::SELF_CREATE_METHOD) {
                $compMapp = $allIndividualAccs->company_mapping;
                foreach ($compMapp as $comp) {
                    if ($comp['id'] == $data->company_id) {
                        $integrations = $comp['integrations'];
                        if ($integrations && is_array($integrations) && count($integrations)) {
                            foreach ($integrations as $integration) {
                                if ($integration['integration_type'] == 'zoom') {
                                    $intEmail = $integration['integration_obj']['email'];
                                    if ($intEmail && !in_array($intEmail, array_keys($finalArr))) {
                                        array_push($availableAccs, $intEmail);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $availableAccs = array_merge($availableAccs, (new EventService())->selectEmails($finalArr, $eventStartDateTime->format('Y-m-d\TH:i:sO'), $eventEndDateTime->format('Y-m-d\TH:i:sO')));
            if ($availableAccs && is_array($availableAccs) && count($availableAccs)) {
                $zoomEmail = $availableAccs[mt_rand(0, count($availableAccs) - 1)];
            }
            return $zoomEmail;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getTotalEvents()
    {
        $connection = (new CommonService)->getConnection();
        // Create an instance of the DynamoDB client
        $client = new DynamoDbClient($connection);

        $tableName = 'events';
        $indexName = 'desc_event_time_index';

        // Set the filter expression and attribute values
        $filterExpression = '#is_deleted = :is_deleted AND attribute_not_exists(#parent_id)';
        $expressionAttributeNames = [
            '#is_deleted' => 'is_deleted',
            '#parent_id' => 'parent_id',
        ];
        $expressionAttributeValues = [
            ':is_deleted' => ['BOOL' => false],
        ];

        // Fetch the count of records that satisfy the conditions
        $result = $client->scan([
            'TableName' => $tableName,
            'IndexName' => $indexName,
            'Select' => 'COUNT',
            'FilterExpression' => $filterExpression,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ScanIndexForward' => false,
        ]);

        $totalRecords = $result['Count']; // Total number of records that satisfy the conditions
        return $totalRecords;
    }

    public function getTotalEventsOfDepts($deptIds)
    {
        $deptIds = implode(",", $deptIds);

        $connection = (new CommonService)->getConnection();
        // Create an instance of the DynamoDB client
        $client = new DynamoDbClient($connection);

        $tableName = 'events';

        // Set the filter expression and attribute values
        $filterExpression = '#is_deleted = :is_deleted AND attribute_not_exists(#parent_id) AND #department_id IN (:deptIds)';
        $expressionAttributeNames = [
            '#is_deleted' => 'is_deleted',
            '#parent_id' => 'parent_id',
            '#department_id' => 'department_id'
        ];
        $expressionAttributeValues = [
            ':is_deleted' => ['BOOL' => false],
            ':deptIds' => ['S' => $deptIds],
        ];

        // Fetch the count of records that satisfy the conditions
        $result = $client->scan([
            'TableName' => $tableName,
            'Select' => 'COUNT',
            'FilterExpression' => $filterExpression,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ScanIndexForward' => false,
        ]);

        $totalRecords = $result['Count']; // Total number of records that satisfy the conditions
        return $totalRecords;
    }

    public function getEvents($params) {
        $connection = (new CommonService)->getConnection();
        $client = new DynamoDbClient($connection);

        $result = $client->scan($params);
        $startKey = $result['LastEvaluatedKey'];
        
        $events = [];
        foreach($result['Items'] as $items) {
            $values = [];
            foreach($items as $key=>$value) {
                $values[$key] = current($value);
            }
            $events[] = $values;
        }
        return [$events, $startKey];
    }

    public function fetchEvents($limit, $startKey = null, $retrivedEvents = 0, $eventList = [], $dynamicLimitCount = 10)
    {
        $fetchAll = false;
        // $attributes = ['id', 'name', 'slug', 'company_id', 'description', 'is_active', 'created_at', 'type', 'parent_id', 'event_count', 'start_datetime'];
        $totalEvents = $this->getTotalEvents();
        $applyLimit = false;
        if ($totalEvents - $retrivedEvents <= 9) {
            $applyLimit = true;
        }

        if ($totalEvents == 0) {
            return ['events' => [], 'totalEvents' => 0, 'startKey' => null];
        } elseif ($totalEvents < $limit) {
            $fetchAll = true;
        }
        // $query = Event::withIndex('asc_event_time_index')->whereNull('parent_id')->where(['is_deleted' => false]);
        $dynamicLimitCount = $dynamicLimitCount + $limit;
        $tableName = "events";
        $indexName = "desc_event_time_index";
        $orderBy = "desc_event_timestamp";

        $filterExpression = "#is_deleted = :is_deleted AND attribute_not_exists(#parent_id)";
        $expressionAttributeNames = [
            '#is_deleted' => "is_deleted",
            '#parent_id' => "parent_id"
        ];
        $expressionAttributeValues = [
            ':is_deleted' => ['BOOL' => false]
        ];

        $params = [
            'TableName' => $tableName,
            'IndexName' => $indexName,
            'FilterExpression' => $filterExpression,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ProjectionExpression' => 'id, desc_event_timestamp, global_partition_key'
        ];

        if ($fetchAll) {
            [$events, $ExclusiveStartKey] = $this->getEvents($params);
            $ExclusiveStartKey['global_partition_key'] = ["N" => "1"];
            $ExclusiveStartKey['desc_event_timestamp'] = ["N" => $events[count($events) - 1]['desc_event_timestamp']];
            return ['events' => $events, 'totalEvents' => $totalEvents, 'startKey' => $ExclusiveStartKey];
        } elseif ($startKey) {
            $params['ExclusiveStartKey'] = $startKey;
            $params['Limit'] = $dynamicLimitCount;
            [$events, $ExclusiveStartKey] = $this->getEvents($params);
        } else {
            $params['Limit'] = $dynamicLimitCount;
            [$events, $ExclusiveStartKey] = $this->getEvents($params);
        }
        if (count($events) > 0) {
            foreach ($events as $e) {
                $id = $e['id'];
                $event = Event::where(["id" => $id])
                    ->first(['id', 'global_partition_key', 'start_datetime', 'company_id', 'created_at', 'slug', 'desc_event_timestamp', 'asc_event_timestamp', 'name', 'asc_creation_timestamp', 'event_count', 'is_active', 'description', 'parent_id', 'desc_creation_timestamp', 'type']);
                $eventList[] = $event;
                if (count($eventList) >= $limit || (!$applyLimit ? false : ($totalEvents - $retrivedEvents) <= count($eventList))) {
                    break;
                }
            }
        }
        $ExclusiveStartKey['global_partition_key'] = ["N" => "1"];

        if (count($eventList) < $limit && (!$applyLimit ? true : $totalEvents - $retrivedEvents > count($eventList))) {
            return self::fetchEvents($limit, count($events) ? $ExclusiveStartKey : $startKey, $retrivedEvents + count($eventList), $eventList, $dynamicLimitCount);
        } else {
            return ['events' => $eventList, 'totalEvents' => $totalEvents, 'startKey' => count($eventList) ? [
                    'id' => [
                    'S' => $eventList[count($eventList) - 1]->id
                ],
                $orderBy => [
                    'N' => $eventList[count($eventList) - 1]->$orderBy
                ],
                'global_partition_key' => [
                    "N" => "1"
                ]
            ] : null];
        }
    }

    public function fetchDeptAdminEvents($deptIds, $limit, $startKey = null, $retrivedEvents = 0, $eventList = [], $dynamicLimitCount = 10)
    {
        $fetchAll = false;
        // $attributes = ['id', 'name', 'slug', 'company_id', 'description', 'is_active', 'created_at', 'type', 'parent_id', 'event_count', 'start_datetime'];
        $totalEvents = $this->getTotalEventsOfDepts($deptIds);

        $applyLimit = false;
        if ($totalEvents - $retrivedEvents <= 9) {
            $applyLimit = true;
        }

        if ($totalEvents == 0) {
            return ['events' => [], 'totalEvents' => 0, 'startKey' => null];
        } elseif ($totalEvents < $limit) {
            $fetchAll = true;
        }

        $query = Event::whereNull('parent_id')->where(['is_deleted' => false])->whereIn('department_id', $deptIds);
        $query->sortByDesc('start_datetime');
        $dynamicLimitCount = $dynamicLimitCount + $limit;

        if ($fetchAll) {
            $events = $query->get();
            //return $events;
            return ['events' => $events->toArray(), 'totalEvents' => $totalEvents, 'startKey' => $events->lastKey()];
        } elseif ($startKey) {
            $events = $query
                ->afterKey($startKey)
                ->limit($dynamicLimitCount)
                ->get();
        } else {
            $events = $query->limit($dynamicLimitCount)->get();
        }

        if (count($events) > 0) {
            $eventArrayList = $events->toArray();
            foreach ($eventArrayList as $e) {
                $eventList[] = $e;
                if (count($eventList) >= $limit || ($applyLimit ? false : $totalEvents - $retrivedEvents <= count($eventList))) {
                    break;
                }
            }
        }
        if (count($eventList) < $limit && (!$applyLimit ? true : $totalEvents - $retrivedEvents > count($eventList))) {
            return self::fetchDeptAdminEvents($deptIds, $limit, count($events) ? $events->lastKey() : null, $retrivedEvents + count($eventList), $eventList, $dynamicLimitCount);
        } else {
            return ['events' => $eventList, 'totalEvents' => $totalEvents, 'startKey' => count($eventList) ? ['id' => $eventList[count($eventList) - 1]['id']] : null];
        }
    }

    public function updateEvents($events)
    {
        foreach ($events as $e) {
            $stf = $e->start_datetime;
            $etf = $e->end_datetime;
            $sttf = $e->timezone_start_datetime;
            $ettf = $e->timezone_end_datetime;

            if ($e->start_datetime) {
                $st = $e->start_datetime;
                // Step 1: Create a DateTime object from the string datetime stamp
                $datetimeObj = DateTime::createFromFormat('Y-m-d H:i:sO', $st);
                if (!$datetimeObj) {
                    continue;
                }
                // Step 2: Format the DateTime object into the desired format
                $stf = $datetimeObj->format('Y-m-d\TH:i:sO');
            }

            if ($e->end_datetime) {
                $et = $e->end_datetime;
                // Step 1: Create a DateTime object from the string datetime stamp
                $datetimeObj = DateTime::createFromFormat('Y-m-d H:i:sO', $et);
                if (!$datetimeObj) {
                    continue;
                }
                // Step 2: Format the DateTime object into the desired format
                $etf = $datetimeObj->format('Y-m-d\TH:i:sO');
            }

            if ($e->timezone_start_datetime) {
                $stt = $e->timezone_start_datetime;
                // Step 1: Create a DateTime object from the string datetime stamp
                $datetimeObj = DateTime::createFromFormat('Y-m-d H:i:sO', $stt);
                if (!$datetimeObj) {
                    continue;
                }
                // Step 2: Format the DateTime object into the desired format
                $sttf = $datetimeObj->format('Y-m-d\TH:i:sO');
            }
            if ($e->timezone_end_datetime) {
                $ett = $e->timezone_end_datetime;
                // Step 1: Create a DateTime object from the string datetime stamp
                $datetimeObj = DateTime::createFromFormat('Y-m-d H:i:sO', $ett);
                if (!$datetimeObj) {
                    continue;
                }
                // Step 2: Format the DateTime object into the desired format
                $ettf = $datetimeObj->format('Y-m-d\TH:i:sO');
            }
            $data = [
                'start_datetime' => $stf,
                'end_datetime' => $etf,
                'timezone_start_datetime' => $sttf,
                'timezone_end_datetime' => $ettf,
            ];
            (new EventService())->updateEvent($data, $e->id);
            echo $e->id . '<br>';
        }
        return true;
    }

    public function convertDatesToUTC($limit = 10, $startKey = null, $retrivedEvents = 0)
    {
        $startKey = ['id' => '9918059c-693f-4b12-a28e-573aa59f03e6'];
        $totalEvents = $this->getTotalEvents();
        $events = null;
        if ($startKey) {
            $events = Event::afterKey($startKey)
                ->limit($limit)
                ->all(['id', 'start_datetime', 'end_datetime', 'timezone_start_datetime', 'timezone_end_datetime']);
        } else {
            $events = Event::limit($limit)->all(['id', 'start_datetime', 'end_datetime', 'timezone_start_datetime', 'timezone_end_datetime']);
        }
        if ($events) {
            (new EventService())->updateEvents($events);
        }
        $retrivedEvents += count($events);
        print_r($retrivedEvents);
        echo count($events) . '<br>';
        echo '<br>';
        if ($totalEvents > $retrivedEvents && count($events) >= 10) {
            return self::convertDatesToUTC($limit, count($events) ? $events->lastKey() : null, $retrivedEvents);
        }
        return 'finished';
    }

    public function getParticularEventDetails($eventId = NULL, $subEventId = NULL) {
        $query = NULL;

        if($subEventId && $eventId) {
            $query = Event::where(['id'=> $subEventId]);
        } elseif($eventId) {
            $query = Event::where(['id'=> $eventId]);
        }
        return $query->first();
    }

}