<?php

namespace App\Services\Event;

use App\Models\DefaultEmailUserGroup;
use App\Models\EventEmailUserGroup;
use App\Models\EventEmailGroupUser;
use App\Services\Auth\UserService;
use App\Services\Utils\CommonService;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use BaoPham\DynamoDb\Facades\DynamoDb;

class EventEmailGroupService
{

    //get single group
    public function getGroup($groupId) {
        $group = [];
        if($groupId) {
            $group = EventEmailUserGroup::find($groupId);
            if(!$group) {
                $group = DefaultEmailUserGroup::find($groupId);
            }
        }
        return $group;
    }

    // group users
    public function getGroupUsers($groupId) {
        if($groupId) {
            return EventEmailGroupUser::where(['group_id' => $groupId])->get();
        } else {
            return [];
        }
    }

    public function listEventGroups($eventId = NULL, $subEventId = NULL) {
        $query = NULL;
        if($eventId && $subEventId) {
            $query = EventEmailUserGroup::where(['event_id' => $eventId, 'sub_event_id' => $subEventId]);
        } else {
            $query = EventEmailUserGroup::where(['event_id' => $eventId]);
        }
        return $query->all();
    }

    public function listDefaultGroups() {
        $defaultGroups = DefaultEmailUserGroup::all();
        return $defaultGroups;
    }

    //update group

    public function updateGroup($data) {
        $updateExpression = "SET #group_name = :group_name, #count = :count";
        DynamoDb::table(EventEmailUserGroup::TABLE)
        ->setKey(DynamoDb::marshalItem(['id' => $data['id']]))
        ->setUpdateExpression($updateExpression)
        ->setExpressionAttributeName('#group_name', 'group_name')
        ->setExpressionAttributeName('#count', 'count')
        ->setExpressionAttributeValue(':group_name', DynamoDb::marshalValue($data['group_name']))
        ->setExpressionAttributeValue(':count', DynamoDb::marshalValue($data['count']))
        ->prepare()
        ->updateItem();  
        return EventEmailUserGroup::find($data['id']);
    }

    //remove group
    public function removeGroup($groupId) {
        $model = EventEmailUserGroup::find($groupId);
        if($model) {
            return $model->deleteAsync()->wait();
        }
    }

    public function saveUserGroup($data) {
        $data['id'] = (new CommonService)->generatePrimaryKey();
        $model = new EventEmailUserGroup($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }
    
    public function saveGroupUsers($data) {
        $data['id'] = (new CommonService)->generatePrimaryKey();
        $model = new EventEmailGroupUser($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function deleteGroupUsers($groupId) {
        $users = EventEmailGroupUser::where('group_id', $groupId)->get();
        foreach($users as $user) {
            $user->deleteAsync()->wait();
        }
        return true;
    }
}
?>