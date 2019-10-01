<?php namespace Klb\Core\Mail;

use Phalcon\Mvc\User\Component;
use Swift_Message as Message;
use Swift_SmtpTransport as Smtp;
use Phalcon\Mvc\View;

/**
 * Vokuro\Mail\Mail
 * Sends e-mails based on pre-defined templates
 */
class Mail extends Component
{
    protected $transport;
    /**
     * @var
     */
    protected $amazonSes;
    /**
     * @var \stdClass
     */
    protected $amazonConfig;


    /**
     * Send a raw e-mail via AmazonSES
     * @deprecated
     * @param string $raw
     * @return bool
     */
    private function amazonSESSend($raw)
    {
        if(!class_exists('\AmazonSES')){
            throw new \BadMethodCallException('Unknown class \AmazonSES');
        }
        if ($this->amazonSes == null) {
            $this->amazonSes = new \AmazonSES(
                [
                    'key'    => $this->config->amazon->AWSAccessKeyId,
                    'secret' => $this->config->amazon->AWSSecretKey
                ]
            );
            @$this->amazonSes->disable_ssl_verification();
        }

        $response = $this->amazonSes->send_raw_email(
            [
                'Data' => base64_encode($raw)
            ],
            [
                'curlopts' => [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0
                ]
            ]
        );

        if (!$response->isOK()) {
            $this->logger->error('Error sending email from AWS SES: ' . $response->body->asXML());
        }

        return true;
    }

    /**
     * Applies a template to be used in the e-mail
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public function getTemplate($name, $params)
    {
        $parameters = array_merge([
            'publicUrl' => $this->config->application->publicUrl
        ], $params);

        return $this->view->getRender('emailTemplates', $name, $parameters, function ($view) {
            $view->setRenderLevel(View::LEVEL_LAYOUT);
        });
    }

    /**
     * Sends e-mails via AmazonSES based on predefined templates
     *
     * @param array $to
     * @param string $subject
     * @param string $name
     * @param array $params
     * @return bool|int
     * @throws \Exception
     */
    public function send($to, $subject, $name, $params)
    {
        // Settings
        $mailSettings = $this->config->mail;

        $template = $this->getTemplate($name, $params);

        // Create the message
        $message = Message::newInstance()
            ->setSubject($subject)
            ->setTo($to)
            ->setFrom([
                $mailSettings->fromEmail => $mailSettings->fromName
            ])
            ->setBody($template, 'text/html');

        if (isset($mailSettings) && isset($mailSettings->smtp)) {

            if (!$this->transport) {
                $this->transport = Smtp::newInstance(
                    $mailSettings->smtp->server,
                    $mailSettings->smtp->port,
                    $mailSettings->smtp->security
                )
                ->setUsername($mailSettings->smtp->username)
                ->setPassword($mailSettings->smtp->password);
            }

            // Create the Mailer using your created Transport
            $mailer = \Swift_Mailer::newInstance($this->transport);
            /** @var \Swift_Mime_Message $message */
            return $mailer->send($message);
        } else {
            return $this->amazonSESSend($message->toString());
        }
    }

    /**
     * @param $subject
     * @param $recipient
     * @param $emailBody
     * @param null $attachment
     * @param null $sender
     * @param null $bcc
     * @return bool
     */
    public function sesEmailSend($subject, $recipient, $emailBody, $attachment = null, $sender = null, $bcc = null) {
        $this->amazonConfig = $this->getDI()->get('config')->amazon;
        //print_r($recipient); exit();
        $transport = \Swift_AWSTransport::newInstance( $this->amazonConfig->AWSAccessKeyId, $this->amazonConfig->AWSSecretKey );
        $transport->setDebug( false ); // Print the response from AWS to the error log for debugging.

        //Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance( $transport );

        //Create the message
        try {
            /** @var \Swift_Mime_Message $message */
            $message = \Swift_Message::newInstance()
                ->setSubject( $subject )
                ->setFrom( $sender ?: $this->amazonConfig->AWSSender )
                ->setTo( $recipient )
                ->setBody( $emailBody, 'text/html' );
            if(null !== $bcc){
                $message->setBcc($bcc);
            }
            if(null !== $attachment) {
                if(!is_array($attachment) && $attachment instanceof \Swift_Mime_Attachment) {
                    $message->attach($attachment);
                } else if(is_array($attachment)){
                    foreach ( $attachment as $attach ){
                        if($attach instanceof \Swift_Mime_Attachment){
                            $message->attach($attach);
                        }
                    }
                }
            }
            return !!$mailer->send( $message );
        } catch(\Exception $ex) {
            $this->di->get('logger')->error($ex->getMessage());
        }

        return false;
    }
}
