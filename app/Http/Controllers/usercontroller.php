<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Validator;
use Illuminate\Support\Facades\Hash;

class usercontroller extends Controller
{
    public function register(Request $request)
    {
        $validator =  request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);
    
        return response()->json(["message" => "success"]); 
    
        // if ($validator->fails()) {
        //     return response()->json($validator->errors());
        //   } 
        //   else {
        //     return response()->json(["message" => "success"]); 
        // }
        // if ($validator->fails()) {
        //     return response()->json($validator->errors());
        //   } else {
        //      // do something
        //  }


            $user=  User::create ([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),

        ]);
        return response()->json([
            'message'=>'user registered',
            'user'=>$user
        ]);
    }

}
