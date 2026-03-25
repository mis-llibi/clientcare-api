<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\ClientCare\Client;
use App\Models\ClientCare\ClientRequest;
use Illuminate\Http\Request;

use App\Models\ClientCare\CompanyV2;
use Illuminate\Http\Response;

class HrController extends Controller
{
    //

    public function index(){
        return CompanyV2::where('isHR', 1)->select([
                            'id as value',
                            'name'
                        ])
                        ->get();
    }

    public function submitForms(Request $request){

        $chief_complaint = $request->chiefComplaint;
        $patient_firstname = strtoupper($request->patientFirstName);
        $patient_lastname = strtoupper($request->patientLastName);
        $patient_type = $request->patientType;
        $provider = $request->provider;
        $company_id = $request->company_id;
        $user_id = $request->user_id;



        $clientData = [
            'request_type' => 1,
            'reference_number' => strtotime('now'),
            'first_name' => $patient_type == "employee" ? $patient_firstname : null,
            'last_name' => $patient_type == "employee" ? $patient_lastname : null,
            'is_dependent' => $patient_type != "employee" ? 1 : null,
            'dependent_first_name' => $patient_type != "employee" ? $patient_firstname : null,
            'dependent_last_name' => $patient_type != "employee" ? $patient_lastname : null,
            'status' => 12,
            'user_id' => $user_id,
            'platform' => "hr"

        ];



        $client = Client::create($clientData);


        // Get Provider
        $providerSplit = explode('++', $provider);
        $providerInfo = explode('||', $provider);


        $providerIdNameSplit = explode('||', $providerSplit[0]);

        $providerCol = $providerInfo[1];
        $provider_id = (int) $providerIdNameSplit[0];



        $clientRequestData = [
            'client_id' => $client->id,
            'provider_id' => $provider_id,
            'provider' => $providerCol,
            'complaint' => $chief_complaint,
            'loa_status' => "Pending Approval",
            'is_hr' => 1
        ];



        $clientRequest = ClientRequest::create($clientRequestData);

        if($clientRequest){
            return response()->json(status:200);
        }

        return response()->json(status:400);

    }
}
