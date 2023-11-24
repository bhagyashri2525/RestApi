<?php

namespace App\Http\Controllers;

use App\Models\Zoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $zoom = Zoom::all();
        if($zoom->count() > 0) {
            return response()->json([
                'status' => 200,
                'zoom list' => $zoom
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
            'slug' => 'required|string|max:100',
            ]);
            if($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'error' => $validator->messages()
                ], 422);
            } else {
                $zoom = new Zoom();
                $zoom->id = $request->id;
                $zoom->name = $request->name;
                $zoom->url = $request->url;
                $zoom->email = $request->email;
                $zoom->start_time = $request->start_time;
                $zoom->save();
                return response()->json([
                'message' => 'zoom created',
                'status' => 'success',
                'data' => $zoom
                ]);
            }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $zoom = Zoom::find($id);
        if($zoom) {
            return response()->json([
                'status' => 200,
                'zoom' => $zoom
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "no such zoom found!"
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
            $zoom = Zoom::find($id);
            if($zoom) {
                $zoom->name = $request->name;
                $zoom->url = $request->url;
                $zoom->email = $request->email;
                $zoom->start_time = $request->start_time;
                $zoom->update();
                return response()->json([
                    'status' => 200,
                    'message' => 'zoom updated',
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
        $zoom = Zoom::find($id);
        if($zoom) {
            $zoom->delete();
            return response()->json([
                'status' => 200,
                'message' => "zoom deleted."
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "zoom not deleted."
            ], 404);
        }
    }
}
