<?php

namespace Didww\Tests;

class ProofTest extends BaseTest
{
    public function testCreateProof()
    {
        $this->startVCR('proofs.yml');
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $proofType = \Didww\Item\ProofType::build('19cd7b22-559b-41d4-99c9-7ad7ad63d5d1');
        $encryptedFiles = new \Swis\JsonApi\Client\Collection([
            \Didww\Item\EncryptedFile::build('254b3c2d-c40c-4ff7-93b1-a677aee7fa10'),
        ]);

        $proof = new \Didww\Item\Proof();
        $proof->setEntity($identity);
        $proof->setProofType($proofType);
        $proof->setFiles($encryptedFiles);
        $proofDocument = $proof->save(['include' => 'proof_type']);
        $proof = $proofDocument->getData();
        $this->assertInstanceOf('Didww\Item\Proof', $proof);
        $this->assertInstanceOf('Didww\Item\ProofType', $proof->proofType()->getIncluded());

        $this->stopVCR();
    }

    public function testDeleteProof()
    {
        $this->startVCR('proofs.yml');

        $proof = \Didww\Item\Proof::build('ed46925b-a830-482d-917d-015858cf7ab9');

        $proofDocument = $proof->delete();

        $this->assertFalse($proofDocument->hasErrors());
        $this->stopVCR();
    }
}
