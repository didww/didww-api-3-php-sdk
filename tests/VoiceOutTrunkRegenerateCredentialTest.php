<?php

namespace Didww\Tests;

class VoiceOutTrunkRegenerateCredentialTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'voice_out_trunk_regenerate_credentials.yml';
    }
    public function testVoiceOutTrunkRegenerateCredential()
    {

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

    }
}
