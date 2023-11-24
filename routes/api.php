<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;

//user routes
Route::get('users',[UserController::class,'index']);
Route::post('users',[UserController::class,'store']);
Route::get('users/{id}',[UserController::class,'show']);
Route::put('users/{id}/update',[UserController::class,'update']);
Route::delete('users/{id}/delete',[UserController::class,'destroy']);

//company routes
Route::get('company',[CompanyController::class,'index']);
Route::post('company',[CompanyController::class,'store']);
Route::get('company/{id}',[CompanyController::class,'show']);
Route::put('company/{id}/update',[CompanyController::class,'update']);
Route::delete('company/{user}/delete',[CompanyController::class,'destroy']);
?>
