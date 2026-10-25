<?php

namespace Didww\Item\SmsTrunkConfiguration;

use Didww\Enum\SmsServerLocation;

/**
 * DIDWW connects to the customer's SMSC as an SMPP client. host,
 * system_id, password and server_location are required when the trunk is
 * created.
 */
class SmppSmsc extends Base
{
    protected array $sensitiveAttributes = ['password'];

    public function getType(): string
    {
        return 'smpp_smsc';
    }

    public function getHost(): ?string
    {
        return $this->attribute('host');
    }

    public function setHost(string $host)
    {
        $this->attributes['host'] = $host;
    }

    public function getTxPort(): ?int
    {
        return $this->attribute('tx_port');
    }

    /**
     * Transmitter port. Defaults to 2775.
     */
    public function setTxPort(int $txPort)
    {
        $this->attributes['tx_port'] = $txPort;
    }

    public function getRxPort(): ?int
    {
        return $this->attribute('rx_port');
    }

    /**
     * Receiver port. Defaults to 2776.
     */
    public function setRxPort(int $rxPort)
    {
        $this->attributes['rx_port'] = $rxPort;
    }

    public function getTransceiverMode(): ?bool
    {
        return $this->attribute('transceiver_mode');
    }

    public function setTransceiverMode(bool $transceiverMode)
    {
        $this->attributes['transceiver_mode'] = $transceiverMode;
    }

    public function getConnectionTimeout(): ?int
    {
        return $this->attribute('connection_timeout');
    }

    /**
     * Bind timeout, seconds.
     */
    public function setConnectionTimeout(int $connectionTimeout)
    {
        $this->attributes['connection_timeout'] = $connectionTimeout;
    }

    public function getUseSsl(): ?bool
    {
        return $this->attribute('use_ssl');
    }

    public function setUseSsl(bool $useSsl)
    {
        $this->attributes['use_ssl'] = $useSsl;
    }

    public function getSystemId(): ?string
    {
        return $this->attribute('system_id');
    }

    /**
     * Max 15 characters.
     */
    public function setSystemId(string $systemId)
    {
        $this->attributes['system_id'] = $systemId;
    }

    public function getSystemType(): ?string
    {
        return $this->attribute('system_type');
    }

    /**
     * Max 12 characters; null clears it.
     */
    public function setSystemType(?string $systemType)
    {
        $this->attributes['system_type'] = $systemType;
    }

    public function getPassword(): ?string
    {
        return $this->attribute('password');
    }

    /**
     * Max 8 characters.
     */
    public function setPassword(string $password)
    {
        $this->attributes['password'] = $password;
    }

    public function getServerLocation(): SmsServerLocation|string|null
    {
        return $this->enumAttribute('server_location', SmsServerLocation::class);
    }

    public function setServerLocation(SmsServerLocation|string $serverLocation)
    {
        $this->setEnumAttribute('server_location', $serverLocation);
    }
}
