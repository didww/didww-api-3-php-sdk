<?php

namespace Didww\Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class IdentityTest extends CassetteTest
{
    use ArraySubsetAsserts;

    protected function getCassetteName(): string
    {
        return 'identities.yml';
    }

    public function testAllWithIncludesAndPagination()
    {
        $identitiesDocument = \Didww\Item\Identity::all(
            [
                'include' => 'country,addresses,proofs,permanent_documents',
                'page' => ['size' => 5, 'number' => 1],
            ]
        );
        $identities = $identitiesDocument->getData();
        $this->assertContainsOnlyInstancesOf('Didww\Item\Identity', $identities);

        $country = $identities[0]->country()->getIncluded();
        $this->assertInstanceOf('Didww\Item\Country', $country);

        $this->assertEquals(2, $identitiesDocument->getMeta()['total_records']);
    }

    public function testCreateIdentityIncludeCountry()
    {
        $attributes = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '123456789',
            'id_number' => 'ABC1234',
            'birth_date' => '1970-01-01',
            'company_name' => 'Test Company Limited',
            'company_reg_number' => '543221',
            'vat_id' => 'GB1234',
            'description' => 'test identity',
            'personal_tax_id' => '987654321',
            'identity_type' => 'Business',
            'external_reference_id' => '111',
        ];
        $country = \Didww\Item\Country::build('1f6fc2bd-f081-4202-9b1a-d9cb88d942b9');

        $identity = new \Didww\Item\Identity($attributes);
        $identity->setCountry($country);
        $this->assertArraySubset($attributes, $identity->getAttributes());
        $identityDocument = $identity->save(['include' => 'country']);
        $identity = $identityDocument->getData();
        $this->assertArraySubset($attributes, $identity->getAttributes());
        $this->assertInstanceOf('Didww\Item\Identity', $identity);
        $this->assertInstanceOf('Didww\Item\Country', $identity->country()->getIncluded());

        // Typed getter assertions
        $this->assertEquals('John', $identity->getFirstName());
        $this->assertEquals('Doe', $identity->getLastName());
        $this->assertEquals('123456789', $identity->getPhoneNumber());
        $this->assertEquals('ABC1234', $identity->getIdNumber());
        $this->assertInstanceOf(\DateTime::class, $identity->getBirthDate());
        $this->assertEquals('1970-01-01', $identity->getBirthDate()->format('Y-m-d'));
        $this->assertEquals('Test Company Limited', $identity->getCompanyName());
        $this->assertEquals('543221', $identity->getCompanyRegNumber());
        $this->assertEquals('GB1234', $identity->getVatId());
        $this->assertEquals('test identity', $identity->getDescription());
        $this->assertEquals('987654321', $identity->getPersonalTaxId());
        $this->assertEquals(\Didww\Enum\IdentityType::BUSINESS, $identity->getIdentityType());
        $this->assertEquals('111', $identity->getExternalReferenceId());
        $this->assertInstanceOf(\DateTime::class, $identity->getCreatedAt());
        $this->assertIsBool($identity->getVerified());
    }

    public function testCreateIdentity()
    {
        $attributes = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '123456789',
            'id_number' => 'ABC1234',
            'birth_date' => '1970-01-01',
            'description' => 'test identity',
            'personal_tax_id' => '987654321',
            'identity_type' => 'Personal',
            'external_reference_id' => '111',
        ];
        $country = \Didww\Item\Country::build('1f6fc2bd-f081-4202-9b1a-d9cb88d942b9');

        $identity = new \Didww\Item\Identity($attributes);
        $identity->setCountry($country);
        $this->assertArraySubset($attributes, $identity->getAttributes());
        $identityDocument = $identity->save();
        $identity = $identityDocument->getData();
        $this->assertArraySubset($attributes, $identity->getAttributes());
        $this->assertInstanceOf('Didww\Item\Identity', $identity);
    }

    public function testUpdateIdentity()
    {
        $attributes = [
            'first_name' => 'Jake',
            'last_name' => 'Johnson',
            'phone_number' => '1111111',
            'id_number' => 'CED4321',
            'birth_date' => '1979-01-01',
            'company_name' => 'Some Company Limited',
            'company_reg_number' => '1222776',
            'vat_id' => 'GB1235',
            'description' => 'test',
            'personal_tax_id' => '983217654',
            'external_reference_id' => '112',
        ];

        $identity = \Didww\Item\Identity::build('e96ae7d1-11d5-42bc-a5c5-211f3c3788ae');
        $identity->fill($attributes);
        $identityDocument = $identity->save();
        $identity = $identityDocument->getData();
        $this->assertInstanceOf('Didww\Item\Identity', $identity);
        $this->assertArraySubset($attributes, $identity->getAttributes());
    }

    public function testGetBirthDateReturnsNullWhenNull()
    {
        $identity = new \Didww\Item\Identity(['birth_date' => null]);
        $this->assertNull($identity->getBirthDate());
    }

    public function testGetBirthDateReturnsDateTimeWhenSet()
    {
        $identity = new \Didww\Item\Identity(['birth_date' => '1990-05-15']);
        $result = $identity->getBirthDate();
        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals('1990-05-15', $result->format('Y-m-d'));
    }

    public function testDeleteIdentity()
    {
        $identity = \Didww\Item\Identity::build('e96ae7d1-11d5-42bc-a5c5-211f3c3788ae');

        $identityDocument = $identity->delete();

        $this->assertFalse($identityDocument->hasErrors());
    }

    public function testIdentitySetters()
    {
        $identity = new \Didww\Item\Identity();

        $identity->setFirstName('Jane');
        $this->assertEquals('Jane', $identity->getFirstName());

        $identity->setLastName('Smith');
        $this->assertEquals('Smith', $identity->getLastName());

        $identity->setPhoneNumber('555-1234');
        $this->assertEquals('555-1234', $identity->getPhoneNumber());

        $identity->setIdNumber('XYZ789');
        $this->assertEquals('XYZ789', $identity->getIdNumber());

        $identity->setBirthDate('1985-06-15');
        $this->assertEquals('1985-06-15', $identity->getBirthDate()->format('Y-m-d'));

        $identity->setCompanyName('Acme Inc');
        $this->assertEquals('Acme Inc', $identity->getCompanyName());

        $identity->setCompanyRegNumber('REG123');
        $this->assertEquals('REG123', $identity->getCompanyRegNumber());

        $identity->setVatId('VAT456');
        $this->assertEquals('VAT456', $identity->getVatId());

        $identity->setDescription('some desc');
        $this->assertEquals('some desc', $identity->getDescription());

        $identity->setPersonalTaxId('TAX789');
        $this->assertEquals('TAX789', $identity->getPersonalTaxId());

        $identity->setIdentityType('Personal');
        $this->assertEquals(\Didww\Enum\IdentityType::PERSONAL, $identity->getIdentityType());

        $identity->setExternalReferenceId('EXT-001');
        $this->assertEquals('EXT-001', $identity->getExternalReferenceId());

        $identity->setContactEmail('john@example.com');
        $this->assertEquals('john@example.com', $identity->getContactEmail());
    }

    public function testNullableGettersReturnNullOnEmptyObject()
    {
        $identity = new \Didww\Item\Identity();
        $this->assertNull($identity->getIdNumber());
        $this->assertNull($identity->getCompanyName());
        $this->assertNull($identity->getCompanyRegNumber());
        $this->assertNull($identity->getVatId());
        $this->assertNull($identity->getDescription());
        $this->assertNull($identity->getPersonalTaxId());
        $this->assertNull($identity->getExternalReferenceId());
        $this->assertNull($identity->getContactEmail());
        $this->assertNull($identity->getBirthDate());
    }

    public function testContactEmailInWhitelist()
    {
        $identity = new \Didww\Item\Identity();
        $identity->setContactEmail('test@example.com');
        $attributes = $identity->getAttributes();
        $this->assertEquals('test@example.com', $attributes['contact_email']);
    }
}
