<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CsvUploader;
use Illuminate\Support\Facades\DB;

class CsvUploaderController extends Controller
{
    //

    public function import(Request $request){

        $request->validate([
        'file' => 'required|file|mimes:csv,txt',
        ]);


        $file = $request->file('file');
        $path = $file->getRealPath();


        // Open stream to file
        if (($handle = fopen($path, 'r')) === false) {
        return response()->json(['message' => 'Unable to open file'], 500);
        }


        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
        fclose($handle);
        return response()->json(['message' => 'Empty CSV or invalid header'], 422);
        }


        // Normalize headers (trim/ lowercase)
        $header = array_map(function($h){ return strtolower(trim($h)); }, $header);


        $batch = [];
        $batchSize = 1000; // tune for memory vs speed
        $inserted = 0;


        DB::beginTransaction();

        try {

            while(($row = fgetcsv($handle)) !== false){
                $row = array_pad($row, count($header), null);
                $data = array_combine($header, $row);
                $batch[] = [
                    'compcode' => $data['compcode'] ?? null,
                    'inscode' => $data['inscode'] ?? null,
                    'loanumb' => $data['loanumb'] ?? null,
                    'claimtype'=> $data['claimtype'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (count($batch) >= $batchSize) {
                    CsvUploader::insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (count($batch) > 0) {
                CsvUploader::insert($batch);
                $inserted += count($batch);
            }
            DB::commit();
            fclose($handle);


            return response()->json(['message' => 'Imported', 'inserted' => $inserted]);
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) fclose($handle);
            return response()->json(['message' => 'Import failed', 'error' => $e->getMessage()], 500);
        }


    }

}
