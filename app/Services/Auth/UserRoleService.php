<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserParent;
use App\Models\UserRole;
use App\Services\Utils\CommonService;
use GuzzleHttp\Promise\Utils;

class UserRoleService
{
    public function getRoles($all = false)
    {
        if ($all) {
            return Role::all();
        } else {
            return Role::whereIsActive(true)->get();
        }
    }

    public function getUsers($all = false)
    {
        if ($all) {
            return User::all();
        } else {
            return User::whereIsActive(true)->with('role_mapping')->get();
        }
    }

    public function getUserRoles($userId)
    {
        $userRoles = UserRole::where("user_id", $userId)->get();
        $output = [];
        if ($userRoles) {
            foreach ($userRoles as $userRole) {
                $output[] = $userRole->role_id;
            }
            return $output;
        } else {
            return [];
        }

    }

    public function removeAllUserRoleMapping($userId)
    {
        return UserRole::where("user_id", $userId)->delete();
    }

    public function removeUsersRoleMapping($userId, $roleIds)
    {
        $deletePromises = [];
        $output = UserRole::where("user_id", $userId)->whereIn('role_id', $roleIds)->get();
        if (!empty($output)) {
            foreach ($output as $userRole) {
                $deletePromises[] = $userRole->deleteAsync();
            }
            Utils::settle($deletePromises)->wait();
        }
    }

    public function update($userId, $roleIds)
    {

        $unwantedUserRoles = UserRole::where(["user_id" => $userId])->get(); //->whereNotIn('role_id', $roleIds)->get();

        $deletePromises = [];
        if (!empty($unwantedUserRoles)) {
            foreach ($unwantedUserRoles as $userRole) {
                $deletePromises[] = $userRole->deleteAsync();
            }
            Utils::settle($deletePromises)->wait();
        }

        if ($roleIds) {
            $promises = [];
            foreach ($roleIds as $roleId) {

                $userRole = UserRole::where(['user_id' => $userId,'role_id' => $roleId])->first();

                if (empty($userRole)) {
                    $data = array_merge(['id' => (new CommonService)->generatePrimaryKey()], ['user_id' => $userId, 'role_id' => $roleId, 'is_active' => true]);
                    $model = new UserRole($data);
                    $promises[] = $model->saveAsync();
                }
            }
            Utils::settle($promises)->wait();

        }
        return true;
    }

    public function addRoleMapping(string $userId, string $roleId){

        $data = ["user_id" => $userId,'role_id' => $roleId];
        $userRole = UserRole::where($data)->first();
        if(empty($userRole)){
            $data = array_merge(['id' => (new CommonService)->generatePrimaryKey(),'is_active' => true], $data);
            $userRole = new UserRole($data);
        }else{
            $userRole->is_active = true;
        }
        $userRole->save();
        return $userRole->refresh();
    }

    public function addPrimaryRole(String $userId,String $roleId){
        
        if($roleId && $userId){
            $user = User::where(['id' => $userId])->first();
            $user->role_id = $roleId;
            $user->save();
            return true;
        }
        return false;
    }

    public function getChildUserRole(String $roleId){
        //$role = Role::where(['id' => $roleId,'is_active' => true])->first();
        return Role::active()->first();
    }
}