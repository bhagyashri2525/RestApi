<?php

namespace App\Services\Event;

use App\Models\EventEmailUserGroup;
use App\Models\EventEmailGroupUser;
use App\Models\EventEmailLog;

use App\Services\Auth\UserService;
use App\Services\Utils\CommonService;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use BaoPham\DynamoDb\Facades\DynamoDb;

class EventEmailLogService
{
    public function log($eventId, $subEventId = NULL) {
        $query = EventEmailLog::where('event_id', $eventId);
        if($subEventId) {
            $query->where("sub_event_id", $subEventId);
        }
        return $query->all();
    }

    public function saveMailLog($data) {
        $data['id'] = (new CommonService)->generatePrimaryKey();
        $send_option = $data['send_option'];
        if($send_option == "sendnow") {
            $date = new DateTime("now", new DateTimeZone($data['timezone']));
            $data['datetime'] = $date->format("Y-m-d H:i:sP");
            $utcDateTime = DateTime::createFromFormat('Y-m-d H:i:sP', $data['datetime']);
            $utcDateTime->setTimezone(new DateTimeZone("UTC"));
            $data['utc_datetime'] = $utcDateTime->format("Y-m-d H:i:sP");
        } else {
            $date = new DateTime($data['datetime'], new DateTimeZone($data['timezone']));
            $data['datetime'] = $date->format("Y-m-d H:i:sP");
            $utcDateTime = DateTime::createFromFormat('Y-m-d H:i:sP', $data['datetime']);
            $utcDateTime->setTimezone(new DateTimeZone("UTC"));
            $data['utc_datetime'] = $utcDateTime->format("Y-m-d H:i:sP");
        }
        $model = new EventEmailLog($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }
}
?>