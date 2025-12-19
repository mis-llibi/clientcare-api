<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;



class SendingEmailForLoa
{
  public function __construct(
    public $email = null,
    public $body = null,
    public $subject = 'CLIENT CARE PORTAL - NOTIFICATION',
    public $key = 'default',
    public $attachments = [],
    public $cc = [],
    public $bcc = []
  ) {
  }

  private function defaultSendMail()
  {
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

    if (!empty($this->attachments)) {
        foreach ($this->attachments as $file) {

            // Case 1: attachment passed as array (binary)
            if (is_array($file) && isset($file['contents'])) {
                $part = [
                    'name' => 'attachment',
                    'contents' => $file['contents'],
                    'filename' => $file['filename'] ?? 'attachment.pdf',
                ];

                if (!empty($file['mime'])) {
                    $part['headers'] = [
                    'Content-Type' => $file['mime']
                    ];
                }

                $post_data['multipart'][] = $part;
                continue;
            }

            // Case 2: attachment passed as local path (string)
            if (is_string($file) && file_exists($file)) {
                $post_data['multipart'][] = [
                    'name' => 'attachment',
                    'contents' => fopen($file, 'r')
                ];
            continue;
            }

            // Optional: log unexpected attachment type
            Log::warning('Invalid attachment provided', [
            'attachment' => is_string($file) ? $file : gettype($file)
            ]);
        }
    }
      if (!empty($this->cc)) {
        foreach ($this->cc as $key => $cc) {
          array_push($post_data['multipart'], [
            'name' => 'cc',
            'contents' => $cc
          ]);
        }
      }
      if (!empty($this->bcc)) {
        foreach ($this->bcc as $key => $bcc) {
          array_push($post_data['multipart'], [
            'name' => 'bcc',
            'contents' => $bcc
          ]);
        }
      }
      $client = new Client();
      $response = $client->post(env('INFOBIP_API_URL') . '/email/3/send', $post_data);
      $body = $response->getBody();

      Log::info('EMAIL SENT');
      return true;
      // return json_decode($body);
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
