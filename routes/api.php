<?php
use App\Services\Utils\CommonService;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ResourceController;


Route::post('register-user', [AuthController::class, 'register']);
Route::post('login-user', [AuthController::class, 'login']);
Route::post('account/verify', [AuthController::class, 'verifyAccount']);
Route::post('account/refresh-token', [AuthController::class, 'refreshToken']);
Route::post('account/welcome', [AuthController::class, 'welcomeAccount']);
Route::post('account/send-reset-password-link/', [AuthController::class, 'sendResetPasswordLink']);
Route::post('account/update-password/', [AuthController::class, 'updatePassword']);

Route::get('get-countries', [SiteController::class, 'getCountries']);
Route::get('getCountryList', [SiteController::class, 'getCountryList']);
Route::get('getTimeZoneList', [SiteController::class, 'getTimeZoneList']);
Route::post('/timezone-list',[SiteController::class,'timezoneList']);

Route::middleware('auth:api')->group(function () {
        
    //company routes
    //done
    Route::get('company',[CompanyController::class,'index']);
    Route::post('company',[CompanyController::class,'store']);
    Route::get('company/{id}',[CompanyController::class,'show']);
    Route::post('company/{id}/update',[CompanyController::class,'update']);
    Route::delete('company/{id}/delete',[CompanyController::class,'destroy']);


    //department routes
    //done
    Route::get('/{companyId}/department/list',[DepartmentController::class,'index']);
    Route::post('/{companyId}/department/create',[DepartmentController::class,'store']);
    Route::get('department/{departmentId}/',[DepartmentController::class,'show']);
    Route::post('/{companyId}/department/{departmentId}/update',[DepartmentController::class,'update']);
    Route::delete('/{companyId}/department/{departmentId}/delete',[DepartmentController::class,'destroy']);

     //user routes
     //done
    Route::get('/{companyId}/users',[UserController::class,'index']);
    Route::post('/{companyId}/users',[UserController::class,'store']);
    Route::get('users/{userId}',[UserController::class,'show']);
    Route::post('/{companyId}/users/{userId}/update',[UserController::class,'update']);
    Route::post('/{companyId}/users/{userId}/delete',[UserController::class,'destroy']);

    //event routes
    //done
    Route::prefix('/event')->group(function () {
        Route::get('/', [EventController::class,'index']);
        Route::post('/{companyId}/create', [EventController::class,'store']);
        Route::get('/{eventId}', [EventController::class,'show']);
        Route::post('/{companyId}/{eventId}/update', [EventController::class,'update']);
        Route::delete('/{eventId}/delete', [EventController::class,'destroy']);
    });

    //profile 
    //done
    Route::prefix('/profile')->group(function () {
        Route::post('perosnal-info', [ProfileController::class, 'personlInfo']);
        Route::post("update-profile-details", [ProfileController::class, 'saveProfileDetails']);
        Route::post('file-upload', [ProfileController::class, 'uploadFile']);
    });
//done
    Route::prefix('/roles')->group(function () {
        Route::resource('/', RoleController::class);
        Route::post('/create', [RoleController::class, 'store']);
        Route::post('/update/{id}', [RoleController::class, 'update']);
        Route::get('/list', [RoleController::class, 'getAll']);
    });

});

    //Event Resource
    Route::post('/add-event-resource', [ResourceController::class, 'eventResource']);
    Route::get('/resource/list', [ResourceController::class, 'Resourcelist']);
    Route::post('/resource/update/{id}', [ResourceController::class,'updateResource']);
    Route::post('/resource/delete/{id}', [ResourceController::class,'deleteResource']);
    Route::post('/resource-file-upload', [ResourceController::class,'uploadFileResource']);

    //Engagement Layout Section
    Route::post('/add-resource-layout-section', [ResourceController::class, 'ResourceLayoutSection']);
    Route::get('/resource-layout/list', [ResourceController::class, 'ResourceLayoutSectionlist']);

// //zoom routes
// Route::get('zoom',[ZoomController::class,'index']);
// Route::post('zoom',[ZoomController::class,'store']);
// Route::get('zoom/{id}',[ZoomController::class,'show']);
// Route::post('zoom/{id}/update',[ZoomController::class,'update']);
// Route::delete('zoom/{id}/delete',[ZoomController::class,'destroy']);

// //report routes
// Route::prefix('/reports')->group(function () {
//     Route::post("/participant-list", [ReportController::class, 'getParticipants']);
//     Route::post("/get-event-details", [ReportController::class, 'getEventDetails']);
//     Route::post("/get-mail-logs", [ReportController::class, 'getMailLogs']);
//     Route::post("/event-user-engagement", [ReportController::class, 'eventUserEngagement']);
//     Route::post("/event-live-users", [ReportController::class, 'eventLiveUsers']);
// });

?>
