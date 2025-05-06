<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $verificationUrl;

    public function __construct($token, $verificationUrl)
    {
        $this->token = $token;
        $this->verificationUrl = $verificationUrl;
    }

    public function build(): PasswordResetMail
    {
        return $this->view('emails.password-reset')
            ->subject('Şifrə sıfırlamaq')
            ->with([
                'resetLink' => $this->verificationUrl . '/auth/reset-password/'.$this->token
            ]);
    }
}
