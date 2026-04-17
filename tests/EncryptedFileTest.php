<?php

namespace Didww\Tests;

class EncryptedFileTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'encrypted_files.yml';
    }

    public function testAllWithPagination()
    {
        $encryptedFilesDocument = \Didww\Item\EncryptedFile::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $encryptedFiles = $encryptedFilesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\EncryptedFile', $encryptedFiles);

        $this->assertEquals(1, $encryptedFilesDocument->getMeta()['total_records']);
    }

    public function testDeleteEncryptedFile()
    {
        $encryptedFile = \Didww\Item\EncryptedFile::build('7f2fbdca-8008-44ce-bcb6-3537ea5efaac');

        $encryptedFileDocument = $encryptedFile->delete();

        $this->assertFalse($encryptedFileDocument->hasErrors());
    }

    public function testUploadResultSingleFile()
    {
        $responseBody = '{"data":{"id":"f6a7b890-1234-5678-9abc-def123456789","type":"encrypted_files","attributes":{"description":"passport.pdf","expires_at":"2026-04-22T10:00:00.000Z"}},"meta":{"api_version":"2026-04-16"}}';
        $result = new \Didww\UploadResult($responseBody, 201);
        $this->assertTrue($result->success());
        $this->assertFalse($result->hasErrors());
        $this->assertEquals('f6a7b890-1234-5678-9abc-def123456789', $result->getId());
    }

    public function testUploadResultError()
    {
        $responseBody = '{"errors":[{"title":"Invalid fingerprint"}]}';
        $result = new \Didww\UploadResult($responseBody, 422);
        $this->assertFalse($result->success());
        $this->assertTrue($result->hasErrors());
        $this->assertNull($result->getId());
        $this->assertEquals([['title' => 'Invalid fingerprint']], $result->getErrors());
    }

    public function testUploadMethodSignature()
    {
        $reflection = new \ReflectionMethod(\Didww\Item\EncryptedFile::class, 'upload');
        $params = $reflection->getParameters();
        $this->assertEquals('fingerprint', $params[0]->getName());
        $this->assertEquals('fileContent', $params[1]->getName());
        $this->assertEquals('description', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
    }
}
