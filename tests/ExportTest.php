<?php

namespace Didww\Tests;

class ExportTest extends BaseTest
{
    public function testCdrExportCreateCdrIn()
    {
        $this->startVCR('exports.yml');

        $export = new \Didww\Item\Export();
        $export->setExportType('cdr_in');
        $export->setFilterDidNumber('1234556789');
        $export->setFilterYear('2019');
        $export->setFilterMonth('01');

        $this->assertEquals($export->toJsonApiArray(), [
            'type' => 'exports',
            'attributes' => [
                'export_type' => 'cdr_in',
                'filters' => [
                    'did_number' => '1234556789',
                    'year' => '2019',
                    'month' => '01',
                ],
            ],
        ]);
        $exportDocument = $export->save();
        $export = $exportDocument->getData();
        $this->assertInstanceOf('Didww\Item\Export', $export);

        $this->assertEquals($export->getAttributes(), [
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'Pending',
            'url' => null,
            'callback_url' => null,
            'callback_method' => null,
            'export_type' => 'cdr_in',
        ]);

        $this->stopVCR();
    }

    public function testCdrExportCreateCdrOut()
    {
        $this->startVCR('exports.yml');

        $export = new \Didww\Item\Export();
        $export->setExportType('cdr_out');
        $export->setFilterVoiceOutTrunkId('1f6fc2bd-f081-4202-9b1a-d9cb88d942b9');
        $export->setFilterYear('2019');
        $export->setFilterMonth('01');
        $export->setFilterDay('03');

        $this->assertEquals($export->toJsonApiArray(), [
            'type' => 'exports',
            'attributes' => [
                'export_type' => 'cdr_out',
                'filters' => [
                    'voice_out_trunk.id' => '1f6fc2bd-f081-4202-9b1a-d9cb88d942b9',
                    'year' => '2019',
                    'month' => '01',
                    'day' => '03',
                ],
            ],
        ]);
        $exportDocument = $export->save();
        $export = $exportDocument->getData();
        $this->assertInstanceOf('Didww\Item\Export', $export);

        $this->assertEquals($export->getAttributes(), [
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'Pending',
            'url' => null,
            'callback_url' => null,
            'callback_method' => null,
            'export_type' => 'cdr_out',
        ]);

        $this->stopVCR();
    }

    public function testAll()
    {
        $this->startVCR('exports.yml');

        $exportsDocument = \Didww\Item\Export::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Export', $exportsDocument->getData());

        $this->stopVCR();
    }

    public function testFind()
    {
        $this->startVCR('exports.yml');

        $uuid = 'da15f006-5da4-45ca-b0df-735baeadf423';
        $exportsDocument = \Didww\Item\Export::find($uuid);
        $export = $exportsDocument->getData();

        $this->assertInstanceOf('Didww\Item\Export', $export);
        $this->assertEquals($export->getAttributes(), [
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'Completed',
            'url' => 'https://sandbox-api.didww.com/v3/exports/e5352384-6f64-4132-bba1-cda18fbc5896.csv',
            'callback_url' => null,
            'callback_method' => null,
            'export_type' => 'cdr_in',
        ]);
        $this->stopVCR();
    }

    public function testDownload()
    {
        $this->startVCR('exports.yml');

        $uuid = '5a03dd1e-6018-44c6-b98b-084999b376ce';

        $csvFixture = 'tests/fixtures/csv/export.csv';
        $export = \Didww\Item\Export::build($uuid, ['url' => 'https://sandbox-api.didww.com/v3/exports/02bf6df4-3af9-416c-96be-16e5b7eeb651.csv']);

        $result = $export->download($csvFixture);
        $this->assertTrue($result);
        $this->assertStringNotEqualsFile($csvFixture, '');
        unlink($csvFixture);
        $this->stopVCR();
    }
}
