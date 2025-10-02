<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\ClientCare\ClientRequestController;
use App\Http\Controllers\ClientCare\DesktopClientRequestController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


// Mobile
Route::get('/get-provider-id', [ClientRequestController::class, 'getProviderId']);
Route::get('/update-client-request', [ClientRequestController::class, 'updateClientRequest']);
Route::post('/submit-client-request', [ClientRequestController::class, 'submitRequest']);
Route::post('/submit-update-request/consultation', [ClientRequestController::class, 'submitUpdateRequestConsultation']);
Route::post('/submit-update-request/laboratory', [ClientRequestController::class, 'submitUpdateRequestLaboratory']);

// Desktop
Route::get('/client-search-hospital', [DesktopClientRequestController::class, 'searchHospital']);
Route::get('/client-search-doctor', [DesktopClientRequestController::class, 'searchDoctor']);
Route::post('/submit-request-consultation', [DesktopClientRequestController::class, 'submitRequestConsultation']);
