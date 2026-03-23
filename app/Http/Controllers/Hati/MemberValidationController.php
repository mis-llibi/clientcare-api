<?php

namespace App\Http\Controllers\Hati;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ClientCare\Masterlist;
use App\Models\ClientCare\RemainingTbl;

use function Symfony\Component\Clock\now;

class MemberValidationController extends Controller
{
    //

    public function validateMember($member_id, $birth_date){

        $member_id = strtoupper($member_id);
        $birth_date = $birth_date;
        $now = now()->format('Y-m-d');

        $result = Masterlist::where('member_id', $member_id)
            ->where('birth_date', $birth_date)
            ->first();

        // Member does not exist
        if (!$result) {
            return response()->json([
                'result' => 0
            ], 404);
        }

        $remaining = RemainingTbl::where('uniquecode', $member_id)->first();

        // Member exists but no remaining record or no limit
        if (!$remaining || $remaining->allow <= 0) {
            return response()->json([
                'result' => 2
            ], 200);
        }

        // Member exists and has limit but expired
        if ($remaining->allow > 0 && $result->incepto < $now) {
            return response()->json([
                'result' => 3
            ], 200);
        }

        // Member exists and has limit and is valid
        return response()->json([
            'result' => 1
        ], 200);


    }
}
