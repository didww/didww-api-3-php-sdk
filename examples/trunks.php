<?php

require_once 'bootstrap.php';

use Didww\Enum\CliFormat;
use Didww\Enum\MediaEncryptionMode;
use Didww\Enum\SstRefreshMethod;
use Didww\Enum\TransportProtocol;

// create Sip Configuration object
$trunkSipConfig = new Didww\Item\Configuration\Sip([
    'username' => 'username',
    'host' => '216.58.215.110',
    'sst_refresh_method_id' => SstRefreshMethod::INVITE,
    'transport_protocol_id' => TransportProtocol::UDP,
    'media_encryption_mode' => MediaEncryptionMode::DISABLED,
    'port' => 5060,
    'codec_ids' => Didww\Item\Configuration\Base::getDefaultCodecIds(),
    'rerouting_disconnect_code_ids' => Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(),
]);

$trunk = new Didww\Item\VoiceInTrunk();
// set name (should be unique)
$trunk->setName('My New Custom Sip Trunk '.uniqid());
// set CLI format using enum
$trunk->setCliFormat(CliFormat::RAW);
// set configuration object
// see also \Didww\Item\Configuration\Pstn
$trunk->setConfiguration($trunkSipConfig);

$trunk->setRingingTimeout(30);
$trunkDocument = $trunk->save();

if ($trunkDocument->hasErrors()) {
    var_dump($trunkDocument->getErrors());
} else {
    $trunk = $trunkDocument->getData();
    var_dump(
        $trunk->getId(), // 1f6fc2bd-f081-4202-9b1a-d9cb88d942b9
        $trunk->getName(), // "My New Custom Sip Trunk 5c2e393794b07"
        $trunk->getCliFormat(), // CliFormat::RAW
        $trunk->getCreatedAt(), // object(DateTime)
        $trunk->getRingingTimeout() // int(30)
    );

    // Configuration enum getters
    $sip = $trunk->getConfiguration();
    var_dump(
        $sip->getSstRefreshMethodId(), // SstRefreshMethod::INVITE
        $sip->getMediaEncryptionMode(), // MediaEncryptionMode::DISABLED
        $sip->getCodecIds() // [Codec::PCMU, Codec::PCMA, ...]
    );
}
