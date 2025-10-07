<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\BuildingController;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(ApiKeyMiddleware::class)->group(function () {

    Route::get('/organizations/nearby', [OrganizationController::class, 'nearby']);
    Route::get('/buildings/nearby', [BuildingController::class, 'nearby']);
    Route::get('organizations/search', [OrganizationController::class, 'search']);
    Route::get('/activities/{id}/organizations-with-descendants', [ActivityController::class, 'organizationsWithDescendants']);

    Route::apiResource('organizations', OrganizationController::class);
    Route::apiResource('buildings', BuildingController::class);
    Route::apiResource('activities', ActivityController::class);
    
    Route::get('buildings/{id}/organizations', [BuildingController::class, 'organizations']);
    Route::get('activities/{id}/organizations', [ActivityController::class, 'organizations']);


});
