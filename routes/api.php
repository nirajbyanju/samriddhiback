<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\PermissionMatrixController;
use App\Http\Controllers\Api\V1\EmployeePermissionController;
use App\Http\Controllers\Api\V1\OptionController;
use App\Http\Controllers\Api\V1\FrontController;
use App\Http\Controllers\Api\V1\InqueryController;
use App\Http\Controllers\Api\V1\InqueryFollowupController;
use App\Http\Controllers\Api\V1\FieldVisitsController;

Route::prefix('v1')->group(function () {

    // frontend routes

    Route::post('/frontTour', [FieldVisitsController::class, 'frontTour']);
    Route::post('/frontInquery', [InqueryController::class, 'frontInquery']);

    Route::get('/property-summary', [FrontController::class, 'propertySummary']);
    Route::get('/property-details/{slug}', [FrontController::class, 'propertyDetail']);
    Route::get('/property-list', [InqueryController::class, 'propertyList']);

    //getinquery from frontend
    Route::get('/get-inquery', [FrontController::class, 'getInquery']);

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refreshToken']);




    Route::get('get-all-options', [OptionController::class, 'getAllOptions']);
    // backend routes
    Route::prefix('properties')->controller(PropertyController::class)->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::post('/', [PropertyController::class, 'store']);
        Route::get('/{property}', [PropertyController::class, 'show']);
        Route::post('/{property}', [PropertyController::class, 'update']);
        Route::delete('/{id}', [PropertyController::class, 'destroy']);
        Route::patch('/status/{id}', [PropertyController::class, 'updateStatus']);
    });

    Route::middleware('auth:sanctum')->group(function () {

        // User info
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Menu routes (accessible based on permissions)
        Route::middleware('auth:sanctum')->get('/accessible-menus', [MenuController::class, 'getAccessibleMenus']);
        Route::apiResource('menus', MenuController::class);
        Route::post('/menus/reorder', [MenuController::class, 'reorder']);

        // Permission Matrix Routes (for admin)
        Route::middleware('auth:sanctum')->prefix('permission-matrix')->group(function () {

            Route::get('/', [PermissionMatrixController::class, 'index']);
            Route::get('/grouped', [PermissionMatrixController::class, 'getMatrixGrouped']);
            Route::post('/', [PermissionMatrixController::class, 'store']);
            Route::put('/{permissionMatrix}', [PermissionMatrixController::class, 'update']);
            Route::delete('/{permissionMatrix}', [PermissionMatrixController::class, 'destroy']);
            Route::get('/employee/{roleId}', [PermissionMatrixController::class, 'getEmployeePermissions']);
            Route::post('/employee/bulk-update', [PermissionMatrixController::class, 'bulkUpdateForEmployee']);
        });

        // Employee Permission Routes
        Route::prefix('employee-permissions')->group(function () {
            Route::get('/roles', [EmployeePermissionController::class, 'getEmployeeRoles']);
            Route::get('/matrix', [EmployeePermissionController::class, 'getEmployeePermissionMatrix']);
            Route::post('/assign/{roleId}', [EmployeePermissionController::class, 'assignToEmployeeRole']);
            Route::get('/role/{roleId}/employees', [EmployeePermissionController::class, 'getEmployeesByRole']);
        });

        // Route::prefix('request-for-posts')->group(function () {
        //     Route::get('/', [RequestForPostsController::class, 'index']);
        //     Route::post('/', [RequestForPostsController::class, 'store']);
        //     Route::get('/{requestForPost}', [RequestForPostsController::class, 'show']);
        //     Route::post('/{requestForPost}', [RequestForPostsController::class, 'update']);
        //     Route::delete('/{id}', [RequestForPostsController::class, 'destroy']);
        //     Route::patch('/status/{id}', [RequestForPostsController::class, 'updateStatus']);
        // });

        Route::get('/options', [OptionController::class, 'fetchOption']);
        Route::post('/options', [OptionController::class, 'store']);
        Route::get('/options/{id}', [OptionController::class, 'getOptionById']);
        Route::put('/options/{id}', [OptionController::class, 'update']);
        Route::delete('/options/{id}/{type}', [OptionController::class, 'destroy']);
        Route::get('/showOptions', [OptionController::class, 'showOption']);
        Route::patch('/options/status/{id}', [OptionController::class, 'updateStatus']);
        Route::get('/optionMenu', [OptionController::class, 'optionMenu']);

        // fetch option dropdown
        Route::get('get-dropdown/{slug}/{module?}', [OptionController::class, 'getDropdownOptions']);

        // fetch all options
    });

    //inquery routes

    Route::apiResource('inqueries', InqueryController::class);
    Route::apiResource('inquery-followups', InqueryFollowupController::class);



    Route::prefix('property-inqueries')->group(function () {
        Route::get('/', [InqueryController::class, 'index']);
        Route::post('/', [InqueryController::class, 'store']);
        Route::get('/{inquery}', [InqueryController::class, 'show']);
        Route::post('/{inquery}', [InqueryController::class, 'update']);
        Route::delete('/{id}', [InqueryController::class, 'destroy']);
    });

    Route::prefix('properties-inqueries/followups')->group(function () {
        Route::get('/{inqueryId}', [InqueryFollowupController::class, 'index']);
        Route::post('/', [InqueryFollowupController::class, 'store']);
        Route::get('/{inqueryFollowup}', [InqueryFollowupController::class, 'show']);
        Route::post('/{inqueryFollowup}', [InqueryFollowupController::class, 'update']);
        Route::delete('/{id}', [InqueryFollowupController::class, 'destroy']);
    });


    Route::prefix('properties-fieldVisit')->group(function () {
        Route::get('/{propertyId}', [FieldVisitsController::class, 'index']);
        Route::post('/', [FieldVisitsController::class, 'store']);
        Route::get('/{fieldVisit}', [FieldVisitsController::class, 'show']);
        Route::post('/{fieldVisit}', [FieldVisitsController::class, 'update']);
        Route::delete('/{id}', [FieldVisitsController::class, 'destroy']);
        Route::patch('/status/{id}', [FieldVisitsController::class, 'updateStatus']);
    });
});
