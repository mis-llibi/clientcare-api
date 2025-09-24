<?php

namespace App\Http\Controllers\ClientCare;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ClientCare\ProviderPortal;
use App\Models\ClientCare\Masterlist;
use Illuminate\Validation\Rule;
use App\Models\ClientCare\Client;
use App\Models\ClientCare\ClientRequest;
use App\Models\ClientCare\Hospital;
use App\Models\ClientCare\Callback;
use Illuminate\Support\Facades\DB;
use App\Models\ClientCare\DoctorsClinics;

class ClientRequestController extends Controller
{
    //

    public function getProviderId(Request $request){
        $providerId = $request->id;

        $findProvider = ProviderPortal::where('provider_id', $providerId)
                                    ->where('user_type', 'Hospital')
                                    ->first();

        if($findProvider){
            return response()->json([
                'provider' => $findProvider,
                'success' => true
            ], 200);
        }

        return response()->json([
            'success' => false
        ], 422);
    }

    public function submitRequest(Request $request){

        $validated = $request->validate([
            'dob' => ['required', Rule::date()->format('Y-m-d')],
            'erCardNumber' => ['string', 'nullable'],
            'patientFirstName' => ['string', 'nullable'],
            'patientLastName' => ['string', 'nullable'],
            'patientType' => ['string', 'nullable'],
            'provider' => ['required', 'string'],
            'provider_id' => ['required', 'integer'],
            'provider_email' => ['required', 'string'],
            'verificationDetailsType' => ['required', 'string'],
            'employeeFirstName' => ['nullable', 'string'],
            'employeeLastName' => ['nullable', 'string']
        ]);



        $verificationDetailsType = $validated['verificationDetailsType'];
        $erCardNumber = $validated['erCardNumber'];
        $dob = $validated['dob'];
        $provider_id = $validated['provider_id'];
        $provider_email = $validated['provider_email'];
        $patientType = $validated['patientType'];
        $patientLastName = $validated['patientLastName'];
        $patientFirstName = $validated['patientFirstName'];
        $employeeFirstName = $validated['employeeFirstName'] ?? null;
        $employeeLastName = $validated['employeeLastName'] ?? null;


        if($verificationDetailsType == "insurance"){
            $findPatient = Masterlist::where('member_id', $erCardNumber)
                                        ->where('birth_date', $dob)
                                        ->first();
        }else{
            $findPatient = Masterlist::where('last_name', strtoupper($patientLastName))
                                    ->where('first_name', strtoupper($patientFirstName))
                                    ->where('birth_date', $dob)
                                    ->first();
        }



        // Validate if we find the patient
        if(!$findPatient){
            return response()->json([
                'message' => "Cannot find the patient"
            ], 404);
        }

        // Validate if the patient is dependent but it doesn't select "Patient is Dependent"
        if($findPatient->relation != "EMPLOYEE" && $patientType != "dependent"){
            return response()->json([
                'message' => "Select the Patient is Dependent"
            ], 404);
        }

        if($findPatient->relation == "EMPLOYEE" && $patientType == "dependent"){
            return response()->json([
                'message' => "Select the Patient is Employee"
            ], 404);
        }

        // Find provider details in Hospital DB
        $provider = Hospital::where('id', $provider_id)
                            ->where('status', 1)
                            ->select(
                                    'id',
                                    'name',
                                    'add1',
                                    'city',
                                    'state',
                                    'email1',
                                    )
                            ->first();

        $client = [
            'request_type' => 1,
            'reference_number' => strtotime('now'),
            'email' => $provider_email,
            'member_id' => $patientType == "employee" ? $findPatient->member_id : null,
            'first_name' => $patientType == "employee" ? $findPatient->first_name : strtoupper($employeeFirstName),
            'last_name' => $patientType == "employee" ? $findPatient->last_name : strtoupper($employeeLastName),
            'dob' => $patientType == "employee" ? $dob : null,
            'is_dependent' => $patientType == "dependent" ? 1 : null,
            'dependent_member_id' => $patientType == "dependent" ? $findPatient->member_id : null,
            'dependent_first_name' => $patientType == "dependent" ? $findPatient->first_name : null,
            'dependent_last_name' => $patientType == "dependent" ? $findPatient->last_name : null,
            'dependent_dob' => $patientType == "dependent" ? $dob : null,
            'status' => 1,
            'platform' => 'qr'
        ];


        $client = Client::create($client);

        $clientRequest = [
            'client_id' => $client->id,
            'member_id' => $findPatient->member_id,
            'provider_id' => $provider->id,
            'provider'    =>
                ($provider->name ?? "") . "++" .
                ($provider->add1 ?? "") . "++" .
                ($provider->city ?? "") . "++" .
                ($provider->state ?? "") . "++" .
                ($provider->email1 ?? ""),
            'loa_type' => "consultation",
        ];

        $callback = [
            'client_id' => $client->id,
            'failed_count' => 0
        ];
        ClientRequest::create($clientRequest);
        Callback::create($callback);

        $request_link = config('app.frontend') . '/provider/' . $provider_id . "/" . $client->reference_number . '?provider=' . $provider->name;

        return $request_link;



    }

    public function updateClientRequest(Request $request){

        $provider_id = $request->id;
        $refno = $request->refno;

        $doneUpdate = [2,3,4];

        // Check if the refno and provider id exists

        $checker = DB::connection('portal_request_db')
                    ->table('app_portal_clients as c')
                    ->leftJoin('app_portal_requests as r', 'r.client_id', '=', 'c.id')
                    ->where('c.reference_number', $refno)
                    ->where('r.provider_id', $provider_id)
                    ->select(
                            'c.reference_number',
                            DB::raw("
                                CASE
                                    WHEN c.is_dependent = 1
                                        THEN c.dependent_last_name
                                    ELSE c.last_name
                                END as patient_last_name
                            "),
                            DB::raw("
                                CASE
                                    WHEN c.is_dependent = 1
                                        THEN c.dependent_first_name
                                    ELSE c.first_name
                                END as patient_first_name
                            "),
                            'c.status'
                            )
                    ->first();



        if(!$checker){
            return response()->json([
                'message' => 'There is no request to the reference number provided, please retype then try again'
            ], 404);
        }

        if(in_array($checker->status, $doneUpdate)){
            return response()->json([
                'isSubmitted' => true
            ], 200);
        }

        $findDoctors = DoctorsClinics::select([
                'doctors_clinics.doctors_id as value',
                DB::raw("CONCAT(doctors.last, ', ', doctors.first, ' - ', doctors.specialization) as name")
            ])
            ->leftJoin('doctors', 'doctors_clinics.doctors_id', '=', 'doctors.id')
            ->rightJoin('hospitals', 'doctors_clinics.hospital_id', '=', 'hospitals.id')
            ->where('doctors_clinics.hospital_id', $provider_id)
            ->orderBy('doctors.last')
            ->get();





        return response()->json([
            'isSubmitted' => false,
            'data' => $checker,
            'doctors' => $findDoctors
        ], 200);
    }
}
