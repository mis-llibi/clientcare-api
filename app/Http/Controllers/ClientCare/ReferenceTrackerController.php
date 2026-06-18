<?php

namespace App\Http\Controllers\ClientCare;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientCare\ClientRequest;
use App\Models\ClientCare\Client;


class ReferenceTrackerController extends Controller
{
    //

    public function referenceTracker(Request $request){
        $reference = $request->reference;
        $result = Client::with('clientRequest.masterlist')
                        ->where('reference_number', $reference)
                        ->get();

        return $result;

    }
}
