<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\Utils\ApiService as API;
use Exception;
use App\Services\Company\CompanyService;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $company = Company::all();
        if($company->count() > 0) {
            return response()->json([
                'status' => 200,
                'company list' => $company
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'company list' => 'record not found'
            ], 404);
        }
    }
   
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'display_name' => ['required', 'max:50'],
                'slug' => ['required', 'max:50'],
                'code' => ['required', 'max:50'],
                'is_active' => ['required', 'integer', 'min:0', 'max:1'],
                'description' => ['nullable', 'max:255'],
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            $existCompany = Company::where(['display_name' => $request->display_name])->first();
            if (!empty($existCompany)) {
                return API::response(API::FAIL, [], 'Company name already exist.');
            }

            $existCompany = Company::where(['slug' => $request->slug])->first();
            if (!empty($existCompany)) {
                return API::response(API::FAIL, [], 'Company slug already exist.');
            }

            $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);

            //Adding company type
            $request->request->add(['type' => Company::COMPANY_TYPES['genuine']]);

            $company = (new CompanyService())->store($request->all());
            return API::response(!empty($company) ? API::SUCCESS : API::FAIL, $company);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function show($id)
    {
        $company = (new CompanyService())->details($id);
        $status = !empty($company) ? API::SUCCESS : API::NOT_FOUND;
        return API::response($status, $company);
    }
   
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'display_name' => ['required', 'max:50'],
                'slug' => ['required', 'max:50'],
                'code' => ['required', 'max:50'],
                'description' => ['nullable', 'max:255'],
            ]);

            if ($validator->fails()) {
                return API::response(API::FAIL, [], $validator->messages()->first());
            }

            $existCompany = Company::where(['slug' => $request->slug,'display_name' => $request->display_name])
                ->where('id', '!=', $id)
                ->first();
            if (!empty($existCompany)) {
                return API::response(API::FAIL, [], 'Company slug already exist.');
            }

            $request->request->add(['is_active' => $request->is_active == 1 ? true : false]);

            $companyRecord = (new CompanyService())->update($id, $request->all());
            $status = !empty($companyRecord) ? API::SUCCESS : API::FAIL;
            return API::response($status, $companyRecord);
        } catch (Exception $e) {
            if (env('APP_DEBUG')) {
                print_r($e->getMessage());
                // Log::error($e->getMessage());
            }
            return API::response(API::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $company = Company::where(['id' => $id])->first();
            if (!empty($company)) {
                $company->delete();
                return API::response(API::SUCCESS);
            }
            return API::response(API::FAIL);
        } catch (Exception $e) {
            return API::response(API::ERROR);
        }
    }
}
