<?php

namespace Didww\Tests;

class SupportingDocumentTemplateTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'supporting_document_templates.yml';
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
}
