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
use App\Models\ClientCare\Doctor;
use App\Models\ClientCare\Attachment;
use App\Services\SendingEmail;

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
            'employeeLastName' => ['nullable', 'string'],
            'loa_type' => ['required', 'string']
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
        $loa_type = $validated['loa_type'];


        if($verificationDetailsType == "insurance"){
            $findPatient = Masterlist::where('member_id', strtoupper($erCardNumber))
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
            'loa_type' => $loa_type,
        ];

        $callback = [
            'client_id' => $client->id,
            'failed_count' => 0
        ];
        ClientRequest::create($clientRequest);
        Callback::create($callback);

        $request_link = config('app.frontend') . '/provider/'. $provider_id . "/" . $loa_type . "/" . $client->reference_number;

        return $request_link;



    }

    public function updateClientRequest(Request $request){

        $provider_id = $request->id;
        $refno = $request->refno;
        $loa_type = $request->loa_type;

        $doneUpdate = [2,3,4];

        // Check if the refno and provider id exists

        $checker = DB::connection('portal_request_db')
                    ->table('app_portal_clients as c')
                    ->leftJoin('app_portal_requests as r', 'r.client_id', '=', 'c.id')
                    ->where('c.reference_number', $refno)
                    ->where('r.provider_id', $provider_id)
                    ->where('r.loa_type', $loa_type)
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
                DB::raw("CONCAT(doctors.last, ', ', doctors.first, ' - ', doctors.specialization) as name"),
                'hospitals.name as provider'
            ])
            ->leftJoin('doctors', 'doctors_clinics.doctors_id', '=', 'doctors.id')
            ->rightJoin('hospitals', 'doctors_clinics.hospital_id', '=', 'hospitals.id')
            ->where('doctors_clinics.hospital_id', $provider_id)
            ->orderBy('doctors.last')
            ->get();

        $findProvider = Hospital::where('id', $provider_id)->select('name')->first();





        return response()->json([
            'isSubmitted' => false,
            'data' => $checker,
            'doctors' => $findDoctors,
            'provider' => $findProvider->name
        ], 200);
    }

    public function submitUpdateRequestConsultation(Request $request){

        $complaints = $request->complaint;
        $refno = $request->refno;
        $doctor_id = (int) $request->doctor;
        $doctor_name = "";

        $email = $request->email;
        $contact = $request->contact;

        $combinedComplaints = collect($complaints)
            ->pluck('label')        // get only the "label" values
            ->implode(', ');        // join them with comma + space


        $findClientId = DB::connection('portal_request_db')
                        ->table('app_portal_clients as c')
                        ->leftJoin('app_portal_requests as r', 'r.client_id', '=', 'c.id')
                        ->where('c.reference_number', $refno)
                        ->select(['c.id'])
                        ->first();

        // Find doctor
        if($doctor_id != 0){
            $doctor = Doctor::where('id', $doctor_id)->first();
            $doctor_name = $doctor->last . ", " . $doctor->first . "++" . $doctor->specialization;
        }else{
            $doctor_name = ", ++";
        }

        $clientRequestData = [
            'complaint' => $combinedComplaints,
            'doctor_id' => $doctor_id,
            'doctor_name' => $doctor_name,
            'loa_status' => "Pending Approval"
        ];
        $clientRequest = ClientRequest::where('client_id', $findClientId->id)->first();
        $clientRequest->update($clientRequestData);

        $client = Client::where('id', $findClientId->id)->first();
        $clientData = [
            'status' => 2,
            'alt_email' => $email,
            'contact' => $contact
        ];
        $client->update($clientData);

        $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name : $client->first_name . ' ' . $client->last_name;
        $time = "15 - 30";

        $this->SendEmail($patientName, $time, $client->reference_number, $client->email);

        if($client->alt_email){
            $this->SendEmail($patientName, $time, $client->reference_number, $client->alt_email);
        }

        if($client->contact){

            $sms =
            "From Lacson & Lacson:\n\nHi $patientName,\n\nYou have successfully submitted your request for LOA.\n\nOur Client Care will respond to your request within $time minutes.\n\nYour reference number is $client->reference_number\n\nThis is an auto-generated SMS. Doesn’t support replies and calls.";

            $this->SendSMS($client->contact, $sms);
        }

        return response(201);
    }

    public function submitUpdateRequestLaboratory(Request $request){

        $request->validate([
            'contact' => ['nullable'],
            'email' => ['email', 'nullable'],
            'files'   => ['required', 'array'],     // ✅ must be an array
            'files.*' => ['file', 'mimes:pdf,jpg,png'], // ✅ each item must be a file,
            'refno' => ['integer'],
            'hospital' => ['string']
        ]);

        $refno = $request->refno;
        $contact = $request->contact;
        $email = $request->email;


        $findClientId = DB::connection('portal_request_db')
                        ->table('app_portal_clients as c')
                        ->leftJoin('app_portal_requests as r', 'r.client_id', '=', 'c.id')
                        ->where('c.reference_number', $refno)
                        ->select(['c.id'])
                        ->first();

        $clientRequestData = [
            'loa_status' => "Pending Approval"
        ];

        $clientRequest = ClientRequest::where('client_id', $findClientId->id)->first();


        $client = Client::where('id', $findClientId->id)->first();
        $clientData = [
            'status' => 2,
            'alt_email' => $email,
            'contact' => $contact
        ];

        $client->update($clientData);
        $clientRequest->update($clientRequestData);

        if($request->hasFile('files')){
            foreach($request->file('files') as $file){
                $path = $file->storeAs('Self-service/LAB/' . $refno, $file->getClientOriginalName(), 'llibiapp');
                $name = $file->getClientOriginalName();

                Attachment::create([
                    'request_id' => $client->id,
                    'file_name' => $name,
                    'file_link' => config('app.DO_ENDPOINT') . "/" . $path
                ]);
            }
        }

        $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name : $client->first_name . ' ' . $client->last_name;
        $time = "30 - 45";

        $this->SendEmail($patientName, $time, $client->reference_number, $client->email);

        if($client->alt_email){
            $this->SendEmail($patientName, $time, $client->reference_number, $client->alt_email);
        }

        if($client->contact){

            $sms =
            "From Lacson & Lacson:\n\nHi $patientName,\n\nYou have successfully submitted your request for LOA.\n\nOur Client Care will respond to your request within $time minutes.\n\nYour reference number is $client->reference_number\n\nThis is an auto-generated SMS. Doesn’t support replies and calls.";

            $this->SendSMS($client->contact, $sms);
        }

        return response(201);


    }

    private function SendEmail($patientName, $time, $ref_no, $email){

        $body = view('send-request-loa-notification', [
            'name' => $patientName,
            'within' => $time,
            'ref' => $ref_no
        ]);

        $emailer = new SendingEmail(email: $email, body: $body, subject: 'PROVIDER PORTAL - ACCOUNT NOTIFICATION');

        $emailer->send();

        return true;

    }

    private function SendSMS($sms, $message){
        $ch = curl_init('http://192.159.66.221/goip/sendsms/');

        $parameters = array(
            'auth' => array('username' => "root", 'password' => "LACSONSMS"), //Your API KEY
            'provider' => "SIMNETWORK2",
            'number' => $sms,
            'content' => $message,
          );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //Send the parameters set above with the request
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));

        // Receive response from server
        $output = curl_exec($ch);
        curl_close($ch);
    }

}
