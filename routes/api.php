<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\ClientCare\ClientRequestController;
use App\Http\Controllers\ClientCare\DesktopClientRequestController;
use App\Http\Controllers\ClientCare\ErrorLogsController;
use App\Http\Controllers\CsvUploaderController;
use Vinkla\Hashids\Facades\Hashids;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


// Mobile
Route::get('/get-provider-id', [ClientRequestController::class, 'getProviderId']);
Route::get('/encrypt-provider', function () {


    $providerIds = [
        '300' => 'The Medical City Clinic Ali Mall',
        '145' => 'The Medical City Clinic Antipolo',
        '1127' => 'The Medical City Clinic Araneta',
        '1950' => 'The Medical City Clinic - Ayala Capitol Central Ba...',
        '1771' => 'The Medical City Clinic - Cebu',
        '1268' => 'The Medical City Clinic Ayala Malls Feliz',
        '240' => 'The Medical City Clinic Trinoma',
        '1974' => 'The Medical City Clinic - Dumaguete',
        '794' => 'The Medical City Clinic Eastwood Branch',
        '1594' => 'The Medical City Clinic Estancia',
        '541' => 'The Medical City Clinic Fisher Mall',
        '335' => 'The Medical City Clinic Gateway',
        '539' => 'The Medical City Clinic SM FTI',
        '1850' => 'The Medical City Clinic SM Hypermarket Imus',
        '1908' => 'The Medical City Clinic - SM Hypermarket Lapu-Lapu',
        '1164' => 'The Medical City Clinic SM Shaw',
        '620' => 'The Medical City Clinic Market! Market!',
        '1955' => 'The Medical City Clinic Robinsons Antipolo',
        '191' => 'The Medical City Clinic Cainta',
        '1815' => 'The Medical City Clinic Robinsons Galleria South',
        '976' => 'The Medical City Clinic San Lorenzo Place',
        '1237' => 'The Medical City Clinic Santolan Town Plaza',
        '750' => 'The Medical City Clinic SM Cabanatuan',
        '1852' => 'The Medical City Clinic SM Caloocan',
        '1904' => 'The Medical City Clinic - SM CDO Downtown Premiere',
        '1394' => 'The Medical City Clinic SM Dagupan',
        '39' => 'The Medical City SM East Ortigas',
        '1893' => 'The Medical City Clinic - SM General Santos',
        '1710' => 'The Medical City SM Grand Central',
        '1803' => 'The Medical City Clinic - SM City Iloilo',
        '331' => 'The Medical City Clinic SM Lipa',
        '760' => 'The Medical City Clinic SM MOA',
        '355' => 'The Medical City Clinic SM Manila',
        '268' => 'The Medical City Clinic SM Marikina',
        '513' => 'The Medical City Clinic SM Masinag',
        '1783' => 'The Medical City Clinic SM Tunasan Muntinlupa',
        '464' => 'The Medical City Clinic SM North Annex',
        '348' => 'The Medical City Clinic SM Novaliches',
        '1811' => 'The Medical City Clinic - SM City Olongapo',
        '1820' => 'The Medical City Clinic - SM City Roxas',
        '1168' => 'The Medical City Clinic @ SM South Tower',
        '507' => 'The Medical City Clinic SM Sta. Mesa',
        '1739' => 'The Medical City Clinic SM Sta Rosa Branch',
        '535' => 'The Medical City Clinic SM Sucat',
        '1639' => 'The Medical City Clinic SM Taytay',
        '907' => 'The Medical City Clinic SM Valenzuela',
        '613' => 'The Medical City Clinic SM Light Mall',
        '171' => 'The Medical City Clinic Southwoods Mall',
        '508' => 'The Medical City Clinic Timog',
        '592' => 'The Medical City Clinic UP Town Center',
        '301' => 'The Medical City Clinic Victory Mall',
        '1671' => 'The Medical City Clinic Waltermart Bacoor',
        '619' => 'The Medical City Clinic Waltermart Bicutan',
        '1851' => 'The Medical City Clinic Waltermart Gapan',
        '323' => 'The Medical City Clinic Waltermart Makati',
        '1593' => 'The Medical City Clinic Waltermart Malolos',
        '751' => 'The Medical City Clinic Waltermart San Fernando',
        '1600' => 'The Medical City Clinic Waltermart Sta. Maria',
        '1137' => 'The Medical City Clinic Waltermart Taytay',
        '194' => 'The Medical City Marikina Clinic',
        '509' => 'The Medical City Clinic Monte Carlo / Il Centro',
        '307' => 'The Medical City Clinic Commonwealth',
        '1439' => 'The Medical City Clinic SM San Jose Del Monte',
        '1946' => 'The Medical City Clinic Robinsons Puerto Princesa Palawan',
        '1978' => 'The Medical City Clinic SM Sun Mall',
        '1979' => 'The Medical City Clinic SM City Tuguegarao',
        '540' => 'The Medical City Clinic Robinsons Magnolia',
        '540' => 'The Medical City Clinic Robinsons Magnolia Ext.',
        '1980' => 'The Medical City Clinic SM City Cauayan',
        '169' => 'The Medical City Clinic SM Fairview',
        '192' => 'The Medical City Congressional Clinic',
        '908' => 'The Medical City Clinic SM San Lazaro',
        '614' => 'The Medical City Clinic SM San Mateo',
        '234' => 'Healthdev Integrative Clinics Inc. Quezon City',
        '1407' => '3 Star Medical Clinic and Diagnostic Center',
        '96' => 'Chong Hua Hospital',
        '306' => 'St. Lukes Medical Center - Global City',
        '257' => 'Marikina Valley Medical Center',
        '643' => 'Tooth Works General Dentistry and Orthodontics (FRC, PGRC & WECARE)',
        '18' => 'Makati Medical Center',
        '669' => 'Chong Hua Hospital Mandaue & Cancer Center',
    ];

    $hashedProviders = [];

    foreach($providerIds as $key => $value){

        $hashed = Hashids::encode($key);

        $hashedProviders[$key] = [$value, "/provider/$hashed"];

    }

    return $hashedProviders;

});
Route::get('/update-client-request', [ClientRequestController::class, 'updateClientRequest']);
Route::post('/submit-client-request', [ClientRequestController::class, 'submitRequest']);
Route::post('/submit-update-request/consultation', [ClientRequestController::class, 'submitUpdateRequestConsultation']);
Route::post('/submit-update-request/laboratory', [ClientRequestController::class, 'submitUpdateRequestLaboratory']);

// Desktop
Route::get('/client-search-hospital', [DesktopClientRequestController::class, 'searchHospital']);
Route::get('/client-search-doctor', [DesktopClientRequestController::class, 'searchDoctor']);
Route::post('/submit-request-consultation', [DesktopClientRequestController::class, 'submitRequestConsultation']);
Route::post('/submit-request-laboratory', [DesktopClientRequestController::class, 'submitRequestLaboratory']);
Route::get('/search-complaint', [DesktopClientRequestController::class, 'searchComplaint']);
Route::post('/validate-reimbursement', [DesktopClientRequestController::class, 'validateReimbursement']);
Route::post('/submit-followup-request', [DesktopClientRequestController::class, 'submitFollowUpRequest']);

// Error logs
Route::post('/error-logs', [ErrorLogsController::class, 'UpdateErrorLog']);

Route::post('/csv/import', [CsvUploaderController::class, 'import']);

// Email Preview Route
// Route::get('/preview', function () {
//         return view('send-follow-up-request-notification', [
//             'patientName' => 'John Doe',
//         ]);
//     });
