<?php

namespace Didww\Tests;

class PermanentSupportingDocumentTest extends BaseTest
{
    public function testCreatePermanentSupportingDocument()
    {
        $this->startVCR('permanent_supporting_documents.yml');
        $identity = \Didww\Item\Identity::build('5e9df058-50d2-4e34-b0d4-d1746b86f41a');
        $template = \Didww\Item\SupportingDocumentTemplate::build('4199435f-646e-4e9d-a143-8f3b972b10c5');
        $encryptedFiles = new \Swis\JsonApi\Client\Collection([
            \Didww\Item\EncryptedFile::build('254b3c2d-c40c-4ff7-93b1-a677aee7fa10'),
        ]);

        $permanentSupportingDoc = new \Didww\Item\PermanentSupportingDocument();
        $permanentSupportingDoc->setIdentity($identity);
        $permanentSupportingDoc->setTemplate($template);
        $permanentSupportingDoc->setFiles($encryptedFiles);
        $permanentSupportingDocDocument = $permanentSupportingDoc->save(['include' => 'template']);
        $permanentSupportingDoc = $permanentSupportingDocDocument->getData();
        $this->assertInstanceOf('Didww\Item\PermanentSupportingDocument', $permanentSupportingDoc);
        $this->assertInstanceOf('Didww\Item\SupportingDocumentTemplate', $permanentSupportingDoc->template()->getIncluded());

        $this->stopVCR();
    }

    public function testDeletePermanentSupportingDocument()
    {
        $this->startVCR('permanent_supporting_documents.yml');

        $permanentSupportingDoc = \Didww\Item\PermanentSupportingDocument::build('19510da3-c07e-4fa9-a696-6b9ab89cc172');

        $permanentSupportingDocDocument = $permanentSupportingDoc->delete();

        $this->assertFalse($permanentSupportingDocDocument->hasErrors());
        $this->stopVCR();
    }
}
