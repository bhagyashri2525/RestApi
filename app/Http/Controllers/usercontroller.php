<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\Services\Utils\ApiService as API;
use App\Services\Auth\UserService;
use App\Services\Validation\ValidationService;
use App\Services\Validation\ValidationService as VS;
use App\Services\Company\CompanyService;
use App\Data\UserData;
use App\Models\Company;
use App\Models\Role;
use App\Services\Email\EmailService;
use Exception;

class UserController extends Controller
{
    //get
    public function index(Request $request, $companyId)
    {
            try {
                if (empty($companyId)) {
                    $alert = API::alert('warning', 'Company not found.');
                    return API::response(API::FAIL, ['alert' => $alert]);
                }
    
                $company = (new CompanyService())->details($companyId, ['is_active' => true]);
    
                if (empty($company)) {
                    $alert = API::alert('warning', 'Company not found.');
                    return API::response(API::FAIL, ['alert' => $alert]);
                } else {
                    $excludeUserIds = [$request->user()->id];
                    return API::response(API::SUCCESS, ['list' => (new CompanyService())->userList($companyId)]);
                }
            } catch (Exception $e) {
                if (env('APP_DEBUG')) {
                    print_r($e->getMessage());
                    // Log::error($e->getMessage());
                }
    
                return API::response(API::ERROR);
            }
    }
   
    public function store(Request $request, $companyId)
    {
        try {
            
            if (empty($companyId)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            $company = (new CompanyService())->details($companyId, ['is_active' => true]);

            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            } else {
                $validator = Validator::make($request->all(), [
                    'email' => ['required', 'email'],
                    'display_name' => ['required', 'max:60'],
                    'departments' => ['required_if:role,' . Role::DEPARTMENT_ADMIN],
                    'is_active' => ['required'],
                    'role' => ['required'],
                ]);

                if ($validator->fails()) {
                    return API::response(API::FAIL, [], $validator->messages()->first());
                }

                // Restriction on Dummmy Company
                $compType = strtolower($company->type);
                if (isset($compType) && $compType == Company::COMPANY_TYPES['trial']) {
                    $users = User::where('companies', 'contains', $company->id)->get();
                    if (isset($users) && count($users) >= Company::MAX_DUMMY_USERS_COUNT) {
                        return API::response(API::FAIL, [], 'User creation limit reached!');
                    }
                }
                //End

                $role = $request->role;
                $request->request->remove('role');

                if ($role != Role::DEPARTMENT_ADMIN) {
                    $request->request->remove('departments');
                }

                $user = User::where(['email' => $request->email])->first();

                if (!empty($user)) {
                    $user->is_active = true;
                    $user->save();

                    if ($user->companies && in_array($companyId, $user->companies)) {
                        return API::response(API::FAIL, [], 'Email already registered.');
                    }
                } else {
                    $authUser = $request->user();
                    $request->request->add(['creator_id' => $authUser->id]);
                    $request->request->add(['password'=>'']);
                    $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);
                    $request->request->remove('id');

                    $user = (new UserService())->store($request->all());
                }

                $departments = $request->departments;
                $request->request->remove('departments');

                $roles = [$role];

                (new UserService())->saveCompanyMapping($user->id, $companyId, $departments, $roles, $user->is_active);
                //exit;
                $tokenObj = (new EmailService())->generateVerificationToken($user->id, User::EMAIL_VERIFICATION_TYPE['email'], $companyId);
                // (new EmailService())->sendVerificationLink($user->id, User::EMAIL_VERIFICATION_TYPE['welcome'], $tokenObj['token_slug']);

                $alert = API::alert('success', 'User information was saved successfully.');
                return API::response(API::SUCCESS, ['alert' => $alert]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }
    //get by id
    public function show($userId)
    {
        $user = User::find($userId);
        if($user) {
            return response()->json([
                'status' => 200,
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "no such user found!"
            ], 404);
        }
    }

    //update by id
    public function update(Request $request, $companyId, $userId)
    {
        try {
            if (empty($companyId)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            $company = (new CompanyService())->details($companyId, ['is_active' => true]);

            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            } else {
                $validator = Validator::make($request->all(), [
                    'id' => ['required'],
                    'display_name' => ['required', 'max:60'],
                    'departments' => ['required_if:role,' . Role::DEPARTMENT_ADMIN],
                    'is_active' => ['required'],
                    'role' => ['required'],
                ]);

                if ($validator->fails()) {
                    return API::response(API::FAIL, [], $validator->messages()->first());
                }

                $user = User::where(['id' => $request->id])->first();

                if (empty($user)) {
                    return API::response(API::FAIL, [], 'The user not exist.');
                }

                if (!in_array($companyId, $user->companies)) {
                    return API::response(API::FAIL, [], 'Something went wrong.');
                }

                $role = $request->role;
                $request->request->remove('role');

                if ($role != Role::DEPARTMENT_ADMIN) {
                    $request->request->remove('departments');
                }
                $roles = [$role];
                $departments = $request->departments;
                $request->request->remove('departments');

                $user = (new UserService())->saveCompanyMapping($user->id, $companyId, $departments, $roles, $request->is_active);

                $alert = API::alert('success', 'User information was saved successfully.');
                return API::response(API::SUCCESS, ['alert' => $alert]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }

    //delete by id
    public function destroy(Request $request, $companyId, $userId)
    {
        try {
            $company = (new CompanyService())->details($companyId, ['is_active' => true]);

            if(!$company) {
                return API::response(API::FAIL, ['message' => 'Company not found']);
            }
            $user = (new UserService())->delete($userId, $companyId);
            if($user) {
                return API::response(API::SUCCESS, ['message' => 'User has been deleted.']);
            } else {
                return API::response(API::FAIL, ['message' => 'Error while deleting event.']);
            }
            
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }
}
