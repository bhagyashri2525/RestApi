<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\Utils\ApiService as API;
use Exception;
use App\Services\Company\CompanyService;
use App\Services\Utils\CommonService;

class DepartmentController extends Controller
{
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
                $departments = (new CompanyService())->departmentList($companyId);
                return API::response(API::SUCCESS, ['list' => $departments]);
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

            $company = (new CompanyService())->details($companyId);

            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            } else {
                $validator = Validator::make($request->all(), [
                    'name' => ['required', 'max:50'],
                    'description' => ['nullable', 'max:255'],
                ]);

                if ($validator->fails()) {
                    return API::response(API::FAIL, [], $validator->messages()->first());
                }

                // Restriction on Dummmy Department
                $compType = strtolower($company->type);
                if (isset($compType) && $compType == Company::COMPANY_TYPES['trial']) {
                    if (isset($company->departments) && count($company->departments) >= Company::MAX_DUMMY_DEPARTMENTS_COUNT) {
                        return API::response(API::FAIL, [], 'Department creation limit reached!');
                    }
                }
                //End

                if (!empty($company->departments)) {
                    $isDepartmentNameExist = false;
                    foreach ($company->departments as $department) {
                        if ($department['name'] == $request->name) {
                            $isDepartmentNameExist = true;
                        }
                    }

                    if ($isDepartmentNameExist) {
                        $alert = API::alert('warning', 'Department name already exist.');
                        return API::response(API::FAIL, ['alert' => $alert]);
                    }
                }

                $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);

                $cleanupData = $request->only(['name', 'description', 'is_active']);
                $company = (new CompanyService())->createDepartment($companyId, $cleanupData);

                $alert = API::alert('success', 'Department information was saved successfully.');
                return API::response(API::SUCCESS, ['company' => $company, 'alert' => $alert]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }

    public function update(Request $request, $companyId, $departmentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'max:50'],
                'description' => ['nullable', 'max:255'],
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            if (empty($companyId)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            }

            $company = (new CompanyService())->details($companyId);
            if (empty($company)) {
                $alert = API::alert('warning', 'Company not found.');
                return API::response(API::FAIL, ['alert' => $alert]);
            } else {
                $departments = $company->departments;
                // print_r($departments);exit();

                $slugObject = (new CommonService())->proccessSlug($departmentId);
                $departmentId = $slugObject->id;
                $departmentIndex = $slugObject->index;
                if (!isset($departments[$departmentIndex])) {
                    $alert = API::alert('warning', 'Department not found.');
                    return API::response(API::FAIL, ['alert' => $alert]);
                } elseif ($departments[$departmentIndex]['id'] != $departmentId) {
                    $alert = API::alert('warning', 'Department not found.');
                    return API::response(API::FAIL, ['alert' => $alert]);
                }

                if (!empty($company->departments)) {
                    $isDepartmentNameExist = false;

                    foreach ($company->departments as $deptIndex => $department) {
                        if ($departmentIndex != $deptIndex && $department['name'] == $request->name) {
                            $isDepartmentNameExist = true;
                        }
                    }

                    if ($isDepartmentNameExist) {
                        $alert = API::alert('warning', 'Department name already exist.');
                        return API::response(API::FAIL, ['alert' => $alert]);
                    }
                }

                $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);
                $cleanupData = $request->only(['id', 'name', 'description', 'is_active']);

                $company = (new CompanyService())->updateDepartment($companyId, $departmentIndex, $cleanupData);

                $alert = API::alert('success', 'Company information was saved successfully.');
                return API::response(API::SUCCESS, ['company' => $company, 'alert' => $alert]);
            }
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }

            return API::response(API::ERROR);
        }
    }

    public function destroy(Request $request, $companyId, $departmentId)
    { 
        try {
            if(isset($companyId) && isset($departmentId) && $companyId && $departmentId) {
                $company = Company::find($companyId);
                if(!$company) {
                    return API::response(API::FAIL, ['alert' => 'Company not found.']);
                }
                $departments = $company->departments;
                if(is_array($departments) && count($departments)) {
                    $departmentKey = array_search(strtolower($departmentId), array_map('strtolower', array_column($departments, 'id')));
                    $departments[$departmentKey]['is_deleted'] = true;
                    $company->departments = $departments;
                    $company->save();
                    return API::response(API::SUCCESS, ['message' => 'Department has been deleted successfully.']);
                } else {
                    return API::response(API::FAIL, ['alert' => 'Deparmetnt not found.']);
                }
            } else {
                return API::response(API::FAIL, ['alert' => 'Company/Deparmetnt not found.']);
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
