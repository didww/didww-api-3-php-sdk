<?php

namespace Didww\Tests;

class SupportingDocumentTemplateTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'supporting_document_templates.yml';
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

    public function testAllWithIncludesAndPagination()
    {
        $supportingDocumentTemplatesDocument = \Didww\Item\SupportingDocumentTemplate::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $supportingDocumentTemplates = $supportingDocumentTemplatesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\SupportingDocumentTemplate', $supportingDocumentTemplates);

        $this->assertEquals(30, $supportingDocumentTemplatesDocument->getMeta()['total_records']);

        $first = $supportingDocumentTemplates[0];
        $this->assertIsString($first->getName());
        $this->assertEquals('Generic LOI', $first->getName());
        $this->assertIsBool($first->getPermanent());
        $this->assertFalse($first->getPermanent());
        $this->assertIsString($first->getUrl());
        $this->assertStringStartsWith('https://', $first->getUrl());

        $second = $supportingDocumentTemplates[1];
        $this->assertEquals('Belgium Registration Form', $second->getName());
        $this->assertTrue($second->getPermanent());
    }

    public function testDownload()
    {
        $template = \Didww\Item\SupportingDocumentTemplate::build(
            '206ccec2-1166-461f-9f58-3a56823db548',
            ['url' => 'https://sandbox-api.didww.com/storage/public/w7f2irbo819la7vd7up7u67pkmkn']
        );

        $destFile = tempnam(sys_get_temp_dir(), 'didww_test_');
        $result = $template->download($destFile);

        $this->assertTrue($result);
        $this->assertEquals('Generic LOI template contents', file_get_contents($destFile));
        unlink($destFile);
    }

    public function testDownloadToResourceHandle()
    {
        $template = \Didww\Item\SupportingDocumentTemplate::build(
            '206ccec2-1166-461f-9f58-3a56823db548',
            ['url' => 'https://sandbox-api.didww.com/storage/public/w7f2irbo819la7vd7up7u67pkmkn']
        );

        $destFile = tempnam(sys_get_temp_dir(), 'didww_test_');
        $handle = fopen($destFile, 'w');
        $result = $template->download($handle);
        fclose($handle);

        $this->assertTrue($result);
        $this->assertEquals('Generic LOI template contents', file_get_contents($destFile));
        unlink($destFile);
    }
}
