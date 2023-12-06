<?php

namespace App\Services\Event;

use App\Models\EventRole;
use App\Services\Utils\CommonService;

class EventRoleService{
    public function getAll()
    {
        return EventRole::all();
    }

    public function store($data): EventRole
    {
        $data = array_merge(['id' => (new CommonService)->generatePrimaryKey()], $data);
        $model = new EventRole($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function update($id, $data): EventRole
    {
        $role = EventRole::where("id", $id)->first();
        if ($role) {
            $role->update($data);
            return $role->refresh();
        }
        return null;
    }

    public function details($id, $whereData = []): EventRole
    {
        $role = EventRole::where("id", $id)
            ->when(!empty($whereData), function ($query) use ($whereData) {
                $query->where($whereData);
            })
            ->active()->first();

        return $role;
    }

    public function getRoleBySlug(String $slug){
        $role = EventRole::where(['slug' => $slug,'is_active' => true])->first();
        return !empty($role) ? $role : null;
    }

    public function getRoleByUsingId(String $roleId){
        return EventRole::where(['id' => $roleId,'is_active' => true])->first();
    }
}