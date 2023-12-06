<?php
namespace App\Services\Email;

use App\Models\EventEmailSentLog;
use App\Services\Utils\CommonService;

class EventEmailSentLogService {
    public function saveMailReport($data) {
        if($data) {
            $data['id'] = (new CommonService)->generatePrimaryKey();
            $model = new EventEmailSentLog($data);
            return $model->saveAsync()->wait();
        }
    }

    public function getEmailSentLog($companyId, $eventId, $emailLogId) {
        if(!$companyId || !$eventId || !$emailLogId) {
            return false;
        }

        return EventEmailSentLog::where('email_log_id', $emailLogId)->all();
    }
}
?>