<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientCare\DesktopClientRequestController;







Route::middleware('corporate_api_key')->group(function (){

    Route::post('/portal/consultation-request', [DesktopClientRequestController::class, 'submitRequestConsultation']);
    Route::post('/portal/laboratory-request', [DesktopClientRequestController::class, 'submitRequestLaboratory']);
});


