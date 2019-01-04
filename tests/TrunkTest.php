<?php

namespace Didww\Tests;

class TrunkTest extends BaseTest
{
    public function testAllWithIncludesAndPagination()
    {
        $this->startVCR('trunks.yml');

        $trunksDocument = \Didww\Item\Trunk::all(['sort' => '-created_at', 'include' => 'trunk_group,pop', 'page' => ['size' => 4, 'number' => 1]]);
        $trunks = $trunksDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Trunk', $trunks);
        $trunkGroup = $trunks[0]->trunkGroup()->getIncluded();

        $pop = $trunks[2]->pop()->getIncluded();

        $this->assertInstanceOf('Didww\Item\Pop', $pop);
        $this->assertEquals('US, LA', $pop->getName());
        $this->assertInstanceOf('Didww\Item\TrunkGroup', $trunkGroup);

        $this->assertInstanceOf('Didww\Item\Configuration\Iax2', $trunks[0]->getConfiguration());
        $this->assertInstanceOf('Didww\Item\Configuration\Pstn', $trunks[1]->getConfiguration());
        $this->assertInstanceOf('Didww\Item\Configuration\H323', $trunks[2]->getConfiguration());
        $this->assertInstanceOf('Didww\Item\Configuration\Sip', $trunks[3]->getConfiguration());

        $this->assertEquals($trunksDocument->getMeta()['total_records'], 70);

        $this->stopVCR();
    }

    public function testCreateH323Trunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\H323([
            'dst' => '558540420024',
            'host' => 'example.com',
            'port' => 4569,
            'codec_ids' => \Didww\Item\Configuration\Base::getDefaultCodecIds(),
        ]),
        'name' => 'hello, test h323 trunk',
    ];
        $trunk = new \Didww\Item\Trunk($attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();

        $h323Configuration = $trunk->getConfiguration();
        $this->assertInstanceOf('Didww\Item\Configuration\H323', $h323Configuration);

        $this->assertEquals($attributes['configuration']->getAttributes(), $h323Configuration->getAttributes());

        $this->assertEquals(4569, $h323Configuration->getPort());

        $this->assertEquals('558540420024', $h323Configuration->getDst());
        $this->assertEquals('example.com', $h323Configuration->getHost());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultCodecIds(), $h323Configuration->getCodecIds());

        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $this->assertInstanceOf('Didww\Item\Configuration\H323', $trunk->getConfiguration());
        $this->assertEquals('78146511-7648-45ba-9b26-a4b2cf87db06', $trunk->getId());
        $this->assertEquals($attributes['configuration']->getAttributes(), $trunk->getConfiguration()->getAttributes());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->stopVCR();
    }

    public function testUpdateH323Trunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\H323([
          'host' => 'example2.com',
          'port' => 4567,
        ]),
        'name' => 'hello, updated test h323 trunk',
    ];
        $trunk = \Didww\Item\Trunk::build('78146511-7648-45ba-9b26-a4b2cf87db06', $attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $this->assertInstanceOf('Didww\Item\Configuration\H323', $trunk->getConfiguration());
        $this->assertArraySubset($attributes['configuration']->getAttributes(), $trunk->getConfiguration()->getAttributes());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->stopVCR();
    }

    public function testCreateIax2Trunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\Iax2([
            'dst' => '558540420024',
            'host' => 'example.com',
            'port' => 4569,
            'auth_user' => 'auth_user',
            'auth_password' => 'auth_password',
            'codec_ids' => \Didww\Item\Configuration\Base::getDefaultCodecIds(),
        ]),
        'name' => 'hello, test iax2 trunk',
    ];
        $trunk = new \Didww\Item\Trunk($attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $this->assertEquals('2021b895-52c9-4f65-990b-e57a1abf858d', $trunk->getId());
        $this->assertEquals($attributes['name'], $trunk->getName());

        $iaxConfiguration = $trunk->getConfiguration();
        $this->assertInstanceOf('Didww\Item\Configuration\Iax2', $iaxConfiguration);

        $this->assertEquals($attributes['configuration']->getAttributes(), $iaxConfiguration->getAttributes());

        $this->assertEquals(4569, $iaxConfiguration->getPort());
        $this->assertEquals('auth_user', $iaxConfiguration->getAuthUser());
        $this->assertEquals('auth_password', $iaxConfiguration->getAuthPassword());
        $this->assertEquals('558540420024', $iaxConfiguration->getDst());
        $this->assertEquals('example.com', $iaxConfiguration->getHost());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultCodecIds(), $iaxConfiguration->getCodecIds());

        $this->stopVCR();
    }

    public function testUpdateIaxTrunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\Iax2([
          'port' => 4567,
          'auth_user' => 'new_auth_user',
          'auth_password' => 'new_auth_password',
        ]),
        'name' => 'hello, updated test iax2 trunk',
    ];
        $trunk = \Didww\Item\Trunk::build('2021b895-52c9-4f65-990b-e57a1abf858d', $attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $iaxConfiguration = $trunk->getConfiguration();
        $this->assertInstanceOf('Didww\Item\Configuration\Iax2', $iaxConfiguration);
        $this->assertArraySubset($attributes['configuration']->getAttributes(), $iaxConfiguration->getAttributes());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->stopVCR();
    }

    public function testCreatePstnTrunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\PSTN([
            'dst' => '558540420024',
        ]),
        'name' => 'hello, test pstn trunk',
    ];
        $trunk = new \Didww\Item\Trunk($attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $this->assertInstanceOf('Didww\Item\Configuration\Pstn', $trunk->getConfiguration());
        $this->assertEquals('41b94706-325e-4704-a433-d65105758836', $trunk->getId());
        $this->assertEquals($attributes['configuration']->getAttributes(), $trunk->getConfiguration()->getAttributes());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->stopVCR();
    }

    public function testUpdatePstnTrunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\PSTN([
            'dst' => '558540420025',
        ]),
        'name' => 'hello, updated test pstn trunk',
    ];
        $trunk = \Didww\Item\Trunk::build('41b94706-325e-4704-a433-d65105758836', $attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $this->assertInstanceOf('Didww\Item\Configuration\Pstn', $trunk->getConfiguration());
        $this->assertEquals('558540420025', $trunk->getConfiguration()->getDst());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->stopVCR();
    }

    public function testCreateSipTrunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
          'configuration' => new \Didww\Item\Configuration\Sip([
              'username' => 'username',
              'host' => '216.58.215.110',
              'sst_refresh_method_id' => 1,
              'port' => 5060,
              'codec_ids' => \Didww\Item\Configuration\Base::getDefaultCodecIds(),
              'rerouting_disconnect_code_ids' => \Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(),
          ]),
          'name' => 'hello, test sip trunk',
        ];
        $trunk = new \Didww\Item\Trunk($attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);

        $sipConfiguration = $trunk->getConfiguration();

        $this->assertInstanceOf('Didww\Item\Configuration\Sip', $sipConfiguration);
        $this->assertArraySubset($attributes['configuration']->getAttributes(), $sipConfiguration->getAttributes());

        $this->assertEquals('216.58.215.110', $sipConfiguration->getHost());
        $this->assertEquals(1, $sipConfiguration->getSstRefreshMethodId());
        $this->assertEquals(5060, $sipConfiguration->getPort());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultCodecIds(), $sipConfiguration->getCodecIds());
        $this->assertEquals(\Didww\Item\Configuration\Base::getDefaultReroutingDisconnectCodeIds(), $sipConfiguration->getReroutingDisconnectCodeIds());
        $this->assertEquals('username', $sipConfiguration->getUsername());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->stopVCR();
    }

    public function testUpdateSipTrunk()
    {
        $this->startVCR('trunks.yml');
        $attributes = [
        'configuration' => new \Didww\Item\Configuration\Sip([
            'username' => 'new-username',
            'max_transfers' => 5,
        ]),
        'name' => 'hello, updated test sip trunk',
        'description' => 'just a description',
    ];
        $trunk = \Didww\Item\Trunk::build('a80006b6-4183-4865-8b99-7ebbd359a762', $attributes);
        $trunkDocument = $trunk->save();
        $trunk = $trunkDocument->getData();
        $this->assertInstanceOf('Didww\Item\Trunk', $trunk);
        $this->assertInstanceOf('Didww\Item\Configuration\Sip', $trunk->getConfiguration());
        $this->assertArraySubset($attributes['configuration']->getAttributes(), $trunk->getConfiguration()->getAttributes());
        $this->assertEquals($attributes['name'], $trunk->getName());
        $this->assertEquals($attributes['description'], $trunk->getDescription());
        $this->stopVCR();
    }

    public function testDeleteTrunk()
    {
        $this->startVCR('trunks.yml');

        $trunk = \Didww\Item\Trunk::build('41b94706-325e-4704-a433-d65105758836');

        $trunkDocument = $trunk->delete();

        $this->assertFalse($trunkDocument->hasErrors());
        $this->stopVCR();
    }

    public function testUpdateRelashionships()
    {
        $this->startVCR('trunks.yml');
        $trunk = \Didww\Item\Trunk::build('2021b895-52c9-4f65-990b-e57a1abf858d');
        $trunk->setPop(\Didww\Item\Pop::build('ba7ccbef-82ac-4372-9391-eac90d5c9479'));
        $trunk->setTrunkGroup(\Didww\Item\TrunkGroup::build('837c5764-a6c3-456f-aa37-71fc8f8ca07b'));
        $trunkDocument = $trunk->save(['include' => 'pop,trunk_group']);
        $this->assertFalse($trunkDocument->hasErrors());
        $trunk = $trunkDocument->getData();

        $trunkGroup = $trunk->trunkGroup()->getIncluded();
        $pop = $trunk->pop()->getIncluded();

        $this->assertInstanceOf('Didww\Item\Pop', $pop);
        $this->assertInstanceOf('Didww\Item\TrunkGroup', $trunkGroup);
        $this->assertEquals('ba7ccbef-82ac-4372-9391-eac90d5c9479', $pop->getId());
        $this->assertEquals('837c5764-a6c3-456f-aa37-71fc8f8ca07b', $trunkGroup->getId());

        $this->stopVCR();
    }
}
