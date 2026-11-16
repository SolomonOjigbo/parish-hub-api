<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;

class EmailService
{

       public function sendOtp($email, $otp)
    {
        Mail::send(
            'emails.otp',
            ['otp' => $otp],
            function ($message) use ($email) {

                $message->to($email)
                    ->subject('Your OTP Code');
            }
        );
    }

     public function sendWelcome($email, $name)
    {
        Mail::send(
            'emails.welcome',
            ['name' => $name],
            function ($message) use ($email) {

                $message->to($email)
                    ->subject('Welcome');
            }
        );
    }

      public function sendReset(
        $email,
        $otp,
        $resetUrl,
        $name
    ) {
        Mail::send(
            'emails.reset',
            [
                'otp' => $otp,
                'resetUrl' => $resetUrl,
                'name' => $name
            ],
            function ($message) use ($email) {

                $message->to($email)
                    ->subject('Reset Your Password');
            }
        );
    }

    

}