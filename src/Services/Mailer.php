<?php


namespace Klb\Core\Services;


use Klb\Core\Model\MailTemplate;
use Phalcon\Config;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use RuntimeException;
use Swift_Attachment;
use Swift_AWSTransport;
use Swift_Mailer;
use Swift_Message as Message;
use Swift_Mime_Attachment;

class Mailer
{
    /**
     * @var Model
     */
    private $template;
    private $code;
    private $sender;
    private $subject;
    private $recipients;
    private $variables;
    private $bcc;
    private $body;
    private static $pushCallback;
    /**
     * @var Config
     */
    private $config;

    /**
     * Mailer constructor.
     *
     * @param Config $config
     * @param null   $code
     * @param array  $variable
     */
    public function __construct( Config $config, $code = null, array $variable = [] )
    {
        if ( null !== $code ) {
            $this->setCode( $code );
            $this->setVariables( $variable );
        }
        $this->config = $config;
    }

    /**
     * @param mixed $pushCallback
     */
    public static function setPushCallback( $pushCallback )
    {
        self::$pushCallback = $pushCallback;
    }


    /**
     * @return Model
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param Model $template
     *
     * @return Mailer
     */
    public function setTemplate( Model $template )
    {
        $this->template = $template;
        $this->setBcc( $this->template->bcc );
        $this->setSender( $this->template->sender );
        $this->setRecipients( $this->template->recipients );
        $this->setSubject( $this->template->subject );
        $this->setBody( $this->template->body );
        $this->setSubject( $this->template->subject );

        return $this;
    }

    /**
     * @param array $variable
     * @param null  $body
     *
     * @throws \Exception
     */
    public function push( array $variable = [], $body = null )
    {
        $this->setVariables( $variable );
        $this->setBody( $body );
        if ( is_callable( static::$pushCallback ) ){
            $pushCallback = self::$pushCallback;
            self::$pushCallback( [
                'code'       => $this->getCode(),
                'variables'  => serialize( $variable ),
                'bcc'        => $this->getBcc(),
                'recipients' => $this->getRecipients(),
                'sender'     => $this->getSender(),
                'body'       => $body,
            ] );
        }
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return Mailer
     */
    public function setCode( $code )
    {

        if ( is_object( $code ) && $code instanceof MailTemplate ) {
            $this->code = $code->code;
            $this->setTemplate( $code );
        } else {
            $this->code = $code;
            if ( empty( $this->template ) ) {
                $this->setTemplate( MailTemplate::findFirst( "code='$code' AND status = 1" ) );
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param mixed $bcc
     *
     * @return Mailer
     */
    public function setBcc( $bcc )
    {
        $this->bcc = $this->formatEmail( $bcc );

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @param mixed $recipients
     *
     * @return Mailer
     */
    public function setRecipients( $recipients )
    {
        $this->recipients = $this->formatEmail( $recipients );

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param mixed $sender
     *
     * @return Mailer
     */
    public function setSender( $sender )
    {
        $this->sender = $sender ?: $this->sender;

        return $this;
    }

    /**
     * @param array $data
     * @param null  $file
     *
     * @return bool
     */
    public function send( array $data = [], $file = null )
    {
        $attachment = null;
        if ( null !== $file ) {
            if ( !is_readable( $file ) ) {
                throw new RuntimeException( "File [$file] can't be read!" );
            }
            $fileName = basename( $file );
            $attachment = Swift_Attachment::fromPath( $file )->setFilename( $fileName );
        }
        if ( !empty( $data['bcc'] ) ) $this->setBcc( $data['bcc'] );
        if ( !empty( $data['sender'] ) ) $this->setSender( $data['sender'] );
        if ( !empty( $data['recipients'] ) ) $this->setRecipients( $data['recipients'] );
        if ( !empty( $data['variables'] ) ) $this->setVariables( $data['variables'] );
        if ( !empty( $data['subject'] ) ) $this->setSubject( $data['subject'] );
        if ( !empty( $data['body'] ) ) $this->setBody( $data['body'] );
        /** Cek Recipients */
        if ( count( $this->getRecipients() ) === 0 ) {
            throw new RuntimeException( 'Invalid Recipients Email' );
        }
        /** Cek Subject */
        if ( empty( $this->getSubject() ) ) {
            throw new RuntimeException( 'Invalid Subject Email' );
        }
        /** @var string $body Prepare Body */
        $body = $this->prepareBody();

        if ( empty( $body ) ) {
            throw new RuntimeException( 'Invalid Body Email' );
        }

        $this->template = null;//Reset the template object

        return $this->sendMail( $this->getSubject(), $this->getRecipients(), $body, $attachment, $this->getSender() ?: null, $this->getBcc() ?: null );
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     *
     * @return Mailer
     */
    public function setSubject( $subject )
    {
        $this->subject = $subject ?: $this->subject;

        return $this;
    }

    /**
     * @return bool
     */
    protected function prepareBody()
    {
        $compiler = new Compiler();

        $content = $this->getBody();

        if ( empty( $content ) ) {
            return $content;
        }

        ob_start();

        extract( $this->getVariables(), EXTR_OVERWRITE );

        @eval( ' ?>' . $compiler->compileString( $content ) );

        $body = ob_get_contents();

        ob_end_clean();

        return $body;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     *
     * @return Mailer
     */
    public function setBody( $body )
    {
        $this->body = $body ?: $this->body;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariables()
    {
        return (array) $this->variables;
    }

    /**
     * @param mixed $variables
     *
     * @return Mailer
     */
    public function setVariables( $variables )
    {

        if ( is_string( $variables ) ) {
            $variables = @unserialize( $variables );
        }

        if ( is_array( $variables ) ) {
            $this->variables = array_merge( $this->getVariables(), $variables );
        }

        return $this;
    }

    /**
     * @param $to
     *
     * @return array
     */
    private function formatEmail( $to )
    {
        $emails = [];
        if ( !is_array( $to ) ) {
            $to = explode( ',', $to );
        }
        foreach ( $to as $i => $email ) {
            if ( !$email ) {
                continue;
            }

            if ( !is_numeric( $i ) && strpos( $i, '@' ) !== false ) {
                $email = trim( $email );
                $emails[$i] = $email;
            } else {
                $email = trim( $email );
                $name = null;
                if ( strpos( $email, ':' ) !== false ) {
                    list( $email, $name ) = explode( ':', $email );
                }

                if ( strpos( $email, '@' ) === false ) {
                    continue;
                }

                if ( $name ) {
                    $emails[$email] = $name;
                } else {
                    $emails[] = $email;
                }
            }
        }

        return $emails;
    }

    /**
     * @param      $subject
     * @param      $recipient
     * @param      $emailBody
     * @param null $attachment
     * @param null $sender
     * @param null $bcc
     *
     * @return bool
     */
    private function sendMail( $subject, $recipient, $emailBody, $attachment = null, $sender = null, $bcc = null )
    {
        if ( empty( $this->config->sender ) || empty( $this->config->key ) || empty( $this->config->secret ) ) {
            throw new RuntimeException( "Please check your configuration" );
        }

        $transport = Swift_AWSTransport::newInstance( $this->config->key, $this->config->secret );
        $transport->setDebug( false ); // Print the response from AWS to the error log for debugging.

        //Create the Mailer using your created Transport
        $mailer = new Swift_Mailer( $transport );

        //Create the message

        /** @var Message $message */
        $message = ( new Message() )
            ->setSubject( $subject )
            ->setFrom( $sender ?: $this->config->sender )
            ->setTo( $recipient )
            ->setBody( $emailBody, 'text/html' );
        if ( null !== $bcc ) {
            $message->setBcc( $bcc );
        }
        if ( null !== $attachment ) {
            if ( !is_array( $attachment ) && $attachment instanceof Swift_Mime_Attachment ) {
                $message->attach( $attachment );
            } else if ( is_array( $attachment ) ) {
                foreach ( $attachment as $attach ) {
                    if ( $attach instanceof Swift_Mime_Attachment ) {
                        $message->attach( $attach );
                    }
                }
            }
        }
        return !!$mailer->send( $message );
    }
}
