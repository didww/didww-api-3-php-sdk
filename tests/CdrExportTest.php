<?php

namespace Didww\Tests;

class CdrExportTest extends BaseTest
{
    public function testCdrExportCreate()
    {
        $this->startVCR('cdr_exports.yml');

        $cdrExport = new \Didww\Item\CdrExport();
        $cdrExport->setFilterDidNumber('1234556789');
        $cdrExport->setFilterYear('2019');
        $cdrExport->setFilterMonth('01');

        $this->assertEquals($cdrExport->toJsonApiArray(), [
            'type' => 'cdr_exports',
            'attributes' => [
                'filters' => [
                  'did_number' => '1234556789',
                  'year' => '2019',
                  'month' => '01',
                ],
            ],
        ]);
        $cdrExportDocument = $cdrExport->save();
        $cdrExport = $cdrExportDocument->getData();
        $this->assertInstanceOf('Didww\Item\CdrExport', $cdrExport);

        $this->assertEquals($cdrExport->getAttributes(), [
            'filters' => (object) [
                   'did_number' => '1234556789',
                   'year' => '2019',
                   'month' => '01',
            ],
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'Pending',
            'url' => null,
            'callback_url' => null,
            'callback_method' => null,
        ]);

        $this->stopVCR();
    }

    public function testAll()
    {
        $this->startVCR('cdr_exports.yml');

        $cdrExportsDocument = \Didww\Item\CdrExport::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\CdrExport', $cdrExportsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('cdr_exports.yml');

        $uuid = 'da15f006-5da4-45ca-b0df-735baeadf423';
        $cdrExportsDocument = \Didww\Item\CdrExport::find($uuid);
        $cdrExport = $cdrExportsDocument->getData();

        $this->assertInstanceOf('Didww\Item\CdrExport', $cdrExport);
        $this->assertEquals($cdrExport->getAttributes(), [
          'filters' => (object) [
                 'did_number' => '1234556789',
                 'year' => '2019',
                 'month' => '01',
          ],
          'created_at' => '2019-01-02T10:23:00.897Z',
          'status' => 'Completed',
          'url' => 'https://sandbox-api.didww.com/v3/cdr_exports/e5352384-6f64-4132-bba1-cda18fbc5896.csv',
          'callback_url' => null,
          'callback_method' => null,
        ]);
        $this->stopVCR();
    }

    public function testDownload()
    {
        $this->startVCR('cdr_exports.yml');

        $uuid = '5a03dd1e-6018-44c6-b98b-084999b376ce';

        $csvFixture = 'tests/fixtures/csv/cdrExport.csv';
        $cdrExport = \Didww\Item\CdrExport::build($uuid, ['url' => 'https://sandbox-api.didww.com/v3/cdr_exports/02bf6df4-3af9-416c-96be-16e5b7eeb651.csv']);

        $result = $cdrExport->download($csvFixture);
        $this->assertTrue($result);
        $this->assertStringNotEqualsFile($csvFixture, '');
        unlink($csvFixture);
        $this->stopVCR();
    }
}
