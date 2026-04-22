<?php

// CRUD for voice out trunks using 2026-04-16 polymorphic authentication_method.
// Note: Voice Out Trunks require additional account configuration.
// Contact DIDWW support to enable.

require_once 'bootstrap.php';

use Didww\Item\AuthenticationMethod\CredentialsAndIp;
use Didww\Item\AuthenticationMethod\IpOnly;
use Didww\Item\AuthenticationMethod\Twilio;
use Didww\Item\VoiceOutTrunk;

// List voice out trunks
echo "=== Listing Voice Out Trunks ===\n";
$document = VoiceOutTrunk::all();
$trunks = $document->getData();
echo 'Found '.count($trunks)." voice out trunks\n";

foreach ($trunks->take(5) as $trunk) {
    echo $trunk->getName().' ('.$trunk->getStatus()->value.")\n";
    echo '  ID: '.$trunk->getId()."\n";
    $auth = $trunk->getAuthenticationMethod();
    echo '  Auth type: '.($auth ? $auth->getType() : 'none')."\n";
    if ($auth instanceof CredentialsAndIp) {
        echo '  Username: '.($auth->getUsername() ?? 'null')."\n";
    } elseif ($auth instanceof IpOnly) {
        echo '  Allowed SIP IPs: '.implode(', ', $auth->getAllowedSipIps() ?? [])."\n";
    } elseif ($auth instanceof Twilio) {
        echo '  Twilio Account SID: '.($auth->getTwilioAccountSid() ?? 'null')."\n";
    }
    echo '  External Reference ID: '.($trunk->getExternalReferenceId() ?? 'null')."\n";
    echo '  Emergency Enable All: '.($trunk->getEmergencyEnableAll() ? 'true' : 'false')."\n";
    echo '  RTP Timeout: '.($trunk->getRtpTimeout() ?? 'null')."\n";
    echo "\n";
}

// Create a voice out trunk with credentials_and_ip authentication
// Note: only credentials_and_ip and twilio can be set via the API.
// ip_only is read-only and can only be configured by DIDWW staff.
echo "\n=== Creating Voice Out Trunk (credentials_and_ip) ===\n";
$suffix = bin2hex(random_bytes(4));

$authMethod = new CredentialsAndIp([
    'allowed_sip_ips' => ['203.0.113.0/24'],
    'tech_prefix' => '',
]);

$voiceOutTrunk = new VoiceOutTrunk();
$voiceOutTrunk->setName("PHP Outbound Trunk $suffix");
$voiceOutTrunk->setAuthenticationMethod($authMethod);
$voiceOutTrunk->setOnCliMismatchAction('reject_call');
$voiceOutTrunk->setExternalReferenceId("php-example-$suffix");
$voiceOutTrunk->setRtpTimeout(60);

$document = $voiceOutTrunk->save();

if ($document->hasErrors()) {
    echo "Error creating trunk:\n";
    var_dump($document->getErrors());
} else {
    $trunk = $document->getData();
    echo 'Created trunk: '.$trunk->getId()."\n";
    echo '  Name: '.$trunk->getName()."\n";
    echo '  Status: '.$trunk->getStatus()->value."\n";
    $auth = $trunk->getAuthenticationMethod();
    echo '  Auth type: '.($auth ? $auth->getType() : 'none')."\n";
    if ($auth instanceof CredentialsAndIp) {
        echo '  Username: '.($auth->getUsername() ?? 'null')."\n";
        echo '  Password: '.($auth->getPassword() ?? 'null')."\n";
    }
    echo '  External Reference ID: '.($trunk->getExternalReferenceId() ?? 'null')."\n";
    echo '  RTP Timeout: '.($trunk->getRtpTimeout() ?? 'null')."\n";

    // Delete trunk
    echo "\n=== Deleting Voice Out Trunk ===\n";
    $deleteDoc = $trunk->delete();
    echo $deleteDoc->hasErrors() ? "Error deleting trunk\n" : "Trunk deleted successfully\n";
}
