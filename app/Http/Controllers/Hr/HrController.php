<?php

namespace App\Http\Controllers\Hr;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Loa\GenerateLoaController;

use Illuminate\Http\Request;

use App\Models\ClientCare\Client;
use App\Models\ClientCare\ClientRequest;
use App\Models\HrUsers;
use App\Models\ClientCare\Complaint;
use App\Models\ClientCare\Attachment;
use App\Models\ClientCare\Hospital;
use App\Models\ClientCare\Masterlist;
use App\Models\ClientCare\ProviderPortal;
use App\Models\ClientCare\RemainingTbl;
use App\Models\ClientCare\RemainingTblLogs;
use App\Models\ClientCare\AppLoaMonitor;
use App\Models\ClientCare\CompanyV2;
use App\Models\ClientCare\LoaInTransit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SelfService\HrExport;

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

        $email = $request->email;
        $alt_email = $request->alt_email;

        // $company = CompanyV2::where('id', $company_id)->first();



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
            'platform' => "hr",
            'email' => $email,
            'alt_email' => $alt_email

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
        ];

        if($email){

            $patient_name = $patient_firstname . " " . $patient_lastname;
            $body = array(
                'body' => view('send-hr-approve'),
            );

            (new NotificationController)->EncryptedPDFMailNotification($patient_name, $email, $body);
        }

        if($alt_email){
            $patient_name = $patient_firstname . " " . $patient_lastname;
            $body = array(
                'body' => view('send-hr-approve'),
            );

            (new NotificationController)->EncryptedPDFMailNotification($patient_name, $alt_email, $body);
        }



        $clientRequest = ClientRequest::create($clientRequestData);

        if($clientRequest){
            return response()->json(status:200);
        }

        return response()->json(status:400);

    }
    public function SearchRequest($search, $id)
    {
        $defaultStatus = [12];
        $start = Carbon::yesterday()->startOfDay();
        $end   = now()->endOfDay();

        $q = DB::connection('portal_request_db')->table('app_portal_clients as t1')
            ->leftJoin('app_portal_requests as t2', 't2.client_id', '=', 't1.id')
            ->leftJoin('app_portal_callback as t3', 't3.client_id', '=', 't1.id')
            ->leftJoin(DB::raw(DB::connection('sync_db')->getDatabaseName() . '.masterlist as mlist'), function ($join) {
                $join->on('mlist.member_id', '=', DB::raw("
                    CASE
                        WHEN t1.is_dependent = 1 THEN t1.dependent_member_id
                        ELSE t1.member_id
                    END
                "));
            })
            ->select(
                't1.id',
                't1.reference_number as refno',
                't1.email as email',
                't1.alt_email as altEmail',
                't1.contact as contact',
                't1.member_id as memberID',
                't1.first_name as firstName',
                't1.last_name as lastName',
                't1.dob as dob',
                't1.is_dependent as isDependent',
                't1.dependent_member_id as depMemberID',
                't1.dependent_first_name as depFirstName',
                't1.dependent_last_name as depLastName',
                't1.dependent_dob as depDob',
                't1.remarks as remarks',
                't1.provider_remarks as provider_remarks',
                't1.status as status',
                't1.opt_landline as opt_landline',
                't1.callback_remarks as callback_remarks',
                't1.landline as landline',
                't1.opt_contact as opt_contact',
                't1.remaining as remaining',
                't1.is_complaint_has_approved as is_complaint_has_approved',
                't1.follow_up_request_quantity as follow_up_request_quantity',
                't2.loa_type as loaType',
                't2.loa_number as loaNumber',
                't2.approval_code as approvalCode',
                't2.loa_attachment as loaAttachment',
                't2.complaint as complaint',
                't2.lab_attachment as labAttachment',
                't2.assessment_q1 as ass1',
                't2.assessment_q2 as ass2',
                't2.assessment_q3 as ass3',
                't1.created_at as createdAt',
                't2.provider_id as providerID',
                't2.provider as providerName',
                't2.doctor_id as doctorID',
                't2.doctor_name as doctorName',
                't2.diagnosis as diagnosis',
                't2.provider_procedure_type as procedure_type',
                't2.is_excluded as is_excluded',
                't1.approved_date',
                DB::raw('TIMESTAMPDIFF(MINUTE, t1.created_at, t1.approved_date) as elapse_minutes'),
                DB::raw('TIMESTAMPDIFF(HOUR, t1.created_at, t1.approved_date) as elapse_hours'),
                'mlist.company_name',
                'mlist.company_code',
                'mlist.empcode as inscode',
                't1.provider_email2',
                't1.is_send_to_provider',
                't1.platform',
                't3.failed_count',
                't3.first_attempt_date',
                't3.second_attempt_date',
                't3.third_attempt_date',
                't3.created_at as callback_created_at',
                't3.updated_at as callback_updated_at',
                't2.type_approval_code',
                't2.approval_code_loanumber',
            );

        if ($id == 12) {
            $q->whereBetween('t1.created_at', [$start, $end]);
        }

        // status filter
        $q->where(function ($query) use ($id, $defaultStatus) {
            if ($id == 12) {
                $query->whereIn('t1.status', $defaultStatus);
            } elseif (in_array($id, ['qr', 'viber', 'provider'])) {
                $query->where('t1.platform', $id)
                      ->where('t1.status', '!=', 1);
            } elseif (is_array($id)) {
                $query->where('t1.id', $id['val']);
            } else {
                $query->where('t1.status', $id);
            }
        });

        // search filter (only if provided)
        if ($search != 0 && $search !== null && $search !== '') {
            $term = trim($search);

            $q->where(function ($query) use ($term) {
                $like = "%{$term}%";
                $query->where('t1.member_id', 'like', $like)
                    ->orWhere('t1.first_name', 'like', $like)
                    ->orWhere('t1.last_name', 'like', $like)
                    ->orWhere('t1.dependent_member_id', 'like', $like)
                    ->orWhere('t1.dependent_first_name', 'like', $like)
                    ->orWhere('t1.dependent_last_name', 'like', $like);
            });
        }

        $sortDirection = ($id == 12) ? 'asc' : 'desc';
        $patients = $q->orderBy('t1.id', $sortDirection)->paginate(10);

        if ($patients->isEmpty()) return $patients;

        // -------- Batch enrichment (no N+1) --------
        $compcodes = $patients->pluck('company_code')->filter()->unique()->values();
        $inscodes  = $patients->pluck('inscode')->filter()->map(fn($v) => (int)$v)->unique()->values();

        $companies = CompanyV2::whereIn('corporate_compcode', $compcodes)
            ->get()
            ->keyBy('corporate_compcode');

        // claims count grouped
        $claimsCount = AppLoaMonitor::selectRaw('compcode, inscode, COUNT(*) as cnt')
            ->whereIn('compcode', $compcodes)
            ->whereIn('inscode', $inscodes)
            ->groupBy('compcode', 'inscode')
            ->get()
            ->mapWithKeys(fn($r) => ["{$r->compcode}|{$r->inscode}" => (int)$r->cnt]);

        $status = [1, 4];
        $types  = ['outpatient', 'laboratory', 'consultation'];

        $patients->getCollection()->transform(function ($p) use ($companies, $claimsCount, $status, $types) {
            $fullname = $p->isDependent
                ? "{$p->depLastName}, {$p->depFirstName}"
                : "{$p->lastName}, {$p->firstName}";

            $compcode = $p->company_code;
            $inscode  = (int)$p->inscode;

            $company = $companies->get($compcode);
            $policy  = $company->policy ?? "2024-11-1";

            // LOA transit count (COUNT only)
            $loaTransitCount = LoaInTransit::where('patient_name', 'like', "%{$fullname}%")
                ->whereIn('status', $status)
                ->where(function ($q) use ($types) {
                    foreach ($types as $type) $q->orWhere('type', 'like', "%{$type}%");
                })
                ->where('date', '>=', $policy)
                ->count();

            $claims = $claimsCount->get("{$compcode}|{$inscode}", 0);

            if ($claims > $loaTransitCount) {
                $p->total_remaining = 0;
            } else {
                $totalLoaTransitClaims = $loaTransitCount - $claims;
                $p->total_remaining = $p->remaining - $totalLoaTransitClaims;
            }

            $p->benefit_type = $company->benefit_type ?? null;
            return $p;
        });

        return $patients;
    }

    public function UpdateRequestApproval(Request $request)
    {
        set_time_limit(600);

        $user_id = request()->user()->id;
        $status = (int)$request->status;
        $update = [];

        if ($status === 14) {
            // HR Disapproved — status 14
            Client::where('id', $request->id)->update([
                'status' => 14,
                'remarks' => $request->disapproveRemarks ? strtoupper($request->disapproveRemarks) : null,
            ]);
            ClientRequest::where('client_id', $request->id)->update([
                'loa_status' => "Denied"
            ]);
        }

        if ($status === 13) {
            // HR Approved — check isAuto to determine status
            $clientRecord = Client::where('id', $request->id)->first();
            $clientRequestRecord = ClientRequest::where('client_id', $request->id)->first();

            $memberId = $clientRecord->is_dependent == 1
                ? $clientRecord->dependent_member_id
                : $clientRecord->member_id;

            $findPatient = Masterlist::where('member_id', $memberId)->first();

            $costcode_companies = ['ARTSA', 'ARTHA', 'AFRYP'];
            if (in_array($findPatient->company_code, $costcode_companies)) {
                $company = CompanyV2::where('prefix_compcode', $findPatient->cost_code)->first();
            } else {
                $company = CompanyV2::where('prefix_compcode', $findPatient->company_code)->first();
            }

            if ($clientRequestRecord->client_id == $request->id && $clientRequestRecord->is_auto == 1) {
                // Auto-generate LOA — status 11
                $hospital_name = explode('++', $clientRequestRecord->provider)[0];
                $doctname = explode('++', $clientRequestRecord->doctor_name ?? '')[0];
                $doctname = $doctname == ", " ? "" : $doctname;

                $employee_name = $clientRecord->last_name . ", " . $clientRecord->first_name;
                $patient_name = $clientRecord->is_dependent == 1
                    ? $clientRecord->dependent_last_name . ", " . $clientRecord->dependent_first_name
                    : $employee_name;
                $patientType = $clientRecord->is_dependent == 1 ? 'dependent' : 'employee';

                $generateLoa = new GenerateLoaController();
                $result = $generateLoa->LOAGenerate(
                    $company->corporate_compcode,
                    $company->company_id_from_corporate,
                    $employee_name,
                    $patient_name,
                    $patientType,
                    $findPatient->company_name,
                    $hospital_name,
                    $doctname,
                    $clientRequestRecord->complaint,
                );

                $loa_number = $result['document_number'];
                $attachment = $result['attachment'];

                Client::where('id', $request->id)->update([
                    'status' => 11,
                    'approved_date' => Carbon::now(),
                ]);

                $update = [
                    'loa_status' => 'Approved',
                    'loa_number' => $loa_number,
                    'loa_attachment' => env('DO_LLIBI_CDN_ENDPOINT') . '/loa/generated/' . $loa_number,
                    'approval_code' => 'HR-APPROVED',
                ];
                ClientRequest::where('client_id', $request->id)->update($update);

                // Send LOA notification to patient
                $email = $clientRecord->alt_email ?? $clientRecord->email;
                $accept_eloa = Hospital::where('id', $clientRequestRecord->provider_id)->value('accept_eloa');

                if ($accept_eloa) {
                    $statusRemarks = 'Your LOA request has been approved. Your LOA Number is ' . '<b>' . $loa_number . '</b>' . '. ' . '<br /><br />' . 'You may print a copy of your LOA and present it to the accredited provider upon availment or you may present your (1) ER card or (2) LOA number together with any valid government ID as this provider now accepts e-LOA';
                } else {
                    $statusRemarks = 'Your LOA request has been approved. Your LOA Number is ' . '<b>' . $loa_number . '</b>' . '. ' . '<br /><br />' . 'Please print a copy of your LOA and present it to the accredited provider upon availment.';
                }

                $homepage = "https://admin.portal.llibi.app";
                $feedbackUrl = $homepage . '/feedback/?q=' . Str::random(64)
                    . '&rid=' . $clientRecord->id
                    . '&compcode=' . $findPatient->company_code
                    . '&memid=' . $memberId
                    . '&reqstat=' . 11;

                $feedbackLink = '
                <div>
                    We value your feedback: <a href="' . $feedbackUrl . '">Please click here</a>
                </div>
                <div>
                    <a href="' . $feedbackUrl . '">
                    <img src="https://llibi-storage.sgp1.cdn.digitaloceanspaces.com/Self-service/Images/ccportal_1.jpg" alt="Feedback Icon" width="300">
                    </a>
                </div>
                <br /><br />';

                $body = array(
                    'body' => view('send-request-loa', [
                        'name' => $clientRecord->is_dependent == 1
                            ? strtoupper($employee_name)
                            : strtoupper($patient_name),
                        'dependent' => $clientRecord->is_dependent == 1 ? $patient_name : null,
                        'statusRemarks' => $statusRemarks,
                        'is_accept_eloa' => $accept_eloa,
                        'ref' => $clientRecord->reference_number,
                        'feedbackLink' => $feedbackLink,
                    ]),
                    'attachment' => $attachment
                );

                (new NotificationController)->EncryptedPDFMailNotification($patient_name, $clientRecord->email, $body);

                if ($clientRecord->alt_email) {
                    (new NotificationController)->EncryptedPDFMailNotification($patient_name, $clientRecord->alt_email, $body);
                }

                if ($clientRecord->contact) {
                    $patientSmsName = $clientRecord->is_dependent == 1
                        ? $clientRecord->dependent_first_name . ' ' . $clientRecord->dependent_last_name
                        : $clientRecord->first_name . ' ' . $clientRecord->last_name;
                    $sms = "From Lacson & Lacson:\n\nHi $patientSmsName,\n\nYour LOA request has been approved. Your LOA Number is $loa_number.\n\nYour reference number is $clientRecord->reference_number";
                    $this->SendSMS($clientRecord->contact, $sms);
                }

                $client = $this->SearchRequest(0, ['val' => $request->id]);
                $allClient = $this->SearchRequest(0, 12);
                return array('client' => $client, 'all' => $allClient, 'isAuto' => true);

            } else {
                // isAuto = 0 — status 13 (pending client care manual handling)
                Client::where('id', $request->id)->update([
                    'status' => 13,
                    // 'user_id' => $user_id,
                ]);

                $update = [
                    'approval_code' => 'HR-APPROVED',
                    'loa_status' => 'Pending Approval',
                ];
                ClientRequest::where('client_id', $request->id)->update($update);
            }
        }

        $client = $this->SearchRequest(0, ['val' => $request->id]);
        $allClient = $this->SearchRequest(0, 12);

        return array('client' => $client, 'all' => $allClient);
    }

    private function encryptPdf($path, $password)
    {
        $filePath = Storage::path('public/' . $path);
        return $filePath;
    }

    function removePastValue($in, $before)
    {
        $pos = strpos($in, $before);
        return $pos !== FALSE
            ? substr($in, $pos + strlen($before), strlen($in))
            : "";
    }

    function clean($string)
    {
        $string = str_replace(' ', '-', $string);
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }

    private function sendNotificationProvider($data, $name, $email, $altEmail, $contact, $dependent, $providerID)
    {
        $hospital = Hospital::where('id', $providerID)->first();
        $accept_eloa = $hospital->accept_eloa;

        $provider_portal = ProviderPortal::where('provider_id', $providerID)
            ->where('user_type', 'Hospital')
            ->first();

        $name = ucwords(strtolower($name));
        $dependent = $dependent === null ? null : ucwords(strtolower($dependent));
        $remarks = $data['remarks'];
        $ref = $data['refno'];
        $provider_email2 = 'testllibi1@yopmail.com';
        $is_send_to_provider = $data['is_send_to_provider'];
        $company_code = $data['company_code'];
        $member_id = $data['member_id'];
        $request_id = $data['request_id'];

        $loanumber = (!empty($data['loa_number']) ? $data['loa_number'] : '');
        $approvalcode = (!empty($data['approval_code']) ? $data['approval_code'] : '');

        if (!empty($email)) {
            $attachment = [];
            if ($data['status'] == 3) {
                $attach = $data['encryptedLOA'];
                $attachment = [$attach];
            }

            $homepage = env('FRONTEND_URL');
            $feedbackLink = '
                <div>
                  We value your feedback: <a href="' . $homepage . '/feedback/?q=' . Str::random(64) . '&rid=' . $request_id . '&compcode=' . $company_code . '&memid=' . $member_id . '&reqstat=' . $data['status'] . '">
                    Please click here
                  </a>
                </div>
                <div>
                  <a href="' . $homepage . '/feedback/?q=' . Str::random(64) . '&rid=' . $request_id . '&compcode=' . $company_code . '&memid=' . $member_id . '&reqstat=' . $data['status'] . '">
                  <img src="' . env('APP_URL', 'https://portal.llibi.app') . '/storage/ccportal_1.jpg" alt="Feedback Icon" width="300">
                  </a>
                </div>
              <br /><br />';

            if ($data['status'] === 3) {
                $statusRemarksProvider = 'LOA Request for ' . '<b>' . $name . '</b>' . ' is <b>approved</b>. You may print LOA and issue to the patient.';

                if ($accept_eloa) {
                    $statusRemarks = 'Your LOA request has been approved. You may now notify the ' . '<b>' . $hospital->name . '</b>' . ' that it was already sent to their email. Alternatively, we also sent you a copy and you may forward it to the clinic/hospital email. ' . '<br /><br />' . 'You may print a copy of your LOA and present it to the accredited provider upon availment or you may present your (1) ER card or (2) LOA number together with any valid government ID as this provider now accepts e-LOA';
                } else {
                    $statusRemarks = 'Your LOA request has been approved. You may now notify the ' . '<b>' . $hospital->name . '</b>' . ' that it was already sent to their email. Alternatively, we also sent you a copy and you may forward it to the clinic/hospital email.';
                }
            } else {
                $statusRemarks = 'Your LOA request is <b>disapproved</b> with remarks: ' . $remarks;
                $feedbackLink = '';
            }

            $bodyProvider = array(
                'body' => view('send-request-loa', [
                    'name' => $hospital->name,
                    'dependent' => null,
                    'statusRemarks' => $statusRemarksProvider ?? $statusRemarks,
                    'is_accept_eloa' => $accept_eloa,
                    'ref' => $ref,
                    'feedbackLink' => $feedbackLink,
                ]),
                'attachment' => $attachment
            );

            $bodyPatient = array(
                'body' => view('send-request-loa', [
                    'name' => $name,
                    'dependent' => $dependent,
                    'statusRemarks' => $statusRemarks,
                    'is_accept_eloa' => $accept_eloa,
                    'ref' => $ref,
                    'feedbackLink' => $feedbackLink,
                ]),
                'attachment' => $attachment
            );

            $mail = (new NotificationController)->EncryptedPDFMailNotification($name, $email, $bodyProvider);
            if (!empty($altEmail)) {
                $altMail = (new NotificationController)->EncryptedPDFMailNotification($name, $altEmail, $bodyPatient);
            }
        }

        if (!empty($contact)) {
            if ($data['status'] === 3) {
                if (isset($provider_portal->notification_sms) || $provider_portal->notification_sms != 'undefined') {
                    $smsProvider = 'Hi ' . $hospital->name . '\n\n' . 'LOA request for ' . ($dependent == null ? $name : $dependent) . ' is approved. You may now print LOA and issue to the patient. \n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.';
                    $this->SendSMS($provider_portal->notification_sms, $smsProvider);
                }

                if ($accept_eloa) {
                    $sms = 'From Lacson & Lacson:\n\nHi ' . $name . '' . ($dependent !== null ? " and $dependent" : "") . ',\n\nYour LOA request is approved. You may now notify the ' . $hospital->name . ' that it was already sent to their email. Alternatively, we also sent you a copy and you may forward it to the clinic/hospital email.' . '\n\n' . 'You may print a copy of your LOA and present it to the accredited provider upon availment or you may present your (1) ER card or (2) LOA number together with any valid government ID as this provider now accepts e-LOA.\n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.\n\nYour reference number: ' . $ref . '\n\nThis is an auto-generated SMS. Doesn\'t support replies and calls.';
                } else {
                    $sms = 'From Lacson & Lacson:\n\nHi ' . $name . '' . ($dependent !== null ? " and $dependent" : "") . ',\n\nYour LOA request is approved. You may now notify the ' . $hospital->name . ' that it was already sent to their email. Alternatively, we also sent you a copy and you may forward it to the clinic/hospital email.' . '\n\n' . 'Please print a copy LOA and present to the accredited provider upon availment.\n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.\n\nYour reference number: ' . $ref . '\n\nThis is an auto-generated SMS. Doesn\'t support replies and calls.';
                }
            } else {
                $sms = "From Lacson & Lacson:\n\nHi $name" . ($dependent !== null ? " and $dependent" : "") . ",\n\nYour LOA request is disapproved with remarks: $remarks\n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.\n\nYour reference number is $ref\n\nThis is an auto-generated SMS. Doesn\'t support replies and calls.";
            }
            $this->SendSMS($contact, $sms);
        }
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

    private function sendNotification($data, $name, $email, $altEmail, $contact, $dependent, $providerID)
    {
        $hospital = Hospital::where('id', $providerID)->first();
        $accept_eloa = $hospital->accept_eloa;

        $name = ucwords(strtolower($name));
        $dependent = $dependent === null ? null : ucwords(strtolower($dependent));
        $remarks = $data['remarks'];
        $ref = $data['refno'];
        $provider_email2 = 'testllibi1@yopmail.com';
        $is_send_to_provider = $data['is_send_to_provider'];
        $company_code = $data['company_code'];
        $member_id = $data['member_id'];
        $request_id = $data['request_id'];

        $loanumber = (!empty($data['loa_number']) ? $data['loa_number'] : '');
        $approvalcode = (!empty($data['approval_code']) ? $data['approval_code'] : '');

        if (!empty($email)) {
            $attachment = [];
            if ($data['status'] == 3) {
                $attach = $data['encryptedLOA'];
                $attachment = [$attach];
            }

            $homepage = env('FRONTEND_URL');
            $feedbackLink = '
                <div>
                  We value your feedback: <a href="' . $homepage . '/feedback/?q=' . Str::random(64) . '&rid=' . $request_id . '&compcode=' . $company_code . '&memid=' . $member_id . '&reqstat=' . $data['status'] . '">
                    Please click here
                  </a>
                </div>
                <div>
                  <a href="' . $homepage . '/feedback/?q=' . Str::random(64) . '&rid=' . $request_id . '&compcode=' . $company_code . '&memid=' . $member_id . '&reqstat=' . $data['status'] . '">
                  <img src="' . env('APP_URL', 'https://portal.llibi.app') . '/storage/ccportal_1.jpg" alt="Feedback Icon" width="300">
                  </a>
                </div>
              <br /><br />';

            if ($data['status'] === 3) {
                if ($accept_eloa) {
                    $statusRemarks = 'Your LOA request has been approved. Your LOA Number is ' . '<b>' . $data['loa_number'] . '</b>' . '. ' . '<br /><br />' . 'You may print a copy of your LOA and present it to the accredited provider upon availment or you may present your (1) ER card or (2) LOA number together with any valid government ID as this provider now accepts e-LOA';
                } else {
                    $statusRemarks = 'Your LOA request has been approved. Your LOA Number is ' . '<b>' . $data['loa_number'] . '</b>' . '. ' . '<br /><br />' . 'Please print a copy of your LOA and present it to the accredited provider upon availment.';
                }
            } else {
                $statusRemarks = 'Your LOA request is <b>disapproved</b> with remarks: ' . $remarks;
                $feedbackLink = '';
            }

            $body = array(
                'body' => view('send-request-loa', [
                    'name' => $name,
                    'dependent' => $dependent,
                    'statusRemarks' => $statusRemarks,
                    'is_accept_eloa' => $accept_eloa,
                    'ref' => $ref,
                    'feedbackLink' => $feedbackLink,
                ]),
                'attachment' => $attachment
            );

            $mail = (new NotificationController)->EncryptedPDFMailNotification($name, $email, $body);
            if (!empty($altEmail)) {
                $altMail = (new NotificationController)->EncryptedPDFMailNotification($name, $altEmail, $body);
            }
        }

        if (!empty($contact)) {
            if ($data['status'] === 3) {
                if ($accept_eloa) {
                    $sms = 'From Lacson & Lacson:\n\nHi ' . $name . '' . ($dependent !== null ? " and $dependent" : "") . ',\n\nYour LOA request has been approved. Your LOA Number is ' . $data['loa_number'] . '. \n\n' . 'You may print a copy of your LOA and present it to the accredited provider upon availment or you may present your (1) ER card or (2) LOA number together with any valid government ID as this provider now accepts e-LOA.\n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.\n\nYour reference number: ' . $ref . '\n\nThis is an auto-generated SMS. Doesn\'t support replies and calls.';
                } else {
                    $sms = 'From Lacson & Lacson:\n\nHi ' . $name . '' . ($dependent !== null ? " and $dependent" : "") . ',\n\nYour LOA request has been approved. Your LOA Number is ' . $data['loa_number'] . '. \n\n' . 'Please print a copy LOA and present to the accredited provider upon availment.\n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.\n\nYour reference number: ' . $ref . '\n\nThis is an auto-generated SMS. Doesn\'t support replies and calls.';
                }
            } else {
                $sms = "From Lacson & Lacson:\n\nHi $name" . ($dependent !== null ? " and $dependent" : "") . ",\n\nYour LOA request is disapproved with remarks: $remarks\n\nFor further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.\n\nYour reference number is $ref\n\nThis is an auto-generated SMS. Doesn\'t support replies and calls.";
            }
            $this->SendSMS($contact, $sms);
        }
    }

    //FILE UPLOADS
    public function getFiles($id)
    {
        $attachment = Attachment::where('request_id', $id)
            ->select('id', 'file_name', 'file_link')
            ->get();

        $client_request = Client::query()->with('clientRequest:id,client_id,loa_type')->where('id', $id)->first();

        return ['attachment' => $attachment, 'client_request' => $client_request];
    }

    public function getProcedure($id)
    {
        $client_request = Client::query()->with('clientRequest:id,client_id,loa_type')->where('id', $id)->first();

        return response()->json([
            'client_request' => $client_request
        ], 200);
    }


    public function previewExport(Request $request)
    {
        $request_status = $request->status;
        $request_search = $request->search;
        $request_from = $request->from;
        $request_to = $request->to;

        $records = $this->exportRecords($request_search, $request_status, $request_from, $request_to);

        return response()->json(['status' => true, 'message' => 'Fetching success', 'data' => $records]);
    }

    public function exportRecords($search = 0, $status = 2, $from = null, $to = null)
    {
        $portalRequestDb = DB::connection('portal_request_db')->getDatabaseName();
        $syncDb = DB::connection('sync_db')->getDatabaseName();

        $buildQuery = function ($clientTable, $requestTable) use ($search, $status, $from, $to, $portalRequestDb, $syncDb) {
            $query = DB::connection('portal_request_db')->table("{$clientTable} as t1")
                ->join("{$requestTable} as t2", 't2.client_id', '=', 't1.id')
                ->leftJoin('users as user', 'user.id', '=', 't1.user_id')
                ->leftJoin(DB::raw("{$syncDb}.masterlist as mlist"), 'mlist.member_id', '=', 't1.member_id')
                ->select(
                    't1.id',
                    't1.reference_number as refno',
                    't1.email as email',
                    't1.alt_email as altEmail',
                    't1.contact as contact',
                    't1.member_id as memberID',
                    't1.first_name as firstName',
                    't1.last_name as lastName',
                    't1.remarks as remarks',
                    't1.status as status',
                    't2.loa_type as loaType',
                    't2.loa_number as loaNumber',
                    't2.approval_code as approvalCode',
                    't2.complaint as complaint',
                    't1.created_at as createdAt',
                    't1.approved_date',
                    't1.handling_time as elapse_minutes',
                    'user.first_name as approved_by_first_name',
                    'user.last_name as approved_by_last_name',
                    'user.user_level',
                    'mlist.company_name',
                    't1.platform'
                )
                ->whereIn('t1.status', [2, 3, 4, 5])
                ->where(function ($q) use ($search, $status) {
                    if ($search != 0) {
                        $q->orWhere('t1.member_id', 'like', '%' . strtoupper($search) . '%');
                        $q->orWhere('t1.first_name', 'like', '%' . strtoupper($search) . '%');
                        $q->orWhere('t1.last_name', 'like', '%' . strtoupper($search) . '%');

                        $q->orWhere('t1.dependent_member_id', 'like', '%' . strtoupper($search) . '%');
                        $q->orWhere('t1.dependent_first_name', 'like', '%' . strtoupper($search) . '%');
                        $q->orWhere('t1.dependent_last_name', 'like', '%' . strtoupper($search) . '%');
                    }
                    if (is_array($status)) {
                        $q->where('t1.id', $status['val']);
                    } else {
                        if ($status != 0) {
                            $q->where('t1.status', $status);
                        }
                    }
                });

            if ($from && $to) {
                $query->whereDate('t1.created_at', '>=', $from)->whereDate('t1.created_at', '<=', $to);
            }

            return $query;
        };

        $request = $buildQuery('app_portal_clients', 'app_portal_requests');
        $archive = $buildQuery('app_portal_clients_archive', 'app_portal_requests_archive');

        return $request->unionAll($archive)->orderBy('id', 'DESC')->get();
    }

    public function viewBy(Request $request)
    {
        $user_id = request()->user()->id;

        $view_checker = Client::where('id', $request->id)->select('view_by')->first();

        if ($view_checker->view_by != NULL && ($view_checker->view_by != $user_id && $request->type == 'view')) {
            return response()->json(['status' => false, 'message' => 'This request is already handled by another HR.']);
        }

        if ($request->type == 'view') {
            Client::where('id', $request->id)->update(['view_by' => $user_id]);
        } else {
            if ($view_checker->view_by == $user_id) {
                Client::where('id', $request->id)->update(['view_by' => null]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Success.']);
    }

    private function emailIsValid($email)
    {
        $isValidEmail = preg_match('/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/', $email) === 1;
        return $isValidEmail;
    }

    function viewLogs(Request $request)
    {
        $syncDb = DB::connection('sync_db')->getDatabaseName();

        $result = DB::connection('portal_request_db')->table('app_portal_clients as t1')
            ->join('app_portal_requests as t2', 't2.client_id', '=', 't1.id')
            ->leftJoin(DB::raw("{$syncDb}.masterlist as mlist"), 'mlist.member_id', '=', 't1.member_id')
            ->join('users as user', 'user.id', '=', 't1.view_by')
            ->select(
                't1.id',
                't1.reference_number as refno',
                't1.email as email',
                't1.alt_email as altEmail',
                't1.contact as contact',
                't1.member_id as memberID',
                't1.first_name as firstName',
                't1.last_name as lastName',
                't1.remarks as remarks',
                't1.status as status',
                't2.loa_type as loaType',
                't2.loa_number as loaNumber',
                't2.approval_code as approvalCode',
                't2.complaint as complaint',
                't1.created_at as createdAt',
                't2.provider_id as providerID',
                't2.provider as providerName',
                't1.approved_date',
                DB::raw('TIMESTAMPDIFF(MINUTE, t1.created_at, t1.approved_date) as elapse_minutes'),
                DB::raw('TIMESTAMPDIFF(HOUR, t1.created_at, t1.approved_date) as elapse_hours'),
                'mlist.company_name',
                'mlist.company_code',
                't1.provider_email2',
                't1.is_send_to_provider',
                't1.view_by',
                'user.first_name as viewFirstname',
                'user.last_name as viewLastname',
                'user.email as viewEmail',
            )
            ->whereIn('t1.status', [2])
            ->orderBy('t1.id', 'DESC')
            ->limit(40)
            ->get();

        return $result;
    }

    public function pendingCounter()
    {
        $request = DB::connection('portal_request_db')->table('app_portal_clients as t1')
            ->join('app_portal_requests as t2', 't2.client_id', '=', 't1.id')
            ->select('t1.id')
            ->where('t1.status', 2)
            ->limit(40)
            ->count();

        return ['pending' => $request];
    }

    public function GetCompanies()
    {
        $company = CompanyV2::select('name', 'corporate_compcode as code')->get();

        return $company;
    }
}
