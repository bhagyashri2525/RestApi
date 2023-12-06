<?php

namespace App\Services\Company;

use App\Data\Event\EventData;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmailTemplate;
use App\Models\CompanyTokens;
use App\Models\Event;
use App\Models\MasterCompany;
use App\Models\User;
use App\Services\Event\EventService;
use App\Services\Utils\CommonService;
use Aws\DynamoDb\DynamoDbClient;
use BaoPham\DynamoDb\Facades\DynamoDb;
use BaoPham\DynamoDb\RawDynamoDbQuery;
use DateTime;
use DateTimeZone;
use BaoPham\DynamoDb\DynamoDbCollection;

class CompanyService
{
    public function list($all = false)
    {
        $list = [];

        if ($all) {
            $list = Company::get();
        } else {
            //$list = Company::where(['is_active' => true])->get();
            $list = Company::where(['is_active' => true])->get();
        }
        //print_r($list);

        return $list;
    }

    public function store($data): Company
    {
        $data['id'] = (new CommonService())->generatePrimaryKey();
        $model = new Company($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }


    public function storeToken($data): CompanyTokens
    {
        $data['id'] = (new CommonService())->generatePrimaryKey();
        $data['token'] = (new CommonService())->generatePrimaryKey();

        $model = new CompanyTokens($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function tokenList($all = false)
    {
        $list = [];

        if ($all) {
            $list = CompanyTokens::get();
        } else {
            //$list = Company::where(['is_active' => true])->get();
            $list = CompanyTokens::where(['is_active' => true])->get();
        }
        return $list;
    }

    public function updateToken($id, $data): CompanyTokens
    {
        $token = CompanyTokens::where(['id' => $id])->first();
        if ($token) {
            $token->update($data);
            return $token->refresh();
        }
        return null;
    }
    
    public function deleteToken($id): CompanyTokens
    {
        $token = CompanyTokens::where(['id' => $id])->first();
        // echo($token);exit();
        if ($token) {
            $token->delete($id);
            return $token->refresh();
        }
        return null;
    }

    public function update($id, $data): Company
    {
        $company = Company::where(['id' => $id])->first();
        if ($company) {
            $company->update($data);
            return $company->refresh();
        }
        return null;
    }

    public function delete($id): Company
    {
        $company = Company::where(['id' => $id])->first();
        if ($company) {
            $company->delete($id);
            return $company->refresh();
        }
        return null;
    }

    public function storetemplate($data): EmailTemplate
    {
        $data['id'] = (new CommonService())->generatePrimaryKey();

        $model = new EmailTemplate($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function getTemplates($eventId)
    {
        return EmailTemplate::where(['event_id' => $eventId])->get();
    }

    public function details($id)
    {
        $company = Company::details($id);
        return $company;
    }

    public function createDepartment($companyId, $data)
    {
        $company = Company::where(['id' => $companyId])->first();

        if (!empty($company)) {
            $timestamp = (string) now();
            $departmentId = (new CommonService())->generatePrimaryKey();
            $department = array_merge(['id' => $departmentId, 'created_at' => $timestamp], $data);

            if (!empty($company->departments)) {
                $index = count($company->departments);
                $queryData = $department;
                $updateExpression = "SET #col[{$index}] = :data";
            } else {
                $queryData = [$department];
                $updateExpression = 'SET #col = :data';
            }

            DynamoDb::table(Company::TABLE)
                ->setKey(DynamoDb::marshalItem(['id' => $companyId]))
                ->setUpdateExpression($updateExpression)
                ->setExpressionAttributeName('#col', 'departments')
                ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($queryData))
                ->prepare()
                ->updateItem();

            return $department;
        }

        return null;
    }

    // public function deletedepartment($companyId ,$departmentId): Company
    // {
    //     $company = Company::where(['id' => $departmentId])->first();
    //     if ($company) {
    //         $company->delete($departmentId);
    //         return $company->refresh();
    //     }
    //     return null;
    // }

    public function updateDepartment($companyId, $departmentIndexId, $data)
    {
        $updateExpression = "SET #col[$departmentIndexId] = :data"; # update property
        DynamoDb::table('companies')
            ->setKey(DynamoDb::marshalItem(['id' => $companyId]))
            ->setUpdateExpression($updateExpression)
            ->setExpressionAttributeName('#col', 'departments')
            ->setExpressionAttributeValue(':data', DynamoDb::marshalValue($data))
            ->prepare()
            ->updateItem();

        return true;
    }

    public function departmentList($companyId)
    {
        $departments = Department::where(['company_id' => $companyId])->get();
        return $departments;
    }

    public function userList($companyId)
    {
        $list = [];
        $userList = User::where('companies', 'contains', $companyId)->get();
        if ($userList) {
            foreach ($userList as $user) {
                $company = [];
                if (!empty($user->company_mapping)) {
                    foreach ($user->company_mapping as $mapp) {
                        if ($mapp['id'] == $companyId) {
                            $company = $mapp;
                        }
                    }
                    $list[] = ['id' => $user->id, 'email' => $user->email, 'display_name' => $user->display_name, 'company' => $company];
                }
            }
        }
        return $list;
    }
    
    public function getCompaniesTotalEvents($companyId) {

        $connection = (new CommonService)->getConnection();
        
        // Create an instance of the DynamoDB client
        $client = new DynamoDbClient($connection);
        
        $tableName = 'events';
        $indexName = 'desc_event_time_index';

        // Set the filter expression and attribute values
        $filterExpression = "#is_deleted = :is_deleted AND attribute_not_exists(#parent_id) AND #company_id = :company_id";
        $expressionAttributeNames = [
            '#is_deleted' => 'is_deleted',
            '#parent_id' => 'parent_id',
            '#company_id' => 'company_id',
        ];
        $expressionAttributeValues = [
            ':is_deleted' => ['BOOL' => false],
            ':company_id' => ['S' => $companyId],
        ];

        // Fetch the count of records that satisfy the conditions
        $result = $client->scan([
            'TableName' => $tableName,
            'IndexName' => $indexName,
            'Select' => 'COUNT',
            'FilterExpression' => $filterExpression,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'ExpressionAttributeValues' => $expressionAttributeValues,
        ]);

        $totalRecords = $result['Count']; // Total number of records that satisfy the conditions
        return $totalRecords;
    }

    public function eventList($companyId, $limit, $startKey, $retrivedEvents = 0, $eventList = [], $dynamicLimitCount = 10) {
        $fetchAll = false;
        $ExclusiveStartKey = null;
        // $attributes = 'id, #myAttributeName, slug, company_id, description, is_active, created_at, type, parent_id, event_count, start_datetime';
        $totalEventCount = self::getCompaniesTotalEvents($companyId);
        $applyLimit = false;
        if(($totalEventCount - $retrivedEvents) <= 9) {
            $applyLimit = true;
        }
        if($totalEventCount == 0){
            return ['events' => [], 'totalEvents' => 0, 'startKey' => null]; 
        }else if($totalEventCount < $limit){
            $fetchAll = true;
        }
       
        $dynamicLimitCount = $dynamicLimitCount + $limit;
        
        $tableName = "events";
        $indexName = "desc_event_time_index";
        $orderBy = "desc_event_timestamp";

        $filterExpression = "#is_deleted = :is_deleted AND attribute_not_exists(#parent_id) AND #company_id = :company_id";
        $expressionAttributeNames = [
            '#is_deleted' => "is_deleted",
            '#parent_id' => "parent_id",
            '#company_id' => "company_id",
        ];
        $expressionAttributeValues = [
            ':is_deleted' => ['BOOL' => false],
            ':company_id' => ['S' => $companyId]
        ];

        $params = [
            'TableName' => $tableName,
            'IndexName' => $indexName,
            'FilterExpression' => $filterExpression,
            'ExpressionAttributeNames' => $expressionAttributeNames,
            'ExpressionAttributeValues' => $expressionAttributeValues,
            'ProjectionExpression' => 'id, desc_event_timestamp, global_partition_key'
        ];
        if($fetchAll){
            [$events, $ExclusiveStartKey] = (new EventService())->getEvents($params);
            $ExclusiveStartKey['global_partition_key'] = ["N" => "1"];
            $ExclusiveStartKey['desc_event_timestamp'] = ["N" => $events[count($events) - 1]['desc_event_timestamp']];
            return ['events' => $events, 'totalEvents' => $totalEventCount, 'startKey' => $ExclusiveStartKey];
        }else if($startKey) {
            $params['ExclusiveStartKey'] = $startKey;
            $params['Limit'] = $dynamicLimitCount;
            [$events, $ExclusiveStartKey] = (new EventService())->getEvents($params);
        } else {
            $params['Limit'] = $dynamicLimitCount;
            [$events, $ExclusiveStartKey] = (new EventService())->getEvents($params);
        }

        if(count($events) > 0){
            foreach($events as $e){
                $id = $e['id'];
                $event = Event::where(["id" => $id])
                    ->first(['id', 'global_partition_key', 'start_datetime', 'company_id', 'created_at', 'slug', 'desc_event_timestamp', 'asc_event_timestamp', 'name', 'asc_creation_timestamp', 'event_count', 'is_active', 'description', 'parent_id', 'desc_creation_timestamp', 'type']);
                $eventList[] = $event;
                if(count($eventList) >= $limit || (!$applyLimit ? false : (($totalEventCount - $retrivedEvents) <= count($eventList)))) {
                    break;
                }
            }
        }
        $ExclusiveStartKey['global_partition_key'] = ["N" => "1"];

        if(count($eventList) < $limit && (!$applyLimit ? true : (($totalEventCount - $retrivedEvents) > count($eventList)))) {
           return self::eventList($companyId, $limit, count($events) ? $ExclusiveStartKey : $startKey, ($retrivedEvents + count($eventList)), $eventList, $dynamicLimitCount);
        }else{
            return ['events' => $eventList, 'totalEvents' => $totalEventCount, 'startKey' => count($eventList) ? [
                    'id' => [
                        'S' => $eventList[count($eventList) - 1]->id
                    ],
                    $orderBy => [
                        'N' => $eventList[count($eventList) - 1]->$orderBy
                    ],
                    'global_partition_key' => [
                        "N" => "1"
                    ]
                ] : null
            ];
        }

    }

    public function getEventList($companyId, $limit, $startKey) {
        $list = [];
        $data = self::eventList($companyId, $limit, $startKey);
        print_r($data);
    }
        
    

    public function saveZoomEmail($email, $companyId)
    {
        $company = Company::where(['id' => $companyId])->first();

        if (!empty($company)) {
            $zoomEmails = !empty($company->zoom_emails) && count($company->zoom_emails) ? $company->zoom_emails : [];
            if (empty($zoomEmails)) {
                $zoomEmails[] = $email;
            } elseif (!in_array($email, $zoomEmails)) {
                array_push($zoomEmails, $email);
            }

            $company->update(['zoom_emails' => $zoomEmails]);

            return true;
        }

        return false;
    }

    public function removeZoomIntegration($companyId, $email)
    {
        $company = Company::where(['id' => $companyId])->first();
        //exit;
        //print_r($company->zoom_emails); exit;
        if (!empty($company->zoom_emails)) {
            foreach ($company->zoom_emails as $i => $zoomEmail) {
                if ($zoomEmail == $email) {
                    $updateExpression = "REMOVE #col[$i]"; # update property
                    DynamoDb::table('companies')
                        ->setKey(DynamoDb::marshalItem(['id' => $companyId]))
                        ->setUpdateExpression($updateExpression)
                        ->setExpressionAttributeName('#col', 'zoom_emails')
                        ->prepare()
                        ->updateItem();
                    break;
                }
            }
        }

        if (!empty($company->zoom_integrations)) {
            foreach ($company->zoom_integrations as $zi => $zoomIntegration) {
                if ($zoomIntegration['email'] == $email) {
                    $updateExpression = "REMOVE #col[$zi]"; # update property
                    DynamoDb::table('companies')
                        ->setKey(DynamoDb::marshalItem(['id' => $companyId]))
                        ->setUpdateExpression($updateExpression)
                        ->setExpressionAttributeName('#col', 'zoom_integrations')
                        ->prepare()
                        ->updateItem();
                    break;
                }
            }
        }

        return true;
    }

    public function findZoomIntegration($zoomIntegrations, $email)
    {
        foreach ($zoomIntegrations as $integrationArr) {
            if ($integrationArr['email'] == $email) {
                return $integrationArr;
            }
        }
        return null;
    }

    function createDummyCompanySlug($companyName)
    {
        $companyName = str_replace(' ', '_', $companyName);
        $companyName = strtolower(preg_replace('/[^A-Za-z0-9\-_]/', '', $companyName));
        $slug = 'trial_' . (new CommonService())->generateUniqueString(12) . '_' . $companyName;
        $isExist = Company::where('slug', $slug)->first();
        if (!empty($isExist)) {
            self::createDummyCompanySlug($companyName);
        } else {
            return $slug;
        }
    }

    function getCompanyRefreshToken($type, $companyId = null)
    {
        $refreshToken = null;
        if ($type == 'stream-on') {
            $company = MasterCompany::where(['slug' => 'stream-on'])->first();
            $integration = $company->zoom_integration;
            return $integration[0]['refresh_token'];
        } elseif ($companyId) {
            $company = Company::where(['id' => $companyId])->first();
            $refreshToken = $company->zoom_integrations[0]['refresh_token'];
        }

        return $refreshToken;
    }

    public function saveZoomPullEmails($data, $companyId) {
        if(!$companyId) {
            return false;
        }
        $company = Company::find($companyId);
        if($company) {
            $company->updateAsync($data)->wait();
            return true;
        }
        return false;
    }
}