<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event = Event::all();
        if($event->count() > 0) {
            return response()->json([
                'status' => 200,
                'event list' => $event
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'event list' => 'record not found'
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
                $event = new Event();
                $event->id = $request->id;
                $event->name = $request->name;
                $event->slug = $request->slug;
                $event->category = $request->category;
                $event->timezone = $request->timezone;
                $event->type = $request->type;
                $event->save();
                return response()->json([
                'message' => 'event created',
                'status' => 'success',
                'data' => $event
                ]);
            }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $event = Event::find($id);
        if($event) {
            return response()->json([
                'status' => 200,
                'event' => $event
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "no such event found!"
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
            $event = Event::find($id);
            if($event) {
                $event->name = $request->name;
                $event->slug = $request->slug;
                $event->category = $request->category;
                $event->timezone = $request->timezone;
                $event->type = $request->type;
                $event->update();
                return response()->json([
                    'status' => 200,
                    'message' => 'event updated',
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
        $event = Event::find($id);
        if($event) {
            $event->delete();
            return response()->json([
                'status' => 200,
                'message' => "event deleted."
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "event not deleted."
            ], 404);
        }
    }
}
