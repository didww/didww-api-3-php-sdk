<?php

namespace Didww\Tests;

use Didww\Credentials;
use Didww\Enum\DiversionInjectMode;
use Didww\Enum\NetworkProtocolPriority;
use Didww\Item\Configuration\Sip;
use Didww\Item\VoiceInTrunk;

/**
 * Cassette-backed CRUD test for the 2026-04-16 SIP-registration flow on
 * /v3/voice_in_trunks. Three interactions are recorded:
 *
 *   1. POST — create with `enabled_sip_registration: true` (no host/port).
 *      Server returns 201 with server-generated `incoming_auth_*`.
 *   2. GET — read shape; host/port/username come back null.
 *   3. PATCH — disable: must also send host non-blank + use_did_in_ruri:false.
 *      Server returns 200 with `incoming_auth_*` cleared.
 *
 * To re-record: set DIDWW_SANDBOX_API_KEY, delete the cassette, run the
 * suite. The default `once` mode records a fresh cassette in one shot.
 */
class VoiceInTrunkSipRegistrationTest extends CassetteTest
{
    private ?string $createdTrunkId = null;

    protected function getCassetteName(): string
    {
        return 'voice_in_trunks_sip_registration.yml';
    }

    protected function getDidwwCredentials(): Credentials
    {
        return new Credentials(getenv('DIDWW_SANDBOX_API_KEY') ?: 'PLACEYOURAPIKEYHERE', 'sandbox');
    }

    protected function tearDown(): void
    {
        if ($this->createdTrunkId && getenv('DIDWW_SANDBOX_API_KEY')) {
            try {
                $trunk = VoiceInTrunk::find($this->createdTrunkId)->getData();
                $trunk?->delete();
            } catch (\Throwable) {
                // Best-effort sandbox cleanup.
            }
        }
        parent::tearDown();
    }

    public function testCreateGetAndDisableSipRegistrationFlow(): void
    {
        $created = (new VoiceInTrunk([
            'configuration' => new Sip([
                'enabled_sip_registration' => true,
                'use_did_in_ruri' => true,
                'cnam_lookup' => true,
                'diversion_relay_policy' => 'as_is',
                'diversion_inject_mode' => 'did_number',
                'network_protocol_priority' => 'prefer_ipv4',
                'codec_ids' => [9, 7],
                'transport_protocol_id' => 1,
            ]),
            'name' => 'sip-registration-trunk',
            'priority' => 1,
            'weight' => 100,
            'cli_format' => 'e164',
            'ringing_timeout' => 30,
        ]))->save()->getData();
        $this->createdTrunkId = $created->getId();
        $this->assertNotEmpty($this->createdTrunkId);

        $createdConfig = $created->getConfiguration();
        $this->assertInstanceOf(Sip::class, $createdConfig);
        $this->assertTrue($createdConfig->getEnabledSipRegistration());
        $this->assertTrue($createdConfig->getUseDidInRuri());
        $this->assertTrue($createdConfig->getCnamLookup());
        $this->assertEquals(DiversionInjectMode::DID_NUMBER, $createdConfig->getDiversionInjectMode());
        $this->assertEquals(NetworkProtocolPriority::PREFER_IPV4, $createdConfig->getNetworkProtocolPriority());
        $this->assertNotEmpty($createdConfig->getIncomingAuthUsername());
        $this->assertNotEmpty($createdConfig->getIncomingAuthPassword());
        // host/port/username come back blank — sip_registration overrides them.
        $this->assertNull($createdConfig->getHost());
        $this->assertNull($createdConfig->getPort());
        $this->assertNull($createdConfig->getUsername());

        $fetchedConfig = VoiceInTrunk::find($this->createdTrunkId)->getData()->getConfiguration();
        $this->assertSame($createdConfig->getIncomingAuthUsername(), $fetchedConfig->getIncomingAuthUsername());
        $this->assertSame($createdConfig->getIncomingAuthPassword(), $fetchedConfig->getIncomingAuthPassword());

        // Disable: server requires host non-blank + use_did_in_ruri:false in the same PATCH.
        $update = new VoiceInTrunk([
            'configuration' => new Sip([
                'enabled_sip_registration' => false,
                'use_did_in_ruri' => false,
                'host' => '203.0.113.10',
            ]),
        ]);
        $update->setId($this->createdTrunkId);
        $disabledConfig = $update->save()->getData()->getConfiguration();
        $this->assertFalse($disabledConfig->getEnabledSipRegistration());
        $this->assertFalse($disabledConfig->getUseDidInRuri());
        $this->assertEquals('203.0.113.10', $disabledConfig->getHost());
        // Server clears the auto-generated credentials after disable.
        $this->assertNull($disabledConfig->getIncomingAuthUsername());
        $this->assertNull($disabledConfig->getIncomingAuthPassword());
    }
}
