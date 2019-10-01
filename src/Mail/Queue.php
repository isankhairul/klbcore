<?php namespace Klb\Core\Mail;

use Kalbe\Model\MailTemplate;
use Kalbe\Worker\QueueMailWorker;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
/**
 * Class Queue
 *
 * @package Klb\Core\Mail
 */
class Queue
{
    /**
     * @var \Phalcon\Mvc\Model
     */
    private $template;
    private $code;
    private $sender;
    private $subject;
    private $recipients;
    private $variables;
    private $bcc;
    private $body;

    /**
     * Queue constructor.
     *
     * @param null|string|MailTemplate $code
     * @param array $variable
     */
    public function __construct($code = null, array $variable = [])
    {
        if (null !== $code) {
            $this->setCode($code);
            $this->setVariables($variable);
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
     * @return Queue
     */
    public function setCode($code)
    {

        if (\is_object($code) && $code instanceof MailTemplate) {
            $this->code = $code->code;
            $this->setTemplate($code);
        } else {
            $this->code = $code;
            if (empty($this->template)) {
                $this->setTemplate(MailTemplate::findFirst("code='$code' AND active = 1"));
            }
        }

        return $this;
    }

    /**
     * @return \Phalcon\Mvc\Model
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param \Phalcon\Mvc\Model $template
     * @return Queue
     */
    public function setTemplate(\Phalcon\Mvc\Model $template)
    {
        $this->template = $template;
        $this->setBcc($this->template->bcc);
        $this->setSender($this->template->sender);
        $this->setRecipients($this->template->recipients);
        $this->setSubject($this->template->subject);
        $this->setBody($this->template->body);
        $this->setSubject($this->template->subject);

        return $this;
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
     * @return Queue
     */
    public function setBody($body)
    {
        $this->body = $body ?: $this->body;

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
     * @return Queue
     */
    public function setSender($sender)
    {
        $this->sender = $sender ?: $this->sender;

        return $this;
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
     * @return Queue
     */
    public function setSubject($subject)
    {
        $this->subject = $subject ?: $this->subject;

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
     * @return Queue
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $this->formatEmail($recipients);

        return $this;
    }

    /**
     * @param $to
     * @return array
     */
    private function formatEmail($to)
    {
        $emails = [];
        if (!\is_array($to)) {
            $to = \explode(',', $to);
        }
        foreach ($to as $i => $email) {
            if (!$email) {
                continue;
            }

            if (!\is_numeric($i) && \strpos($i, '@') !== false) {
                $email = \trim($email);
                $emails[$i] = $email;
            } else {
                $email = \trim($email);
                $name = null;
                if (strpos($email, ':') !== false) {
                    list($email, $name) = explode(':', $email);
                }

                if (\strpos($email, '@') === false) {
                    continue;
                }

                if ($name) {
                    $emails[$email] = $name;
                } else {
                    $emails[] = $email;
                }
            }
        }

        return $emails;
    }

    /**
     * @return mixed
     */
    public function getVariables()
    {
        return (array)$this->variables;
    }

    /**
     * @param mixed $variables
     * @return Queue
     */
    public function setVariables($variables)
    {

        if (\is_string($variables)) {
            $variables = @\unserialize($variables);
        }

        if (\is_array($variables)) {
            $this->variables = \array_merge($this->getVariables(), $variables);
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
     * @return Queue
     */
    public function setBcc($bcc)
    {
        $this->bcc = $this->formatEmail($bcc);

        return $this;
    }

    /**
     * @param null|string|MailTemplate $code
     * @param array $variable
     * @return Queue
     */
    public static function of($code = null, array $variable = [])
    {
        return new self($code, $variable);
    }

    /**
     * @param array $variable
     * @param null $body
     * @param array $queueOptions
     * @return mixed
     */
    public function push(array $variable = [], $body = null, array $queueOptions = [])
    {
        $this->setVariables($variable);
        $this->setBody($body);
        $queue = di('queue');
        $queue->choose(QueueMailWorker::getTubeName());
        $put = $queue->put([
            'code'       => $this->getCode(),
            'variables'  => \serialize($variable),
            'bcc'        => $this->getBcc(),
            'recipients' => $this->getRecipients(),
            'sender'     => $this->getSender(),
            'body'       => $body,
        ], $queueOptions);
        unset($queue);

        return $put;
    }

    /**
     * @return bool
     */
    protected function prepareBody()
    {
        $compiler = new Compiler();

        $content = $this->getBody();

        if (empty($content)) {
            return $content;
        }

        \ob_start();

        \extract($this->getVariables(), EXTR_OVERWRITE);

        @eval(' ?>' . $compiler->compileString($content));

        $body = \ob_get_contents();

        \ob_end_clean();

        return $body;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function send(array $data = [])
    {

        if (!empty($data['bcc'])) $this->setBcc($data['bcc']);
        if (!empty($data['sender'])) $this->setSender($data['sender']);
        if (!empty($data['recipients'])) $this->setRecipients($data['recipients']);
        if (!empty($data['variables'])) $this->setVariables($data['variables']);
        if (!empty($data['subject'])) $this->setSubject($data['subject']);
        if (!empty($data['body'])) $this->setBody($data['body']);
        /** Cek Recipients */
        if (count($this->getRecipients()) === 0) {
            throw new \RuntimeException('Invalid Recipients Email');
        }
        /** Cek Subject */
        if (empty($this->getSubject())) {
            throw new \RuntimeException('Invalid Subject Email');
        }
        /** @var string $body Prepare Body */
        $body = $this->prepareBody();

        if (empty($body)) {
            throw new \RuntimeException('Invalid Body Email');
        }

        $this->template = null;//Reset the template object

        return (new Mail())->sesEmailSend($this->getSubject(), $this->getRecipients(), $body, null, $this->getSender() ?: null, $this->getBcc() ?: null);
    }
}
