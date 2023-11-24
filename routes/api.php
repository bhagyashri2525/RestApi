<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ZoomController;

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
Route::delete('company/{id}/delete',[CompanyController::class,'destroy']);

//event routes
Route::get('event',[EventController::class,'index']);
Route::post('event',[EventController::class,'store']);
Route::get('event/{id}',[EventController::class,'show']);
Route::put('event/{id}/update',[EventController::class,'update']);
Route::delete('event/{id}/delete',[EventController::class,'destroy']);

//report routes
Route::get('report',[ReportController::class,'index']);
Route::post('report',[ReportController::class,'store']);
Route::get('report/{id}',[ReportController::class,'show']);
Route::put('report/{id}/update',[ReportController::class,'update']);
Route::delete('report/{id}/delete',[ReportController::class,'destroy']);

//zoom routes
Route::get('zoom',[ZoomController::class,'index']);
Route::post('zoom',[ZoomController::class,'store']);
Route::get('zoom/{id}',[ZoomController::class,'show']);
Route::put('zoom/{id}/update',[ZoomController::class,'update']);
Route::delete('zoom/{id}/delete',[ZoomController::class,'destroy']);
?>
