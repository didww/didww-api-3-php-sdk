<?php

namespace Didww\Tests;

class SupportingDocumentTemplateTest extends BaseTest
{
    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('supporting_document_templates.yml');
        $supportingDocumentTemplatesDocument = \Didww\Item\SupportingDocumentTemplate::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $supportingDocumentTemplates = $supportingDocumentTemplatesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\SupportingDocumentTemplate', $supportingDocumentTemplates);

        $this->assertEquals(30, $supportingDocumentTemplatesDocument->getMeta()['total_records']);
        $this->stopVCR();
    }
}
