<?php namespace Klb\Core;
/**
 * Class Model
 * @package App
 */
use Kalbe\Model\Mail;

class MailTemplate {

    private $subject;
    private $sender_name;
    private $sender_email;
    private $body;

    public function get_template($templateId) {
        $mail = Mail::findFirst(["id = $templateId"]);
        $this->subject = $mail->subject;
        $this->sender_name = $mail->sender_name;
        $this->sender_email = $mail->sender_email;
        $this->body = $mail->body;
    }

    public function get_sender_email() {
        return $this->sender_email;
    }

    public function get_subject() {
        return $this->subject;
    }

    public function get_body() {
        return $this->body;
    }

    public function put_subject($key, $value) {
        $this->subject = str_replace('{{' . $key . '}}', $value, $this->subject);
    }

    public function put_body($key, $value) {
        $this->body = str_replace('{{' . $key . '}}', $value, $this->body);
    }

}
