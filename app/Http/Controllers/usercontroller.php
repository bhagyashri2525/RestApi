<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //get
    public function index()
    {
        $user = User::all();
        if($user->count() > 0) {
            return response()->json([
                'status' => 200,
                'user list' => $user
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'user list' => 'record not found'
            ], 404);

        }
    }

    //store
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
            $user = new User();
            $user->id = $request->id;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = $request->password;
            return response()->json([
            'message' => 'user created',
            'status' => 'success',
            'data' => $user

            ]);
        }

    }

    //get by id
    public function show($id)
    {
        $user = User::find($id);
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
    public function update(Request $request, int $id)
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
            $user = User::find($id);
            if($user) {
                $user->name = $request->name;
                $user->email = $request->email;
                $user->update();
                return response()->json([
                    'status' => 200,
                    'message' => 'user updated',
                    ], 200);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'something went wrong!',
                    ], 404);
            }
        }
    }

    //delete by id
    public function destroy($id)
    {
        $user = User::find($id);
        if($user) {
            $user->delete();
            return response()->json([
                'status' => 200,
                'message' => "user deleted."
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "user not deleted."
            ], 404);
        }
    }
}
