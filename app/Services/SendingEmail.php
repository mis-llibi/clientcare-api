<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendingEmail{
    public function __construct(
        public $email = null,
        public $body = null,
        public $subject = 'PROVIDER PORTAL - NOTIFICATION',
        public $key = 'default',
    ){
    }


    private function defaultSendMail(){
        try {
            $post_data = [
                'headers' => ['Authorization' => env('INFOBIP_API_KEY')],
                'multipart' => [
                    [
                        'name' => 'bulkId',
                        'contents' => trim(Str::slug($this->subject))
                    ],
                    [
                        'name' => 'from',
                        'contents' => env('INFOBIP_SENDER'),
                    ],
                    [
                        'name' => 'to',
                        'contents' => trim($this->email)
                    ],
                    [
                        'name' => 'subject',
                        'contents' => trim($this->subject)
                    ],
                    [
                        'name' => 'html',
                        'contents' => $this->body
                    ],
                    [
                        'name' => 'intermediateReport',
                        'contents' => 'true'
                    ],
                    [
                      'name' => 'track',
                      'contents' => false
                    ],
                    [
                      'name' => 'trackClicks',
                      'contents' => false
                    ],
                    [
                      'name' => 'trackOpens',
                      'contents' => false
                    ],
                    [
                      'name' => 'trackingUrl',
                      'contents' => false
                    ],
                ]
            ];
            $client = new Client();
            $response = $client->post(config('app.INFOBIP_API_URL') . '/email/3/send', $post_data);
            $body = $response->getBody();
            Log::info('EMAIL SENT');
            return true;

        } catch (\Throwable $th) {
            echo $th;
            Log::error($th);
            return false;
        }
    }

    public function send()
    {
      return $this->defaultSendMail();
    }
}
