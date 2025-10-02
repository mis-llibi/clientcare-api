<?php

namespace App\Http\Controllers\ClientCare;

use App\Http\Controllers\Controller;
use App\Models\ClientCare\Callback;
use App\Models\ClientCare\Client;
use App\Models\ClientCare\ClientRequest;
use Illuminate\Http\Request;
use App\Models\ClientCare\Hospital;
use App\Models\ClientCare\DoctorsClinics;
use App\Models\ClientCare\Doctor;
use App\Models\ClientCare\Masterlist;
use App\Services\SendingEmail;

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

        if(isset($request->provider) && $request->provider != 'undefined'){
            $provider = explode('--', $request->provider);

            $hospital = explode('||', $provider[0]);
            $provider_id = $hospital[0];
            $provider_name = $hospital[1];

            $doctor = explode('||', $provider[1]);
            $doctor_id = $doctor[0];
            $doctor_name = $doctor[1];

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

        $complaint = $this->CheckComplaint($request->complaint);

        $clientRequestData = [
            'client_id' => $client->id,
            'member_id' => $findPatient->member_id,
            'provider_id' => $provider_id,
            'provider' => $provider_name,
            'doctor_id' => $doctor_id,
            'doctor_name' => $doctor_name,
            'loa_type' => $loaType,
            'complaint' => $complaint,
            'loa_status' => $loa_status
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
            'refno' => $client->reference_number
        ], 201);





    }

    public function CheckComplaint($complaintArr){
        $complaint = [];

        if (isset($complaintArr)) {

        foreach ($complaintArr as $key => $value) {

            $nValue = strtoupper($value['label']);

            $complaint[] = $nValue;

        }

        $complaint = implode(', ', $complaint);

        }

        return $complaint;

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
