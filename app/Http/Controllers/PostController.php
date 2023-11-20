<?php

namespace App\Http\Controllers;

use App\Models\post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // return 'helloworld';
        return response()->json(['posts'=>post::get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $post = new Post;
        $post->title=$request->title;
        $post->description =$request->description;
        $post->save();
        return response()->json([
        'message'=>'post created',
        'status'=>'success',
        'data'=>$post
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(post $post)
    {
        return response()->json(['post'=>$post]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, post $post)
    {
        $post->title=$request->title;
        $post->description =$request->description;
        $post->save();
        return response()->json([
        'message'=>'post updated',
        'status'=>'success',
        'data'=>$post
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(post $post)
    {
        $post->delete();
        return response()->json(['message'=>'deleted',
        'status'=>'success'
    ]);
    }
}
