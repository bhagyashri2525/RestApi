<?php

namespace App\Services\Auth;

use App\Models\Permission;
use App\Services\Utils\CommonService;

class PermissionService
{
    public function getAll()
    {
        return Permission::all();
    }

    public function store($data): Permission
    {
        $data = array_merge(['id' => (new CommonService)->generatePrimaryKey()], $data);
        $model = new Permission($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function update($id, $data): Permission
    {
        $permission = Permission::where(['id' => $id])->first();
        if ($permission) {
            $permission->update($data);
            return $permission->refresh();
        }
        return null;
    }

    public function details($id, $whereData = []): Permission
    {
        $permission = Permission::where(['id' => $id])
            ->when(!empty($whereData), function ($query) use ($whereData) {
                $query->where($whereData);
            })
            ->active()->first();

        return $permission;
    }

}
