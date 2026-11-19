<?php

namespace Didww\Tests;

use Didww\Enum\SenderIdVerificationStatus;
use Didww\Enum\SenderType;
use Didww\Item\SenderIdVerification;
use Didww\Item\SmsTrunk;
use PHPUnit\Framework\Attributes\DataProvider;

class SenderIdVerificationTest extends CassetteTest
{
    private const ID = 'e5a4e2f1-3b26-4a5c-9a0a-2f7c1f3e8d10';
    private const HTTP_OUT_ID = '1a2b3c4d-0003-4a5b-8c6d-7e8f9a0b1c03';

    protected function getCassetteName(): string
    {
        return 'sender_id_verifications.yml';
    }

    public function testAll()
    {
        $verifications = SenderIdVerification::all()->getData();
        $this->assertCount(2, $verifications);
        $this->assertContainsOnlyInstancesOf(SenderIdVerification::class, $verifications);

        $active = $verifications[0];
        $this->assertSame(self::ID, $active->getId());
        $this->assertSame('ACME alerts', $active->getName());
        $this->assertSame(SenderIdVerificationStatus::ACTIVE, $active->getStatus());
        $this->assertTrue($active->isActive());
        $this->assertSame(SenderType::LONG_CODE, $active->getSenderType());
        $this->assertSame(['12125550100', '12125550101'], $active->getSenderIds());
        $this->assertSame('SID-1234', $active->getReference());
        $this->assertNull($active->getRejectReasons());
        $this->assertNull($active->getRejectComment());
        $this->assertSame('2026-11-22', $active->getRenewDate());
        $this->assertEquals(new \DateTime('2026-10-01T10:00:00Z'), $active->getCreatedAt());
        $this->assertEquals(new \DateTime('2026-10-03T12:30:00Z'), $active->getActivatedAt());
        $this->assertNull($active->getClosedAt());
        $this->assertSame('10.0', $active->getMeta()['setup_price']);
        $this->assertSame('15.0', $active->getMeta()['monthly_price']);

        $pending = $verifications[1];
        $this->assertTrue($pending->isChangesRequired());
        $this->assertFalse($pending->isActive());
        $this->assertSame(SenderType::ALPHANUMERIC, $pending->getSenderType());
        $this->assertSame(['Company documents are missing'], $pending->getRejectReasons());
        $this->assertSame('Upload the certificate of incorporation.', $pending->getRejectComment());
        $this->assertNull($pending->getRenewDate());
        $this->assertNull($pending->getActivatedAt());
    }

    public function testFindWithIncludedSmsTrunk()
    {
        $verification = SenderIdVerification::find(self::ID, ['include' => 'sms_trunk'])->getData();
        $trunk = $verification->smsTrunk()->getIncluded();

        $this->assertInstanceOf(SmsTrunk::class, $trunk);
        $this->assertSame(self::HTTP_OUT_ID, $trunk->getId());
        $this->assertSame('Outbound HTTP', $trunk->getName());
        $this->assertInstanceOf(\Didww\Item\SmsTrunkConfiguration\HttpOut::class, $trunk->getConfiguration());
        $this->assertSame('https://sms-out.example.com/outbound_messages', $trunk->getHost());
    }

    public function testUpdateName()
    {
        $verification = SenderIdVerification::build(self::ID);
        $verification->setName('ACME notifications');
        $updated = $verification->save()->getData();

        $this->assertSame('ACME notifications', $updated->getName());
    }

    public function testUpdateFromLoadedSendsOnlyName()
    {
        $verification = SenderIdVerification::find(self::ID)->getData();
        $verification->setName('ACME notifications');
        // Read-only attributes are never sent, even when changed locally.
        $verification->fill(['status' => 'closed', 'sender_ids' => ['12125550199']]);

        $this->assertSame([
            'type' => 'sender_id_verifications',
            'id' => self::ID,
            'attributes' => ['name' => 'ACME notifications'],
        ], $verification->toJsonApiPatchArray());
        $this->assertFalse($verification->save()->hasErrors());
    }

    public function testAssignSmsTrunk()
    {
        $verification = SenderIdVerification::build(self::ID);
        $verification->setSmsTrunk(SmsTrunk::build(self::HTTP_OUT_ID));
        $document = $verification->save();

        $this->assertFalse($document->hasErrors());
        $this->assertSame(self::HTTP_OUT_ID, $document->getData()->smsTrunk()->getIncluded()->getId());
    }

    public function testUnassignSmsTrunk()
    {
        $verification = SenderIdVerification::build(self::ID);
        $verification->setSmsTrunk(null);

        $this->assertSame([
            'type' => 'sender_id_verifications',
            'id' => self::ID,
            'relationships' => ['sms_trunk' => ['data' => null]],
        ], $verification->toJsonApiPatchArray());
        $this->assertFalse($verification->save()->hasErrors());
    }

    public function testUpdateClosed()
    {
        $verification = SenderIdVerification::build('e5a4e2f1-3b26-4a5c-9a0a-2f7c1f3e8d12');
        $verification->setName('ACME notifications');
        $document = $verification->save();

        $this->assertTrue($document->hasErrors());
        $this->assertSame(422, $document->getResponse()->getStatusCode());
        $error = $document->getErrors()->first();
        $this->assertSame('Sender ID Verification is closed and can not be changed', $error->getDetail());
        $this->assertSame('/data', $error->getSource()->getPointer());
    }

    public static function statusProvider(): array
    {
        return [
            'active' => ['active', [true, false, false, false, false, false]],
            'changes_required' => ['changes_required', [false, true, false, false, false, false]],
            'closed' => ['closed', [false, false, true, false, false, false]],
            'closing_in_process' => ['closing_in_process', [false, false, false, true, false, false]],
            'in_process' => ['in_process', [false, false, false, false, true, false]],
            'new' => ['new', [false, false, false, false, false, true]],
            'unknown' => ['future_status', [false, false, false, false, false, false]],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testStatusPredicates(string $status, array $expected)
    {
        $verification = new SenderIdVerification(['status' => $status]);

        $this->assertSame($expected, [
            $verification->isActive(),
            $verification->isChangesRequired(),
            $verification->isClosed(),
            $verification->isClosingInProcess(),
            $verification->isInProcess(),
            $verification->isStatusNew(),
        ]);
    }

    public function testIsNewStillMeansWithoutId()
    {
        $verification = new SenderIdVerification(['status' => 'active']);
        $this->assertTrue($verification->isNew());

        $built = SenderIdVerification::build(self::ID, ['status' => 'new']);
        $this->assertTrue($built->isStatusNew());
        $this->assertFalse($built->isNew());
    }

    public function testUnknownEnumValuesFallBackToRawStrings()
    {
        $verification = new SenderIdVerification(['status' => 'future_status', 'sender_type' => 'future_type']);

        $this->assertSame('future_status', $verification->getStatus());
        $this->assertSame('future_type', $verification->getSenderType());
        $this->assertSame([], (new SenderIdVerification())->getSenderIds());
    }
}
