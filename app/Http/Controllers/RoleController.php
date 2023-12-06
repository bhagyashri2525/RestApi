<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Auth\RoleService;
use App\Services\Utils\ApiService as API;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;

class RoleController extends Controller
{

  
    public function getAll(Request $request)
    {
        try {
          
            $authUser = $request->user();
            $all = true;
            $roles = []; 
           
            if((new RoleService)->getUserRole($authUser,Role::ADMIN_ROLES)){
                $roles = (new RoleService)->getAll();
            }
            else if((new RoleService)->getUserRole($authUser,Role::COMPANY_ROLES)){
                $roles = [];     
            }

            return API::response(!empty($roles) ? API::SUCCESS : API::FAIL, ['roles' => $roles]);
 
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR, []);
        }
    }

   
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => ['required'],
                'slug' => ['required'],
                'is_active' => ['required']
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);

            $role = (new RoleService)->store($request->all());
            
            return API::response(!empty($role) ? API::SUCCESS : API::ERROR, $role);
        
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }

    }

  
    public function update($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required'],
                'slug' => ['required'],
                'is_active' => ['required']
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }
        
            $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);
        
            $roleRecord = (new RoleService)->update($id, $request->all());       
            $status = !empty($roleRecord) ? API::SUCCESS : API::ERROR;
        
            return API::response($status, $roleRecord);
            
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

}
