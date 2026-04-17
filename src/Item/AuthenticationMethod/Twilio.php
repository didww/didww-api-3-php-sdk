<?php

namespace Didww\Item\AuthenticationMethod;

class Twilio extends Base
{
    public function getType(): string
    {
        return 'twilio';
    }

    public function getTwilioAccountSid(): ?string
    {
        return $this->attribute('twilio_account_sid');
    }

    public function setTwilioAccountSid(string $twilioAccountSid): void
    {
        $this->attributes['twilio_account_sid'] = $twilioAccountSid;
    }
}
