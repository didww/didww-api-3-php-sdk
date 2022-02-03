<?php

namespace Didww\Tests;

class VoiceOutTrunkRegenerateCredentialTest extends BaseTest
{
    public function testVoiceOutTrunkRegenerateCredential()
    {
        $this->startVCR('voice_out_trunk_regenerate_credentials.yml');

        $voiceOutTrunk = \Didww\Item\VoiceOutTrunk::build('5fc59e7e-79eb-498a-8779-800416b5c68a');
        $regenerateCredentials = new \Didww\Item\VoiceOutTrunkRegenerateCredential();
        $regenerateCredentials->setVoiceOutTrunk($voiceOutTrunk);
        $regenerateCredentialsDocument = $regenerateCredentials->save();

        $data = $regenerateCredentialsDocument->getData();
        $this->assertInstanceOf('Didww\Item\VoiceOutTrunkRegenerateCredential', $data);
        $this->assertEquals(
            '5fc59e7e-79eb-498a-8779-800416b5c68a',
            $data->getId()
        );
        $this->assertEquals(
            [],
            $data->getAttributes()
        );

        $this->stopVCR();
    }
}
