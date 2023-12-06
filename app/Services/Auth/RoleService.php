<?php

namespace App\Services\Auth;

use App\Data\Role\RoleData;
use App\Models\Role;
use App\Services\Utils\CommonService;
use BaoPham\DynamoDb\RawDynamoDbQuery;

class RoleService
{
    public function getAll($all = false)
    {
        if ($all) {
            return Role::all();
        } else {
            return Role::all();
        }
    }

    public function store($data): Role
    {
        $data = array_merge(['id' => (new CommonService)->generatePrimaryKey()], $data);
        $model = new Role($data);
        $model->save();
        return $model->refresh(); # re-hydrate the existing model using fresh data from the database
    }

    public function update($id, $data): Role
    {
        $role = Role::where("id", $id)->first();
        if ($role) {
            $role->update($data);
            return $role->refresh();
        }
        return null;
    }

    public function details($id, $whereData = []): Role
    {
        $role = Role::where("id", $id)
            ->when(!empty($whereData), function ($query) use ($whereData) {
                $query->where($whereData);
            })
            ->active()->first();

        return $role;
    }

    public function getRoleBySlug(String $slug){
        $role = Role::where(['slug' => $slug,'is_active' => true])->first();
        return !empty($role) ? $role : null;
    }

    public function getRoleByUsingId(String $roleId){
        return Role::where(['id' => $roleId,'is_active' => true])->first();
    }

    public function getRolesByType(string $type): object | null{
        
        $roles =  Role::active()->where(['type' => $type])
        ->decorate(function (RawDynamoDbQuery $raw) {
            $raw->query['order'] = true;  // asc order
        })
        ->get();

        return RoleData::collection($roles);

    }

    public function getUserRole($user,$roles,$companyId = null){

        $matchRole = '';
        
        if($user->roles && count($user->roles)){
            foreach($user->roles as $xRole){
                if(in_array($xRole,$roles)){
                    $matchRole = $xRole;
                }
            }
        }

        if(empty($matchRole) && $user->company_mapping &&count($user->company_mapping)){

            if(!empty($companyId)){
                foreach($user->company_mapping as $company){
                    if($company['id'] == $companyId) {
                        if(count($company['roles'])){
                            foreach($company['roles'] as $yRole){
                                if(in_array($yRole['slug'],$roles)){
                                    $matchRole = $yRole['slug'];
                                }
                            }
                        }
                    }
                }
            }else{
                foreach($user->company_mapping as $company){
                    if(count($company['roles'])){
                        foreach($company['roles'] as $yRole){
                            if(in_array($yRole['slug'],$roles)){
                                $matchRole = $yRole['slug'];
                            }
                        }
                    }
                }
            }

           
        }

        return $matchRole;
    }
}
