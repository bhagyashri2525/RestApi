<?php

namespace App\Services\Event;

use App\Models\EventParticipantsLog;
use App\Models\EventPollDetailsLog;
use App\Models\EventPollLog;
use App\Services\Utils\CommonService;
use DateTime;
use DateTimeZone;
use Exception;
use BaoPham\DynamoDb\Facades\DynamoDb;

class ZoomMeetingService
{
    public function saveParticipantData($data) {
        $data['id'] = (new CommonService)->generatePrimaryKey();
        $model = new EventParticipantsLog($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }

    public function savePollData($data) {
        $model = new EventPollLog($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }

    public function savePollReportData($data) {
        $data['id'] = (new CommonService)->generatePrimaryKey();
        $model = new EventPollDetailsLog($data);
        $model->saveAsync()->wait();
        return $model->refresh();
    }
}
?>