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
use App\Models\ClientCare\RemainingTbl;
use App\Models\ClientCare\RemainingTblLogs;
use App\Models\ClientCare\CompanyComplaintExcluded;
use App\Models\ClientCare\CompanyV2;

use Illuminate\Support\Carbon;

// Controller
use App\Http\Controllers\ClientCare\DesktopClientRequestController;
use App\Http\Controllers\Loa\GenerateLoaController;
use App\Http\Controllers\NotificationController;
use App\Models\ClientCare\AppLoaMonitor;
use App\Models\ClientCare\ClientErrorLogs;
use App\Models\ClientCare\Complaint;
use App\Models\ClientCare\LoaInTransit;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;


class ClientRequestController extends Controller
{
    //

    public function getProviderId(Request $request)
    {
        $decoded = Hashids::decode($request->id);

        if (empty($decoded)) {
            return response()->json(['success' => false], 422);
        }

        $providerId = $decoded[0];

        $findProvider = ProviderPortal::where('provider_id', $providerId)
            ->where('user_type', 'Hospital')
            ->first();

        if (!$findProvider) {
            return response()->json(['success' => false], 422);
        }

        return response()->json([
            'provider' => $findProvider,
            'success' => true
        ]);
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

        $now = Carbon::now();


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

            $errorData = [
                'dependent_dob' => $patientType == "dependent" ? $dob : null,
                'dependent_first_name' => $patientType == "dependent" ? $patientFirstName : null,
                'dependent_last_name' => $patientType == "dependent" ? $patientLastName : null,
                'dependent_member_id' => $patientType == "dependent" ? $erCardNumber : null,
                'dob' => $patientType == "employee" ? $dob : null,
                'first_name' => $patientType == "employee" ? $patientFirstName : strtoupper($employeeFirstName),
                'is_dependent' => $patientType == "dependent" ? 1 : null,
                'last_name' => $patientType == "employee" ? $patientLastName : strtoupper($employeeLastName),
                'member_id' => $patientType == 'employee' ? $erCardNumber : null,
                'request_type' => 1
            ];

            $error_data = ClientErrorLogs::create($errorData);

            return response()->json([
                'message' => "Cannot find the patient",
                'error_data' => $error_data
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

        if($now->greaterThan($findPatient->incepto)){
            return response()->json([
                'message' => "Your policy has already expired "
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
                                    'hosp_code'
                                    )
                            ->first();

        // Check hospital exclusion
        if(!empty($provider)){
            if (is_null($provider->hosp_code)) {
                $hospitalExclusion = false;
            } else {
                $hospitalExclusion = CompanyComplaintExcluded::where('compcode', $findPatient->company_code)
                                    ->where('hospcode', $provider->hosp_code)
                                    ->exists();
            }
        }else{
            return response()->json([
                'message' => "Provider is inactive"
            ], 404);
        }

        // It supposed to be not null
        if($hospitalExclusion){
            return response()->json([
                'message' => "$provider->name is excluded from your policy"
            ], 404);
        }

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

        $doneUpdate = [2,3,4,11];

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


        set_time_limit(600);

        $refno = $request->refno;
        $doctor_id = (int) $request->doctor;
        $doctor_name = "";

        $email = $request->email;
        $contact = $request->contact;

        $provider_id = $request->provider_id;





        $findClientId = DB::connection('portal_request_db')
                        ->table('app_portal_clients as c')
                        ->leftJoin('app_portal_requests as r', 'r.client_id', '=', 'c.id')
                        ->where('c.reference_number', $refno)
                        ->select([
                            'c.id',
                            'c.member_id'
                            ])
                        ->first();

        $client = Client::where('id', $findClientId->id)->first();

        // Find patient in masterlist to get the company_code
        $findPatient = Masterlist::where('member_id', $client->member_id)->first();

        $company = CompanyV2::where('prefix_compcode', $findPatient->company_code)->first();

        $loa_status = "Pending Approval";


        // Check if the complaint is excluded in company
        $desktopController = new DesktopClientRequestController();
        $exclusionComplaintChecker = $desktopController->ExclusionComplaintCompany($findPatient->company_code, $request->complaint);



        // Find hospital in provider db
        $provider = ProviderPortal::where('provider_id', $provider_id)
                                    ->where('user_type', 'Hospital')
                                    ->first();

        $doctor = Doctor::where('id', $doctor_id)->first();

        // Find doctor
        if($doctor_id != 0){
            $doctor_name = $doctor->last . ", " . $doctor->first . "++" . $doctor->specialization;
        }else{
            $doctor_name = ", ++";
        }

        $remaining = RemainingTbl::where('uniquecode', $findClientId->member_id)->first();

        if (!$remaining) {

            // Check if member exists in logs, if not add it
            RemainingTblLogs::firstOrCreate([
                'member_id' => $findClientId->member_id
            ]);

        } else {

            // Decrement only if allow is greater than 0
            if ($remaining->allow > 0) {
                RemainingTbl::where('uniquecode', $findClientId->member_id)
                    ->where('allow', '>', 0)
                    ->decrement('allow');
            }

        }


        // Check if remaining, exclusion and complaint requirements are satisfied
        $inscode = $findPatient->empcode;
        $compcode = $findPatient->company_code;
        $policy = $company->policy ?? "2024-11-1";
        $status = [1, 4];
        $types = ['outpatient', 'laboratory', 'consultation'];
        $fullname = "{$findPatient->last_name}, {$findPatient->first_name}";
        $totalRemaining = 0;
        $isComplaintHasApproved = false;
        $complaints = [];

        $loafiles = LoaInTransit::where('patient_name', 'like', "%$fullname%")
                                    ->whereIn('status', $status)
                                    ->where(function ($q) use ($types) {
                                        foreach ($types as $type) {
                                            $q->orWhere('type', 'like', "%$type%");
                                        }
                                    })
                                    // This is supposed to be benefit_type->policy
                                    ->where('date', '>=', $policy)
                                    ->orderBy('id', 'desc')
                                    ->get();


        $claims = AppLoaMonitor::where('compcode', $compcode)
                                ->where('inscode', $inscode)
                                ->get();

        if(count($claims) > count($loafiles)){
            $totalRemaining = 0;
        }else{
            $totalLoaTransitClaims = count($loafiles) - count($claims);
            $totalRemaining = !$remaining ? 0 - $totalLoaTransitClaims : $remaining->allow - $totalLoaTransitClaims;
        }

        if(isset($request->complaint)){
            foreach($request->complaint as $complaint){
                $nValue = strtoupper($complaint['label']);
                $complaints[] = $nValue;
                $check = Complaint::where('title', 'like', $nValue)
                                    ->where('is_status', 1)
                                    ->get();
                if(!count($check) == 0){
                    $isComplaintHasApproved = true;
                }
            }
            $complaints = implode(', ', $complaints);
        }

        if($totalRemaining >= 1 && $isComplaintHasApproved == 1 && $exclusionComplaintChecker == 0){
            if(!empty($company)){
                if($company->isAuto == 1){


                    $hospital = $request->hospital;

                    if($doctor_id != 0){
                        $doctname = $doctor->last . ", " . $doctor->first . "++" . $doctor->specialization;
                    }else{
                        $doctname = "";
                    }

                    $loa_status = "Approved";
                    $patientType = "employee";
                    $patient_name = $findPatient->last_name . ", " . $findPatient->first_name;

                    if($findPatient->relation !== "EMPLOYEE"){
                        // Find employee if dependent
                        $employee = Masterlist::where('empcode', $findPatient->empcode)
                                            ->where('relation', "EMPLOYEE")
                                            ->first();

                        $employeeName = $employee->last_name . ", " . $employee->first_name;
                        $patientType = "dependent";
                    }else{
                        $employeeName = $findPatient->last_name . ", " . $findPatient->first_name;
                    }

                    $generateLoa = new GenerateLoaController();

                    $result = $generateLoa->LOAGenerate($company->corporate_compcode,
                                                        $company->company_id_from_corporate,
                                                        $employeeName,
                                                        $patient_name,
                                                        $patientType,
                                                        $findPatient->company_name,
                                                        $hospital,
                                                        $doctname,
                                                        $complaints
                                                        );

                    $loa_number = $result['document_number'];
                    $attachment = $result['attachment'];
                    $complaint = $desktopController->CheckComplaint($request->complaint, $client);

                    $clientRequestData = [
                        'complaint' => $complaint,
                        'doctor_id' => $doctor_id,
                        'doctor_name' => $doctor_name,
                        'loa_status' => $loa_status,
                        'is_excluded' => $exclusionComplaintChecker,
                        'loa_number' => $loa_number,
                        'loa_attachment' => env('DO_LLIBI_CDN_ENDPOINT') . '/loa/generated/' . $loa_number

                    ];
                    $clientRequest = ClientRequest::where('client_id', $findClientId->id)->first();
                    $clientRequest->update($clientRequestData);

                    $clientData = [
                        'status' => 11,
                        'alt_email' => $email,
                        'contact' => $contact,
                        'remaining' => !$remaining ? null : $remaining->allow
                    ];
                    $client->update($clientData);

                    $hospital = Hospital::where('id', $provider_id)->first();
                    $accept_eloa = $hospital->accept_eloa;

                    if($accept_eloa){
                        $statusRemarks = 'Your LOA request has been approved. Your LOA Number is ' . '<b>'. $loa_number . '</b>' . '. '. '<br /><br />' .'You may print a copy of your LOA and present it to the accredited provider upon availment or you may present your (1) ER card or (2) LOA number together with any valid government ID as this provider now accepts e-LOA';
                    }else{
                        $statusRemarks = 'Your LOA request has been approved. Your LOA Number is ' . '<b>'. $loa_number . '</b>' . '. '. '<br /><br />' .'Please print a copy of your LOA and present it to the accredited provider upon availment.';
                    }


                    $homepage = "https://admin.portal.llibi.app";

                    $feedbackUrl = $homepage . '/feedback/?q=' . Str::random(64)
                        . '&rid=' . $client->id
                        . '&compcode=' . $findPatient->company_code
                        . '&memid=' . $findPatient->member_id
                        . '&reqstat=' . $client->status;

                    $feedbackLink = '
                    <div>
                        We value your feedback: <a href="'.$feedbackUrl.'">Please click here</a>
                    </div>
                    <div>
                        <a href="'.$feedbackUrl.'">
                        <img src="https://llibi-storage.sgp1.cdn.digitaloceanspaces.com/Self-service/Images/ccportal_1.jpg" alt="Feedback Icon" width="300">
                        </a>
                    </div>
                    <br /><br />';

                    $body = array(
                        'body' => view('send-request-loa', [
                            'name' =>  $patientType == "employee" ? strtoupper($patient_name) : strtoupper($employeeName),
                            'dependent' => $patientType == "employee" ? null : $patient_name,
                            'statusRemarks' => $statusRemarks,
                            'is_accept_eloa' => $accept_eloa,
                            'ref' => $client->reference_number,
                            'feedbackLink' => $feedbackLink,
                        ]),
                        'attachment' => $attachment
                    );

                    $sendEmail = (new NotificationController)->EncryptedPDFMailNotification($patient_name, $client->email, $body);
                    if(isset($email)){
                        (new NotificationController)->EncryptedPDFMailNotification($patient_name, $email, $body);
                    }

                    if($sendEmail){

                        if($contact){
                            $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name
                                        : $client->first_name . ' ' . $client->last_name;
                            $sms =
                            "From Lacson & Lacson:\n\nHi $patientName,\n\nYour request have successfully approved.\n\nYour reference number is $client->reference_number";
                            $this->SendSMS($contact, $sms);
                        }


                        return response()->json([
                            'isAuto' => true
                        ], 201);

                    }else{
                        return response()->json([
                            'error' => "Error in generating LOA"
                        ]);
                    }


                }
            }
        }

        $complaint = $desktopController->CheckComplaint($request->complaint, $client);

        $clientRequestData = [
            'complaint' => $complaint,
            'doctor_id' => $doctor_id,
            'doctor_name' => $doctor_name,
            'loa_status' => $loa_status
        ];
        $clientRequest = ClientRequest::where('client_id', $findClientId->id)->first();
        $clientRequest->update($clientRequestData);

        $client = Client::where('id', $findClientId->id)->first();
        $clientData = [
            'status' => 2,
            'alt_email' => $email,
            'contact' => $contact,
            'remaining' => !$remaining ? null : $remaining->allow
        ];
        $client->update($clientData);

        $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name : $client->first_name . ' ' . $client->last_name;
        $time = "15 - 30";

        $this->SendEmailHospital($provider->provider, $time, $client->reference_number, $client->email, $patientName);

        if($client->alt_email){
            $this->SendEmailPatient($patientName, $time, $client->reference_number, $client->alt_email);
        }

        if($client->contact){

            $sms =
            "From Lacson & Lacson:\n\nHi $patientName,\n\nYou have successfully submitted your request for LOA.\n\nOur Client Care will respond to your request within $time minutes.\n\nYour reference number is $client->reference_number\n\nThis is an auto-generated SMS. Doesn’t support replies and calls.";

            $this->SendSMS($client->contact, $sms);
        }

        if($provider->notification_sms){
            $smsProvider =
            "This is contact sms from provider";

            $this->SendSMS($provider->notification_sms, $smsProvider);
        }

        return response()->json([
            'isAuto' => false
        ], 201);
    }

    public function submitUpdateRequestLaboratory(Request $request){
        $request->validate([
            'contact' => ['nullable'],
            'email' => ['email', 'nullable'],
            'files'   => ['required', 'array'],     // ✅ must be an array
            'files.*' => ['file', 'mimes:pdf,jpg,png'], // ✅ each item must be a file,
            'refno' => ['integer'],
            'hospital' => ['string'],
            'provider_id' => ['integer']
        ]);

        $refno = $request->refno;
        $contact = $request->contact;
        $email = $request->email;
        $provider_id = $request->provider_id;


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

        // Find provider in provider db
        $provider = ProviderPortal::where('provider_id', $provider_id)
                                    ->where('user_type', 'Hospital')
                                    ->first();


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

        $this->SendEmailHospital($provider->provider, $time, $client->reference_number, $client->email, $patientName);

        if($client->alt_email){
            $this->SendEmailPatient($patientName, $time, $client->reference_number, $client->alt_email);
        }

        if($client->contact){

            $sms =
            "From Lacson & Lacson:\n\nHi $patientName,\n\nYou have successfully submitted your request for LOA.\n\nOur Client Care will respond to your request within $time minutes.\n\nYour reference number is $client->reference_number\n\nThis is an auto-generated SMS. Doesn’t support replies and calls.";

            $this->SendSMS($client->contact, $sms);
        }

        if($provider->notification_sms){
            $smsProvider =
            "This is contact sms from provider";

            $this->SendSMS($provider->notification_sms, $smsProvider);
        }

        return response(201);


    }

    private function SendEmailHospital($provider_name, $time, $ref_no, $email, $patient_name){

        $body = view('send-request-loa-notification-hospital', [
            'provider_name' => $provider_name,
            'within' => $time,
            'ref' => $ref_no,
            'patient_name' => $patient_name
        ]);

        $emailer = new SendingEmail(email: $email, body: $body, subject: 'CLIENT CARE PORTAL - ACCOUNT NOTIFICATION');

        $emailer->send();

        return true;

    }

    private function SendEmailPatient($patientName, $time, $ref_no, $email){

        $body = view('send-request-loa-notification-patient', [
            'name' => $patientName,
            'within' => $time,
            'ref' => $ref_no
        ]);

        $emailer = new SendingEmail(email: $email, body: $body, subject: 'CLIENT CARE PORTAL - ACCOUNT NOTIFICATION');

        $emailer->send();

        return true;

    }

    private function SendSMS($sms, $message){
        $ch = curl_init('http://192.159.66.221/goip/sendsms/');

        $parameters = array(
            'auth' => array('username' => "root", 'password' => "LACSONSMS"), //Your API KEY
            'provider' => "SIMNETWORK",
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
