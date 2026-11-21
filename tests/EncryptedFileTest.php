<?php

namespace Didww\Tests;

class EncryptedFileTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'encrypted_files.yml';
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Guard against php-vcr silently making a real sandbox call (and
        // re-recording the cassette) if a request doesn't exactly match what
        // is on tape: force a hard failure instead.
        \VCR\VCR::configure()->setMode(\VCR\VCR::MODE_NONE);
    }

    protected function tearDown(): void
    {
        \VCR\VCR::configure()->setMode(\VCR\VCR::MODE_NEW_EPISODES);
        parent::tearDown();
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

    private function buildDataFiles(string $delimiter, array $files, array $fields): string
    {
        $reflection = new \ReflectionMethod(\Didww\Item\EncryptedFile::class, 'buildDataFiles');

        return $reflection->invoke(null, $delimiter, $files, $fields);
    }

    public function testBuildDataFilesMultipartStructureWithDescription()
    {
        $body = $this->buildDataFiles(
            '-------------TESTBOUNDARY0001',
            [['name' => 'encrypted_files[file]', 'data' => 'plain file bytes']],
            [
                ['name' => 'encrypted_files[encryption_fingerprint]', 'data' => 'fp-abc123'],
                ['name' => 'encrypted_files[description]', 'data' => 'test upload description'],
            ]
        );

        $this->assertEquals(
            "---------------TESTBOUNDARY0001\r\n"
            ."Content-Disposition: form-data; name=\"encrypted_files[encryption_fingerprint]\"\r\n\r\n"
            ."fp-abc123\r\n"
            ."---------------TESTBOUNDARY0001\r\n"
            ."Content-Disposition: form-data; name=\"encrypted_files[description]\"\r\n\r\n"
            ."test upload description\r\n"
            ."---------------TESTBOUNDARY0001\r\n"
            ."Content-Disposition: form-data; name=\"encrypted_files[file]\"; filename=\"file.enc\"\r\n"
            ."Content-Type: application/octet-stream\r\n"
            ."Content-Transfer-Encoding: binary\r\n\r\n"
            ."plain file bytes\r\n"
            .'---------------TESTBOUNDARY0001--'."\r\n",
            $body
        );
    }

    public function testBuildDataFilesMultipartStructureWithoutDescription()
    {
        $body = $this->buildDataFiles(
            '-------------TESTBOUNDARY0002',
            [['name' => 'encrypted_files[file]', 'data' => 'bad content']],
            [['name' => 'encrypted_files[encryption_fingerprint]', 'data' => 'fp-error-999']]
        );

        // No description field part; only fingerprint + the single file part.
        $this->assertEquals(
            "---------------TESTBOUNDARY0002\r\n"
            ."Content-Disposition: form-data; name=\"encrypted_files[encryption_fingerprint]\"\r\n\r\n"
            ."fp-error-999\r\n"
            ."---------------TESTBOUNDARY0002\r\n"
            ."Content-Disposition: form-data; name=\"encrypted_files[file]\"; filename=\"file.enc\"\r\n"
            ."Content-Type: application/octet-stream\r\n"
            ."Content-Transfer-Encoding: binary\r\n\r\n"
            ."bad content\r\n"
            .'---------------TESTBOUNDARY0002--'."\r\n",
            $body
        );
    }

    public function testUploadSendsExpectedMultipartRequestAndParsesSuccessResponse()
    {
        $result = \Didww\Item\EncryptedFile::upload(
            'fp-abc123',
            'plain file bytes',
            'test upload description',
            'TESTBOUNDARY0001'
        );

        $this->assertEquals(201, $result->getResponseCode());
        $this->assertTrue($result->success());
        $this->assertFalse($result->hasErrors());
        $this->assertEquals('aaaa1111-2222-3333-4444-555566667777', $result->getId());
    }

    public function testUploadSendsExpectedMultipartRequestAndParsesErrorResponse()
    {
        $result = \Didww\Item\EncryptedFile::upload(
            'fp-error-999',
            'bad content',
            null,
            'TESTBOUNDARY0002'
        );

        $this->assertEquals(422, $result->getResponseCode());
        $this->assertFalse($result->success());
        $this->assertTrue($result->hasErrors());
        $this->assertNull($result->getId());
        $this->assertEquals([['title' => 'Invalid fingerprint']], $result->getErrors());
    }
}
