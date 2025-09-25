<?php

namespace App\Mail;

use App\Helpers\Helper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $verificationUrl;

    public function __construct($token, $code, $verificationUrl = null)
    {
        $this->token = $token;
        $this->code = $code;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * @throws Exception
     */
    public function build(): PasswordResetMail
    {
        return $this->view('emails.password-reset')
            ->subject('Şifrə sıfırlamaq')
            ->with([
                'code' => $this->code,
                'resetLink' => $this->verificationUrl ? $this->verificationUrl . '/auth/reset-password/'.$this->token : null
            ]);
    }
}
