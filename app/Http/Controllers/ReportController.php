<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $report = Report::all();
        if($report->count() > 0) {
            return response()->json([
                'status' => 200,
                'report list' => $report
            ], 200);
        } 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            ]);
            if($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'error' => $validator->messages()
                ], 422);
            } else {
                $report = new Report();
                $report->id = $request->id;
                $report->name = $request->name;
                $report->email = $request->email;
                $report->start_time = $request->start_time;
                $report->save();
                return response()->json([
                'message' => 'report created',
                'status' => 'success',
                'data' => $report
                ]);
            }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $report = Report::find($id);
        if($report) {
            return response()->json([
                'status' => 200,
                'report' => $report
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "no such report found!"
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
            'email' => 'required|email|max:100',
            ]);
        if($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => $validator->messages()
            ], 422);
        } else {
            $report = Report::find($id);
            if($report) {
                $report->name = $request->name;
                $report->email = $request->email;
                $report->start_time = $request->start_time;
                $report->update();
                return response()->json([
                    'status' => 200,
                    'message' => 'report updated',
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
        $report = Report::find($id);
        if($report) {
            $report->delete();
            return response()->json([
                'status' => 200,
                'message' => "report deleted."
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "report not deleted."
            ], 404);
        }
    }
}
