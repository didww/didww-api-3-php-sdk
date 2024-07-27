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

    public function testUploadFiles()
    {
        $this->startVCR('encrypted_files.yml');

        $fingerprint = 'c74684d7863639169c21c4d04747f8d6fa05cfe3:::8a586bd37fa0000501715321b2e6a7b3ca57894c';
        $files = [
            'file-content-1',
            'file-content-2',
        ];
        $descriptions = [
            'some description',
        ];

        $boundary = '606b3cb6603e8'; // boundary from VCR
        $result = \Didww\Item\EncryptedFile::upload($fingerprint, $files, $descriptions, $boundary);
        $this->assertTrue($result->success());
        $this->assertEquals([
            '6eed102c-66a9-4a9b-a95f-4312d70ec12a',
            '371eafbd-ac6a-485c-aadf-9e3c5da37eb4',
        ], $result->getIds());

        $encryptedFileDocument = \Didww\Item\EncryptedFile::find($result->getIds()[0]);
        $encryptedFile = $encryptedFileDocument->getData();
        $this->assertEquals('some description', $encryptedFile->getDescription());

        $encryptedFileDocument = \Didww\Item\EncryptedFile::find($result->getIds()[1]);
        $encryptedFile = $encryptedFileDocument->getData();
        $this->assertEquals(null, $encryptedFile->getDescription());
        $this->stopVCR();
    }
}
