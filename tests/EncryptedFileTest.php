<?php

namespace Didww\Tests;

class EncryptedFileTest extends BaseTest
{
    public function testAllWithPagination()
    {
        $this->startVCR('encrypted_files.yml');
        $encryptedFilesDocument = \Didww\Item\EncryptedFile::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $encryptedFiles = $encryptedFilesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\EncryptedFile', $encryptedFiles);

        $this->assertEquals(1, $encryptedFilesDocument->getMeta()['total_records']);
        $this->stopVCR();
    }

    public function testDeleteEncryptedFile()
    {
        $this->startVCR('encrypted_files.yml');

        $encryptedFile = \Didww\Item\EncryptedFile::build('7f2fbdca-8008-44ce-bcb6-3537ea5efaac');

        $encryptedFileDocument = $encryptedFile->delete();

        $this->assertFalse($encryptedFileDocument->hasErrors());
        $this->stopVCR();
    }
}
