<?php

namespace App\Services\Event;
use App\Models\EventResource;
use App\Models\EngagementPageSections;
use App\Services\Utils\CommonService;
use BaoPham\DynamoDb\Facades\DynamoDb;
use BaoPham\DynamoDb\RawDynamoDbQuery;

class EventResourceService{

    public function list($all = false)
    {
        $list = [];

        if ($all) {
            $list = EventResource::get();
        } else {
            //$list = Company::where(['is_active' => true])->get();
            $list = EventResource::where(['is_active' => true])->get();
        }
        //print_r($list);
        return $list;
    }

    public function store($data): EventResource
    {
        $data['id'] = (new CommonService())->generatePrimaryKey();
        $model = new EventResource($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }    

    public function update($id ,$data): EventResource
    {
        $resource = EventResource::where(['id' => $id])->first();
        if ($resource) {
            $resource->update($data);
            return $resource->refresh();
        }
        return null;
    }    

    public function delete($id ): EventResource
    {
        $resource = EventResource::where(['id' => $id])->first();
        if ($resource) {
            $resource->delete($id);
            return $resource->refresh();
        }
        return null;
    }    

    public function resourceLayoutList($all = false)
    {
        $list = [];

        if ($all) {
            $list = EngagementPageSections::get();
        } else {
            //$list = Company::where(['is_active' => true])->get();
            $list = EngagementPageSections::where(['is_active' => true])->get();
        }
        //print_r($list);
        return $list;
    }

    public function storeResourceLayout($data)
    {
        $data['id'] = (new CommonService())->generatePrimaryKey();
        $model = new EngagementPageSections($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }    

    public function updateResourceLayout($id ,$data): EngagementPageSections
    {
        $resource = EngagementPageSections::where(['id' => $id])->first();
        if ($resource) {
            $resource->update($data);
            return $resource->refresh();
        }
        return null;
    }    

}
?>