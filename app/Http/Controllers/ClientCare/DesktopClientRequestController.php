<?php

namespace App\Http\Controllers\ClientCare;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientCare\Hospital;
use App\Models\ClientCare\DoctorsClinics;
use App\Models\ClientCare\Doctor;

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

  public function searchDoctor(Request $request)

  {

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


}
