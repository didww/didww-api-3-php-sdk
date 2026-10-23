<?php

namespace Didww\Item\SmsTrunkConfiguration;

/**
 * Inbound messages are forwarded as email. All attributes are required
 * when the trunk is created.
 */
class SmsToEmail extends Base
{
    public function getType(): string
    {
        return 'sms_to_email';
    }

    public function getSendTo(): ?string
    {
        return $this->attribute('send_to');
    }

    public function setSendTo(string $sendTo)
    {
        $this->attributes['send_to'] = $sendTo;
    }

    public function getSubject(): ?string
    {
        return $this->attribute('subject');
    }

    public function setSubject(string $subject)
    {
        $this->attributes['subject'] = $subject;
    }

    public function getMessage(): ?string
    {
        return $this->attribute('message');
    }

    public function setMessage(string $message)
    {
        $this->attributes['message'] = $message;
    }
}
