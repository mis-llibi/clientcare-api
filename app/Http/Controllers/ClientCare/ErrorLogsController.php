<?php

namespace App\Http\Controllers\ClientCare;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ClientCare\ClientErrorLogs;

class ErrorLogsController extends Controller
{
    //

    public function UpdateErrorLog(Request $request){

        ClientErrorLogs::where('id', $request->id)->update([
        'fullname' => $request->principalFullName,
        'deps_fullname' => $request->dependentFullName,
        'email' => $request->email,
        'company' => $request->company,
        'mobile' => $request->mobile,
        'is_allow_to_call' => 1,
        ]);

        return response()->noContent();
    }
}
