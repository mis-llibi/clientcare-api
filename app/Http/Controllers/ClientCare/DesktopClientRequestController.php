<?php

namespace App\Http\Controllers\ClientCare;

use App\Http\Controllers\Controller;
use App\Models\ClientCare\Attachment;
use App\Models\ClientCare\Callback;
use App\Models\ClientCare\Client;
use App\Models\ClientCare\ClientRequest;
use Illuminate\Http\Request;
use App\Models\ClientCare\Hospital;
use App\Models\ClientCare\DoctorsClinics;
use App\Models\ClientCare\Doctor;
use App\Models\ClientCare\Masterlist;
use App\Services\SendingEmail;
use App\Models\ClientCare\Complaint;
use App\Models\ClientCare\RemainingTbl;
use App\Models\ClientCare\RemainingTblLogs;
use App\Models\ClientCare\CompanyComplaintExcluded;
use App\Http\Controllers\Loa\GenerateLoaController;
use App\Http\Controllers\NotificationController;
use App\Models\ClientCare\AppLoaMonitor;
use App\Models\ClientCare\CompanyV2;
use App\Models\ClientCare\LoaInTransit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DesktopClientRequestController extends Controller
{
    //

    public function searchHospital(Request $request){
        $accepteloa = $request->accepteloa;

        if($accepteloa == "true"){
            $request = Hospital::where('status', 1)
                        ->where('name', 'like', '%' . $request->search . '%')
                        ->where('accept_eloa', 1)
                        ->orderBy('name', 'ASC')
                        ->limit(100)
                        ->get(['id', 'name', 'add1 as address', 'city', 'state', 'email1', 'email2', 'accept_eloa', 'hosp_code']);
        }else{
            $request = Hospital::where('status', 1)

            ->where('name', 'like', '%' . $request->search . '%')

            ->orderBy('name', 'ASC')

            ->limit(100)

            ->get(['id', 'name', 'add1 as address', 'city', 'state', 'email1', 'email2', 'accept_eloa', 'hosp_code']);
        }

        return $request;

    }

    public function searchDoctor(Request $request){

        $docs = $this->Links($request->hospitalid);



        $request = Doctor::where('status', 1)

        ->whereIn('id', $docs)

        ->where(function ($query) use ($request) {

            $query->orWhere('first', 'like', '%' . $request->search . '%')

            ->orWhere('last', 'like', '%' . $request->search . '%')

            ->orWhere('specialization', 'like', '%' . $request->search . '%');

        })

        ->orderBy('last', 'ASC')

        ->orderBy('first', 'ASC')

        ->limit(100)

        ->get(['id', 'first', 'last', 'specialization']);



        return $request;

    }

    public function Links($hospitalid){

        $request = DoctorsClinics::where('status', 1)

        ->where('hospital_id', $hospitalid)

        ->get(['doctors_id']);



        return $request;

    }

    public function submitRequestConsultation(Request $request){

        // Optionals
        $alt_email = $request->alt_email;
        $contact = $request->contact;

        $dob = $request->dob;
        $email = $request->email;
        $loaType = $request->loaType;
        $patientType = $request->patientType;
        $providerEmail2 = $request->providerEmail2;
        $verificationDetailsType = $request->verificationDetailsType;
        $employeeFirstName = $request->employeeFirstName;
        $employeeLastName = $request->employeeLastName;


        $erCardNumber = $request->erCardNumber;
        $patientFirstName = $request->patientFirstName;
        $patientLastName = $request->patientLastName;

        $now = Carbon::now();
        $ref_no = strtotime("now");

        if($verificationDetailsType === 'insurance'){
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

        if($now->greaterThan($findPatient->incepto)){
            return response()->json([
                'message' => "Your policy has already expired"
            ], 404);
        }

        if(isset($request->provider) && $request->provider != 'undefined'){
            $provider = explode('--', $request->provider);

            $hospital = explode('||', $provider[0]);
            $provider_id = $hospital[0];
            $provider_name = $hospital[1];

            $provider_exclusion = explode('++', $provider_name);
            $provider_name_exclusion = $provider_exclusion[0];

            $doctor = explode('||', $provider[1]);
            $doctor_id = $doctor[0];
            $doctor_name = $doctor[1];




            // Find Hospcode in sync
            $hospcode = Hospital::where('id', $provider_id)
                                ->where('status', 1)
                                ->first();
            // Check hospital exclusion
            if(!empty($provider)){
                if (is_null($hospcode->hosp_code)) {
                    $hospitalExclusion = false;
                } else {
                    $hospitalExclusion = CompanyComplaintExcluded::where('compcode', $findPatient->company_code)
                                        ->where('hospcode', $hospcode->hosp_code)
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
                    'message' => "$provider_name_exclusion is excluded from your policy"
                ], 404);
            }


        }else{

            return response()->json([
                'message' => "Hospital Error"
            ], 404);

        }

        $company = CompanyV2::where('prefix_compcode', $findPatient->company_code)->first();

        $loa_status = "Pending Approval";
        $remaining = RemainingTbl::where('uniquecode', $findPatient->member_id)->first();

        if (!$remaining) {

            // Check if member exists in logs, if not add it
            RemainingTblLogs::firstOrCreate([
                'member_id' => $findPatient->member_id
            ]);

        } else {

            // Decrement only if allow is greater than 0
            if ($remaining->allow > 0) {
                RemainingTbl::where('uniquecode', $findPatient->member_id)
                    ->where('allow', '>', 0)
                    ->decrement('allow');
            }

        }

        // Check if the complaint is excluded in company
        $exclusionComplaintChecker = $this->ExclusionComplaintCompany($findPatient->company_code, $request->complaint);


        // Check if remaining, exclusion and complaint requirements are satisfied
        $inscode = $findPatient->empcode;
        $compcode = $findPatient->company_code;
        $policy = $company->policy ?? "2024-11-1";
        $status = [1, 4];
        $types = ['outpatient', 'laboratory', 'consultation'];
        $fullname = "{$findPatient->last_name}, {$findPatient->first_name}";
        $totalRemaining = 0;
        $isComplaintHasApproved = false;
        $complaints = "";

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
                $complaints .= $complaint['label'] . " ";
                $nValue = strtoupper($complaint['label']);
                $check = Complaint::where('title', 'like', $nValue)
                                    ->where('is_status', 1)
                                    ->get();
                if(!count($check) == 0){
                    $isComplaintHasApproved = true;
                }
            }
        }
        Log::info($company);
        Log::info($totalRemaining);
        Log::info($isComplaintHasApproved);
        Log::info($exclusionComplaintChecker);
        // Validate if the remaining, complaint excluded and complaint approved is valid
        if($totalRemaining >= 1 && $isComplaintHasApproved == 1 && $exclusionComplaintChecker == 0){

            // If all of the requirements are valid, check if the company is auto generate LOA
            if(!empty($company)){

                if($company->isAuto == 1){
                    $hospital_name = explode('++', $provider_name);
                    $hospital_name = $hospital_name[0];

                    $doctname = explode('++', $doctor_name);
                    $doctname = $doctname[0] == ", " ? "" : $doctname[0];

                    $loa_status = "Approved";

                    $generateLoa = new GenerateLoaController();
                    $result = $generateLoa->LOAGenerate(
                                                            $company->corporate_compcode,
                                                            $company->company_id_from_corporate,
                                                            $employeeLastName . ", " . $employeeFirstName,
                                                            $findPatient->last_name . ", " . $findPatient->first_name,
                                                            $patientType,
                                                            $findPatient->company_name,
                                                            $hospital_name,
                                                            $doctname,
                                                            $complaints,
                                                        );

                    $loa_number = $result['document_number'];
                    $attachment = $result['attachment'];
                    $patient_name = $findPatient->last_name . ", " . $findPatient->first_name;
                    $employee_name = $employeeLastName . ", " . $employeeFirstName;

                    $clientData = [
                        'request_type' => 1,
                        'reference_number' => $ref_no,
                        'email' => $email,
                        'alt_email' => $alt_email,
                        'contact' => $contact,
                        'member_id' => $patientType == 'employee' ? $findPatient->member_id : null,
                        'first_name' => $patientType == "employee" ? $findPatient->first_name : strtoupper($employeeFirstName),
                        'last_name' => $patientType == "employee" ? $findPatient->last_name : strtoupper($employeeLastName),
                        'dob' => $patientType == "employee" ? $dob : null,
                        'is_dependent' => $patientType == "dependent" ? 1 : null,
                        'dependent_member_id' => $patientType == "dependent" ? $findPatient->member_id : null,
                        'dependent_first_name' => $patientType == "dependent" ? $findPatient->first_name : null,
                        'dependent_last_name' => $patientType == "dependent" ? $findPatient->last_name : null,
                        'dependent_dob' => $patientType == "dependent" ? $dob : null,
                        'status' => 11,
                        'provider_email2' => $providerEmail2,
                        'remaining' => !$remaining ? null : $remaining->allow
                    ];

                    $client = Client::create($clientData);
                    $complaint = $this->CheckComplaint($request->complaint, $client);

                    $clientRequestData = [
                        'client_id' => $client->id,
                        'member_id' => $findPatient->member_id,
                        'provider_id' => $provider_id,
                        'provider' => $provider_name,
                        'doctor_id' => $doctor_id,
                        'doctor_name' => $doctor_name,
                        'loa_type' => $loaType,
                        'complaint' => $complaint,
                        'loa_status' => $loa_status,
                        'is_excluded' => $exclusionComplaintChecker,
                        'loa_number' => $loa_number,
                        'loa_attachment' => env('DO_LLIBI_CDN_ENDPOINT') . '/loa/generated/' . $loa_number
                    ];

                    $callback = [
                        'client_id' => $client->id,
                        'failed_count' => 0
                    ];

                    ClientRequest::create($clientRequestData);
                    Callback::create($callback);


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
                            'name' =>  $patientType == "employee" ? strtoupper($patient_name) : strtoupper($employee_name),
                            'dependent' => $patientType == "employee" ? null : $patient_name,
                            'statusRemarks' => $statusRemarks,
                            'is_accept_eloa' => $accept_eloa,
                            'ref' => $ref_no,
                            'feedbackLink' => $feedbackLink,
                        ]),
                        'attachment' => $attachment
                    );

                    $sendEmail = (new NotificationController)->EncryptedPDFMailNotification($patient_name, $email, $body);

                    if(isset($alt_email)){
                        (new NotificationController)->EncryptedPDFMailNotification($patient_name, $alt_email, $body);
                    }

                    if($sendEmail){

                        if($client->contact){
                            $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name
                                        : $client->first_name . ' ' . $client->last_name;
                            $sms =
                            "From Lacson & Lacson:\n\nHi $patientName,\n\nYour request have successfully approved.\n\nYour reference number is $client->reference_number";
                            $this->SendSMS($client->contact, $sms);
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


        $clientData = [
            'request_type' => 1,
            'reference_number' => $ref_no,
            'email' => $email,
            'alt_email' => $alt_email,
            'contact' => $contact,
            'member_id' => $patientType == 'employee' ? $findPatient->member_id : null,
            'first_name' => $patientType == "employee" ? $findPatient->first_name : strtoupper($employeeFirstName),
            'last_name' => $patientType == "employee" ? $findPatient->last_name : strtoupper($employeeLastName),
            'dob' => $patientType == "employee" ? $dob : null,
            'is_dependent' => $patientType == "dependent" ? 1 : null,
            'dependent_member_id' => $patientType == "dependent" ? $findPatient->member_id : null,
            'dependent_first_name' => $patientType == "dependent" ? $findPatient->first_name : null,
            'dependent_last_name' => $patientType == "dependent" ? $findPatient->last_name : null,
            'dependent_dob' => $patientType == "dependent" ? $dob : null,
            'status' => 2,
            'provider_email2' => $providerEmail2,
            'remaining' => !$remaining ? null : $remaining->allow
        ];

        $client = Client::create($clientData);

        $complaint = $this->CheckComplaint($request->complaint, $client);

        $clientRequestData = [
            'client_id' => $client->id,
            'member_id' => $findPatient->member_id,
            'provider_id' => $provider_id,
            'provider' => $provider_name,
            'doctor_id' => $doctor_id,
            'doctor_name' => $doctor_name,
            'loa_type' => $loaType,
            'complaint' => $complaint,
            'loa_status' => $loa_status,
            'is_excluded' => $exclusionComplaintChecker
        ];

        $callback = [
            'client_id' => $client->id,
            'failed_count' => 0
        ];

        ClientRequest::create($clientRequestData);
        Callback::create($callback);



        $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name
                    : $client->first_name . ' ' . $client->last_name;
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

        return response()->json([
            'refno' => $client->reference_number,
            'isAuto' => false
        ], 201);
    }

    public function submitRequestLaboratory(Request $request){
        $alt_email = $request->alt_email;
        $contact = $request->contact;

        $dob = $request->dob;
        $email = $request->email;
        $loaType = $request->loaType;
        $patientType = $request->patientType;
        $providerEmail2 = $request->providerEmail2;
        $verificationDetailsType = $request->verificationDetailsType;
        $employeeFirstName = $request->employeeFirstName;
        $employeeLastName = $request->employeeLastName;

        $erCardNumber = $request->erCardNumber;
        $patientFirstName = $request->patientFirstName;
        $patientLastName = $request->patientLastName;

        $now = Carbon::now();

       if($verificationDetailsType === 'insurance'){
            $findPatient = Masterlist::where('member_id', strtoupper($erCardNumber))
                                        ->where('birth_date', $dob)
                                        ->first();
        }else{
            $findPatient = Masterlist::where('last_name', strtoupper($patientLastName))
                                    ->where('first_name', strtoupper($patientFirstName))
                                    ->where('birth_date', $dob)
                                    ->first();
        }

        if($now->greaterThan($findPatient->incepto)){
            return response()->json([
                'message' => "Your policy has already expired"
            ], 404);
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

        if(isset($request->provider) && $request->provider != 'undefined'){
            $provider = explode('--', $request->provider);

            $hospital = explode('||', $provider[0]);
            $provider_id = $hospital[0];
            $provider_name = $hospital[1];

            $loa_status = "Pending Approval";



        }else{

            return response()->json([
                'message' => "Hospital Error"
            ], 404);

        }

        $clientData = [
            'request_type' => 1,
            'reference_number' => strtotime('now'),
            'email' => $email,
            'alt_email' => $alt_email,
            'contact' => $contact,
            'member_id' => $patientType == 'employee' ? $findPatient->member_id : null,
            'first_name' => $patientType == "employee" ? $findPatient->first_name : strtoupper($employeeFirstName),
            'last_name' => $patientType == "employee" ? $findPatient->last_name : strtoupper($employeeLastName),
            'dob' => $patientType == "employee" ? $dob : null,
            'is_dependent' => $patientType == "dependent" ? 1 : null,
            'dependent_member_id' => $patientType == "dependent" ? $findPatient->member_id : null,
            'dependent_first_name' => $patientType == "dependent" ? $findPatient->first_name : null,
            'dependent_last_name' => $patientType == "dependent" ? $findPatient->last_name : null,
            'dependent_dob' => $patientType == "dependent" ? $dob : null,
            'status' => 2,
            'provider_email2' => $providerEmail2
        ];

        $client = Client::create($clientData);

        $clientRequestData = [
            'client_id' => $client->id,
            'member_id' => $findPatient->member_id,
            'provider_id' => $provider_id,
            'provider' => $provider_name,
            'loa_type' => $loaType,
            'loa_status' => $loa_status
        ];

        $callback = [
            'client_id' => $client->id,
            'failed_count' => 0
        ];

        ClientRequest::create($clientRequestData);
        Callback::create($callback);

        if($request->hasFile('files')){
            foreach($request->file('files') as $file){
                $path = $file->storeAs('Self-service/LAB/' . $client->reference_number, $file->getClientOriginalName(), 'llibiapp');
                $name = $file->getClientOriginalName();

                Attachment::create([
                    'request_id' => $client->id,
                    'file_name' => $name,
                    'file_link' => config('app.DO_ENDPOINT') . "/" . $path
                ]);
            }
        }

        $patientName = $client->is_dependent == 1 ? $client->dependent_first_name . " " . $client->dependent_last_name
                    : $client->first_name . ' ' . $client->last_name;
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

        return response()->json([
            'refno' => $client->reference_number
        ], 201);





    }

    public function CheckComplaint($complaintArr, $client){
        $complaint = [];
        $isComplaintApproved = false;

        if (isset($complaintArr)) {

        foreach ($complaintArr as $key => $value) {



            $nValue = strtoupper($value['label']);

            //$this->CheckComplaint($value['label']);

            $check = Complaint::where('title', 'like', $nValue)

            ->get();

            if (count($check) == 0) {

            Complaint::create(['title' => $nValue]);

            } else{
                $isComplaintApproved = true;
            }

            $complaint[] = $nValue;

        }

        $complaint = implode(', ', $complaint);

        }
        $client->update([
            'is_complaint_has_approved' => $isComplaintApproved
        ]);
        return $complaint;

    }

    private function SendEmail($patientName, $time, $ref_no, $email){
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


    public function searchComplaint(Request $request){

        $complaint = $request->complaint;

        $complaint_list = Complaint::where('title', 'like', "%$complaint%")
                                    ->where('is_status', 1)
                                    ->select(
                                        DB::raw('id + 20 as value'),
                                        'title as label'
                                    )
                                    ->get();

        return $complaint_list;
    }

    public function ExclusionComplaintCompany($compcode, $complaints){

        $isExcluded = false;

        if(isset($complaints)){
            foreach($complaints as $complaint){

                $label = strtoupper($complaint['label']);
                $labelWords = explode(' ', strtoupper($label));

                $findExclusion = CompanyComplaintExcluded::where('compcode', $compcode)
                                                        ->whereIn('complaint', $labelWords)
                                                        ->first();

                if($findExclusion){
                    $isExcluded = true;

                    return $isExcluded;
                }
            }
            return $isExcluded;
        }
    }

}
