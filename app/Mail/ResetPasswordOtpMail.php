<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     *
     * @param  mixed  $otp
     * @return void
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Use the 'emails.reset_password_otp' view for the email content
        // and pass the OTP to the view
        return $this->view('emails.reset_password_otp')
                    ->with(['otp' => $this->otp])
                    ->subject('Reset Password OTP');
    }
}
