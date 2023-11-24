<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100',
            ]);
            if($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'error' => $validator->messages()
                ], 422);
            } else {
                $company = new Company();
                $company->id = $request->id;
                $company->name = $request->name;
                $company->slug = $request->slug;
                $company->save();
                return response()->json([
                'message' => 'company created',
                'status' => 'success',
                'data' => $company
                ]);
            }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $company = Company::find($id);
        if($company) {
            return response()->json([
                'status' => 200,
                'company' => $company
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "no such company found!"
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100',
            ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => $validator->messages()
            ], 422);
        } else {
            $company = company::find($id);
            if($company) {
                $company->name = $request->name;
                $company->slug = $request->slug;
                $company->update();
                return response()->json([
                    'status' => 200,
                    'message' => 'company updated',
                    ], 200);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'something went wrong!',
                    ], 404);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {
        $company = company::find($id);
        if($company) {
            $company->delete();
            return response()->json([
                'status' => 200,
                'message' => "company deleted."
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "company not deleted."
            ], 404);
        }
    }
}
