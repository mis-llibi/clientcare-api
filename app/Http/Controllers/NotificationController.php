<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\SendingEmailForLoa;


class NotificationController extends Controller
{
    //

  public function EncryptedPDFMailNotification($name, $email, $message)
  {

    $body = $message['body'];
    $subject = isset($message['subject']) ? $message['subject'] : 'CLIENT CARE PORTAL - NOTIFICATION';
    $cc = [];
    $bcc = [];
    $attachment = [];

    if (isset($message['cc'])) {
      if (is_array($message['cc'])) {
        foreach ($message['cc'] as $key => $row) {
          array_push($cc, $row);
        }
      } else {
        array_push($cc, $message['cc']);
      }
    }

    if (isset($message['bcc'])) {
      if (is_array($message['bcc'])) {
        foreach ($message['bcc'] as $key => $row) {
          array_push($bcc, $row);
        }
      } else {
        array_push($bcc, $message['bcc']);
      }
    }

    if (isset($message['attachment'])) {
      foreach ($message['attachment'] as $file) {
        array_push($attachment, $file);
      }
    }

    try {
      $emailer = new SendingEmailForLoa(
        email: $email,
        body: $body,
        subject: $subject,
        attachments: $attachment,
        cc: $cc,
        bcc: $bcc
      );
      $emailer->send();
      return true;
    } catch (\Throwable $th) {
      //throw $th;
      Log::error($th);
      return false;
    }
  }
}
