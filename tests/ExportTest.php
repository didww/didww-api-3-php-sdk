<?php

namespace Didww\Tests;

use Didww\Enum\ExportType;

class ExportTest extends CassetteTest
{
    protected function getCassetteName(): string
    {
        return 'exports.yml';
    }

    public function testCdrExportCreateCdrIn()
    {
        $export = new \Didww\Item\Export();
        $export->setExportType('cdr_in');
        $export->setFilterDidNumber('1234556789');
        $export->setFilterFrom('2026-04-01 00:00:00');
        $export->setFilterTo('2026-04-15 23:59:59');

        $this->assertEquals($export->toJsonApiArray(), [
            'type' => 'exports',
            'attributes' => [
                'export_type' => 'cdr_in',
                'filters' => [
                    'did_number' => '1234556789',
                    'from' => '2026-04-01 00:00:00',
                    'to' => '2026-04-15 23:59:59',
                ],
            ],
        ]);
        $exportDocument = $export->save();
        $export = $exportDocument->getData();
        $this->assertInstanceOf('Didww\Item\Export', $export);

        $this->assertEquals($export->getAttributes(), [
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'pending',
            'url' => null,
            'callback_url' => null,
            'callback_method' => null,
            'export_type' => 'cdr_in',
            'external_reference_id' => null,
        ]);
    }

    public function testCdrExportCreateCdrOut()
    {
        $export = new \Didww\Item\Export();
        $export->setExportType('cdr_out');
        $export->setFilterVoiceOutTrunkId('1f6fc2bd-f081-4202-9b1a-d9cb88d942b9');
        $export->setFilterFrom('2026-04-01 00:00:00');
        $export->setFilterTo('2026-04-15 23:59:59');

        $this->assertEquals($export->toJsonApiArray(), [
            'type' => 'exports',
            'attributes' => [
                'export_type' => 'cdr_out',
                'filters' => [
                    'voice_out_trunk.id' => '1f6fc2bd-f081-4202-9b1a-d9cb88d942b9',
                    'from' => '2026-04-01 00:00:00',
                    'to' => '2026-04-15 23:59:59',
                ],
            ],
        ]);
        $exportDocument = $export->save();
        $export = $exportDocument->getData();
        $this->assertInstanceOf('Didww\Item\Export', $export);

        $this->assertEquals($export->getAttributes(), [
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'pending',
            'url' => null,
            'callback_url' => null,
            'callback_method' => null,
            'export_type' => 'cdr_out',
            'external_reference_id' => null,
        ]);
    }

    public function testAll()
    {
        $exportsDocument = \Didww\Item\Export::all();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Export', $exportsDocument->getData());
    }

    public function testFind()
    {
        $uuid = 'da15f006-5da4-45ca-b0df-735baeadf423';
        $exportsDocument = \Didww\Item\Export::find($uuid);
        $export = $exportsDocument->getData();

        $this->assertInstanceOf('Didww\Item\Export', $export);
        $this->assertEquals($export->getAttributes(), [
            'created_at' => '2019-01-02T10:23:00.897Z',
            'status' => 'completed',
            'url' => 'https://sandbox-api.didww.com/v3/exports/e5352384-6f64-4132-bba1-cda18fbc5896.csv.gz',
            'callback_url' => null,
            'callback_method' => null,
            'export_type' => 'cdr_in',
            'external_reference_id' => null,
        ]);
        $this->assertEquals(ExportType::CDR_IN, $export->getExportType());
        $this->assertNull($export->getExternalReferenceId());
    }

    public function testUpdateExportExternalReferenceId()
    {
        $uuid = 'da15f006-5da4-45ca-b0df-735baeadf423';
        $export = \Didww\Item\Export::build($uuid);
        $export->setExternalReferenceId('updated-ref-export');
        $document = $export->save();

        $data = $document->getData();
        $this->assertInstanceOf('Didww\Item\Export', $data);
        $this->assertEquals('updated-ref-export', $data->getExternalReferenceId());
        $this->assertNull($export->getCallbackUrl());
        $this->assertNull($export->getCallbackMethod());
    }

    public function testDownload()
    {
        $uuid = '5a03dd1e-6018-44c6-b98b-084999b376ce';
        $destFile = tempnam(sys_get_temp_dir(), 'didww_test_');

        $export = \Didww\Item\Export::build($uuid, ['url' => 'https://sandbox-api.didww.com/v3/exports/02bf6df4-3af9-416c-96be-16e5b7eeb651.csv.gz']);

        $result = $export->download($destFile);
        $this->assertTrue($result);
        // Verify gzip magic bytes
        $magic = file_get_contents($destFile, false, null, 0, 2);
        $this->assertEquals("\x1f\x8b", $magic);
        unlink($destFile);
    }

    public function testDownloadAndDecompress()
    {
        $uuid = '5a03dd1e-6018-44c6-b98b-084999b376ce';
        $destFile = tempnam(sys_get_temp_dir(), 'didww_dest_');

        $export = \Didww\Item\Export::build($uuid, ['url' => 'https://sandbox-api.didww.com/v3/exports/02bf6df4-3af9-416c-96be-16e5b7eeb651.csv.gz']);

        $result = $export->downloadAndDecompress($destFile);
        $this->assertTrue($result);
        $content = file_get_contents($destFile);
        $this->assertStringContainsString('Date/Time Start (UTC)', $content);
        $this->assertStringContainsString('972397239159652', $content);
        unlink($destFile);
    }

    public function testExportSetters()
    {
        $export = new \Didww\Item\Export();

        $export->setExportType('cdr_out');
        $this->assertEquals(ExportType::CDR_OUT, $export->getExportType());

        $export->setCallbackUrl('https://example.com/hook');
        $this->assertEquals('https://example.com/hook', $export->getCallbackUrl());

        $export->setCallbackMethod('post');
        $this->assertEquals(\Didww\Enum\CallbackMethod::POST, $export->getCallbackMethod());
    }

    public function testExportStatusPredicates()
    {
        $pending = new \Didww\Item\Export(['status' => 'pending']);
        $this->assertTrue($pending->isPending());
        $this->assertFalse($pending->isProcessing());
        $this->assertFalse($pending->isCompleted());

        $processing = new \Didww\Item\Export(['status' => 'processing']);
        $this->assertFalse($processing->isPending());
        $this->assertTrue($processing->isProcessing());
        $this->assertFalse($processing->isCompleted());

        $completed = new \Didww\Item\Export(['status' => 'completed']);
        $this->assertFalse($completed->isPending());
        $this->assertFalse($completed->isProcessing());
        $this->assertTrue($completed->isCompleted());
    }
}
