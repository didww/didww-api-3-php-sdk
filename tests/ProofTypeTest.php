<?php

namespace Didww\Tests;

class ProofTypeTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'proof_types.yml';
    }
    public function testAllWithPagination()
    {
        $proofTypesDocument = \Didww\Item\ProofType::all(
            ['page' => ['size' => 5, 'number' => 1]]
        );
        $proofTypes = $proofTypesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\ProofType', $proofTypes);

        $this->assertEquals(17, $proofTypesDocument->getMeta()['total_records']);

        $first = $proofTypes[0];
        $this->assertEquals('Utility Bill', $first->getName());
        $this->assertEquals('Address', $first->getEntityType());

        $fourth = $proofTypes[3];
        $this->assertEquals('Drivers License', $fourth->getName());
        $this->assertEquals('Personal', $fourth->getEntityType());

    }
}
