<?php

namespace App\Http\Controllers\Loa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Models\ClientCare\LoaInTransit;
use App\Models\ClientCare\CompanyV2;
use App\Models\ClientCare\Hospital;

class GenerateLoaController extends Controller
{
    //

    public function LOAGenerate(
                                $corporate_compcode,
                                $company_id,
                                $employee_name,
                                $patient_name,
                                $patient_type,
                                $company_name,
                                $hospital_name,
                                $doctor_name,
                                $complaints,
                                ){



        $folderCompcode = $corporate_compcode == "LLIBI" ? "ADMUM" : $corporate_compcode;

        // Assign Document Number
        $document_number = $corporate_compcode . '-' . date("Y", time()) . '-a-';

        // Find latest document number
        $result = LoaInTransit::where('company_id', $company_id)
                            ->where('document_number', 'like', "%$document_number%")
                            ->orderBy('id', 'desc')
                            ->first();


        if(!empty($result)){
            $arrDoc = explode('-', $result->document_number);
            $num = $arrDoc[count($arrDoc) - 1];
            $removeAsterisk = explode('*', $num);

            $newNum = $removeAsterisk[0] + 1;
            $newNum = str_pad($newNum, 5, "0", STR_PAD_LEFT) . '*';

            $document_number = $document_number . $newNum;
        }else{
            $document_number = $document_number . '00001*';
        }

        $logoPath = public_path('llibi.png');
        $signaturePath = public_path('signature.png');
        $confidentialPath = public_path('confidential.jpg');

        $pdf = Pdf::loadView("pdf.$folderCompcode.outpatient", [
            'document_number' => $document_number,
            'document_datetime' => now()->format('M d, Y h:i A'),
            'doctor_name' => $doctor_name,
            'hospital_name' => $hospital_name,
            'employee_name' => $patient_type == "employee" ? strtoupper($patient_name) : strtoupper($employee_name),
            'company_name' => $company_name,
            'patient_name' => $patient_name,
            'logo' => $logoPath,
            'confidential' => $confidentialPath,
            'signature' => $signaturePath,
            'complaints' => $complaints
        ]);

        $fileName = $document_number;
        $directory = 'loa/generated';
        $path = $directory . '/' . $fileName;



        $attachment = [[
            'contents' => $pdf->output(),
            'filename' => $fileName,
            'mime' => 'application/pdf',
        ]];

        $uploadPdfStatus = Storage::disk('llibiapp')->put($path, $pdf->output(), [
            'visibility' => 'public', // or 'public'
            'ContentType' => 'application/pdf',
        ]);

        if($uploadPdfStatus){
            LoaInTransit::create([
                'loa_files_id' => strtotime("now"),
                'type' => "Consultation",
                'document_number' => $document_number,
                'company_id' => $company_id,
                'employee_name' => $patient_type == "employee" ? strtoupper($patient_name) : strtoupper($employee_name),
                'patient_name' => $patient_name,
                'hospital_name' => $hospital_name,
                'date' => date('Y-m-d'),
                'time' => date("H:i:s"),
                'status' => 1
            ]);
        }else{
            return response()->json([
                'message' => "Error uploading"
            ], 404);
        }

        return [
            'document_number' => $document_number,
            'attachment' => $attachment,
            'path' => $path, // optional: useful too
        ];

    }
}
