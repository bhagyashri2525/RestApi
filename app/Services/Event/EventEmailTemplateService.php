<?php

namespace App\Services\Event;

use App\Models\EventEmailUserGroup;
use App\Models\EventEmailGroupUser;
use App\Models\EmailTemplate;
use App\Services\Auth\UserService;
use App\Services\Utils\CommonService;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use BaoPham\DynamoDb\Facades\DynamoDb;

class EventEmailTemplateService
{
    public function storetemplate($data) : EmailTemplate {

        $data['id'] = (new CommonService)->generatePrimaryKey();
 
        $model = new EmailTemplate($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function removeTemplate($templateId) {
        $model = EmailTemplate::find($templateId);
        if($model) {
            return $model->deleteAsync()->wait();
        }
    }

    public function listEventTemplates($eventId = NULL, $subEventId = NULL) {
        $templates = NULL;
        if($eventId) {
            $query = EmailTemplate::where("event_id", $eventId);
            if($subEventId) {
                $query->where("sub_event_id", $subEventId);
            }return $query->all();
        } else {
            return EmailTemplate::all();
        }
    }

    public function getTemplate($templateId) {
        return EmailTemplate::find($templateId);
    }
    
    public function updateTemplate($data) {
        $updateExpression = "SET #template_name = :template_name, #template = :template, #path = :path, #sub_event_id = :sub_event_id";
        DynamoDb::table(EmailTemplate::TABLE)
        ->setKey(DynamoDb::marshalItem(['id' => $data['id']]))
        ->setUpdateExpression($updateExpression)
        ->setExpressionAttributeNames(['#template_name' => 'template_name', '#template' => 'template', '#path' => 'path', '#sub_event_id' => 'sub_event_id'])
        ->setExpressionAttributeValues([
            ':template_name' => DynamoDb::marshalValue($data['template_name']),
            ':template' => DynamoDb::marshalValue($data['template']),
            ':path' => DynamoDb::marshalValue($data['path']),
            ':sub_event_id' => DynamoDb::marshalValue($data['sub_event_id']),
        ])
        ->prepare()
        ->updateItem();  
        return EmailTemplate::find($data['id']);
    }
    
    public function updateTemplatePath($data) {
        $updateExpression = "SET #path = :path, #subject = :subject";
        DynamoDb::table(EmailTemplate::TABLE)
        ->setKey(DynamoDb::marshalItem(['id' => $data['id']]))
        ->setUpdateExpression($updateExpression)
        ->setExpressionAttributeNames(['#path' => 'path', '#subject' => 'subject'])
        ->setExpressionAttributeValues([
            ':path' => DynamoDb::marshalValue($data['path']),
            ':subject' => DynamoDb::marshalValue($data['subject'])
        ])
        ->prepare()
        ->updateItem();  
        return EmailTemplate::find($data['id']);
    }
}
?>