<?php

namespace App\Libraries\Packages;

use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception;


class Mailer
{
    private $mailer;

    public function __construct($enableExceptiond = false)
    {
        $this->mailer = new PHPMailer($enableExceptiond);
        $this->mailer->isSMTP();                                            //Send using SMTP
        $this->mailer->Host       = EMAIL_HOST;                     //Set the SMTP server to send through
        $this->mailer->SMTPAuth   = true;                                   //Enable SMTP authentication
        $this->mailer->Username   = EMAIL_USER;                     //SMTP username
        $this->mailer->Password   = EMAIL_PASS;                               //SMTP password
        $this->mailer->SMTPSecure = '';            //Enable implicit TLS encryption
        $this->mailer->Port       = 587;

        $this->mailer->addReplyTo(EMAIL_USER, 'TestMail');
    }

    public function addAddress(string $email, string $name = '')
    {
        $this->mailer->addAddress($email, $name);
    }

    public function setReplyTo(string $email, string $name = '')
    {
        $this->mailer->addReplyTo($email, $name);
    }

    public function addCC(string $email, string $name = '')
    {
        $this->mailer->addCC($email, $name);
    }

    public function addBCC(string $email, string $name = '')
    {
        $this->mailer->addBCC($email, $name);
    }

    public function setSubject(string $subject)
    {
        $this->mailer->Subject = $subject;
    }

    public function setBody(string $body, bool $setAlt = true)
    {
        $this->mailer->Body = $body;

        if ($setAlt) {
            $this->setAltBody(strip_tags($body));
        }
    }

    public function setAltBody($body)
    {
        $this->mailer->AltBody = $body;
    }

    public function addAttachment($attachemnt, $name = '')
    {
        $this->mailer->addAttachment($attachemnt, $name);
    }

    public function addEmbeddedImage($path, $cid, $name, $encoding = PHPMailer::ENCODING_BASE64, $type='', $disposition = 'inline')
    {
        $this->mailer->AddEmbeddedImage($path, $cid, $name, $encoding, $type, $disposition);
    }

    public function send()
    {
        return $this->mailer->send();
    }
}
