<?php

// End-to-end SIP registration flow on /voice_in_trunks (API 2026-04-16):
// create with sip_registration enabled → rename → disable by setting host
// → re-enable by toggling the flag. Demonstrates how the SDK keeps the
// dependent fields (host, port, use_did_in_ruri) aligned with the server's
// validation rules. The sandbox trunk is left in place after the script
// completes.
//
// Usage: DIDWW_API_KEY=your_api_key php examples/voice_in_trunk_sip_registration.php

require_once 'bootstrap.php';

use Didww\Item\Configuration\Sip;
use Didww\Item\VoiceInTrunk;

echo "=== PHP SDK — SIP registration flow ===\n";

// 1) Create with sip_registration enabled.
echo "\n[1/4] Create with sip_registration enabled...\n";
$sip = new Sip([
    'enabled_sip_registration' => true,
    'use_did_in_ruri' => true,
    'cnam_lookup' => false,
    'codec_ids' => [9, 8],
    'transport_protocol_id' => 1,
]);
$trunk = new VoiceInTrunk([
    'name' => 'php-sip-registration-'.time(),
    'priority' => 1,
    'weight' => 100,
    'cli_format' => 'e164',
    'ringing_timeout' => 30,
    'configuration' => $sip,
]);
$created = $trunk->save()->getData();
$trunkId = $created->getId();
$cfg1 = $created->getConfiguration();
echo "  id=$trunkId\n";
echo '  incoming_auth_username='.var_export($cfg1->getIncomingAuthUsername(), true)."\n";
echo '  incoming_auth_password='.var_export($cfg1->getIncomingAuthPassword(), true)."\n";

// 2) Rename — single-field PATCH.
echo "\n[2/4] Rename trunk...\n";
$created->setName('php-renamed-'.time());
$created->save();
echo '  name='.$created->getName()."\n";

// 3) Disable sip_registration by setting host. Use the setter — the
//    constructor calls fill() which bypasses the cascade (so server
//    responses deserialize as-is).
echo "\n[3/4] Disable by setting host...\n";
$disableSip = new Sip();
$disableSip->setHost('203.0.113.10');
$disable = new VoiceInTrunk(['configuration' => $disableSip]);
$disable->setId($trunkId);
$disable->save();
$fresh3 = VoiceInTrunk::find($trunkId)->getData()->getConfiguration();
echo '  enabled_sip_registration='.var_export($fresh3->getEnabledSipRegistration(), true)."\n";
echo '  use_did_in_ruri='.var_export($fresh3->getUseDidInRuri(), true)."\n";
echo '  host='.var_export($fresh3->getHost(), true)."\n";
echo '  incoming_auth_username='.var_export($fresh3->getIncomingAuthUsername(), true)."\n";

// 4) Re-enable sip_registration. The SDK should send host=null / port=null on
//    the wire so the server clears the values it had persisted.
echo "\n[4/4] Re-enable by toggling enabled_sip_registration...\n";
$reEnableSip = new Sip();
$reEnableSip->setEnabledSipRegistration(true);
$reEnableSip->setUseDidInRuri(true);
$reEnable = new VoiceInTrunk(['configuration' => $reEnableSip]);
$reEnable->setId($trunkId);
try {
    $reEnable->save();
    $fresh4 = VoiceInTrunk::find($trunkId)->getData()->getConfiguration();
    echo '  enabled_sip_registration='.var_export($fresh4->getEnabledSipRegistration(), true)."\n";
    echo '  host='.var_export($fresh4->getHost(), true)."\n";
    echo '  incoming_auth_username='.var_export($fresh4->getIncomingAuthUsername(), true)."\n";
    echo "\n=== PASS — trunk $trunkId left in sandbox ===\n";
} catch (Throwable $e) {
    echo '  ✗ FAIL: '.$e->getMessage()."\n";
    echo "\n=== FAIL at re-enable — trunk $trunkId left in sandbox ===\n";
}
