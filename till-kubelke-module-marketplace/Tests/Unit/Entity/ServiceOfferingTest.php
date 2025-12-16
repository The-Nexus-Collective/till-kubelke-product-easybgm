<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;

/**
 * Unit tests for ServiceOffering entity.
 */
class ServiceOfferingTest extends TestCase
{
    public function testDefaultDeliveryModeIsOnsite(): void
    {
        $offering = new ServiceOffering();

        $this->assertEquals([ServiceOffering::DELIVERY_ONSITE], $offering->getDeliveryModes());
    }

    public function testSupportsDeliveryModes(): void
    {
        $offering = new ServiceOffering();
        $offering->setDeliveryModes([
            ServiceOffering::DELIVERY_ONSITE,
            ServiceOffering::DELIVERY_REMOTE,
        ]);

        $this->assertTrue($offering->supportsOnsite());
        $this->assertTrue($offering->supportsRemote());
        $this->assertFalse($offering->supportsHybrid());
    }

    public function testSetAllDeliveryModes(): void
    {
        $offering = new ServiceOffering();
        $offering->setDeliveryModes([
            ServiceOffering::DELIVERY_ONSITE,
            ServiceOffering::DELIVERY_REMOTE,
            ServiceOffering::DELIVERY_HYBRID,
        ]);

        $this->assertTrue($offering->supportsOnsite());
        $this->assertTrue($offering->supportsRemote());
        $this->assertTrue($offering->supportsHybrid());
    }

    public function testDefaultIsActiveTrue(): void
    {
        $offering = new ServiceOffering();

        $this->assertTrue($offering->isActive());
    }

    public function testSetIsActive(): void
    {
        $offering = new ServiceOffering();
        $offering->setIsActive(false);

        $this->assertFalse($offering->isActive());
    }

    public function testDefaultIsCertifiedFalse(): void
    {
        $offering = new ServiceOffering();

        $this->assertFalse($offering->isCertified());
    }

    public function testSetCertification(): void
    {
        $offering = new ServiceOffering();
        $offering->setIsCertified(true);
        $offering->setCertificationName('ZPP-zertifiziert');

        $this->assertTrue($offering->isCertified());
        $this->assertEquals('ZPP-zertifiziert', $offering->getCertificationName());
    }

    public function testPricingInfo(): void
    {
        $offering = new ServiceOffering();
        $pricingInfo = [
            'type' => 'fixed',
            'amount' => 500,
            'currency' => 'EUR',
            'note' => 'Pro Teilnehmer',
        ];

        $offering->setPricingInfo($pricingInfo);

        $this->assertEquals($pricingInfo, $offering->getPricingInfo());
    }

    public function testParticipantLimits(): void
    {
        $offering = new ServiceOffering();
        $offering->setMinParticipants(5);
        $offering->setMaxParticipants(20);

        $this->assertEquals(5, $offering->getMinParticipants());
        $this->assertEquals(20, $offering->getMaxParticipants());
    }

    public function testToArray(): void
    {
        $offering = new ServiceOffering();
        $offering->setTitle('Rückenfit Workshop');
        $offering->setDescription('Ein Workshop für Rückengesundheit');
        $offering->setDeliveryModes([ServiceOffering::DELIVERY_ONSITE, ServiceOffering::DELIVERY_REMOTE]);
        $offering->setDuration('2 Stunden');
        $offering->setIsCertified(true);
        $offering->setMinParticipants(10);
        $offering->setMaxParticipants(30);

        $array = $offering->toArray();

        $this->assertEquals('Rückenfit Workshop', $array['title']);
        $this->assertEquals('Ein Workshop für Rückengesundheit', $array['description']);
        $this->assertContains('onsite', $array['deliveryModes']);
        $this->assertContains('remote', $array['deliveryModes']);
        $this->assertEquals('2 Stunden', $array['duration']);
        $this->assertTrue($array['isCertified']);
        $this->assertEquals(10, $array['minParticipants']);
        $this->assertEquals(30, $array['maxParticipants']);
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $offering = new ServiceOffering();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($offering->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $offering->getCreatedAt());
        $this->assertLessThanOrEqual($after, $offering->getCreatedAt());
    }

    public function testSortOrder(): void
    {
        $offering = new ServiceOffering();

        $this->assertEquals(0, $offering->getSortOrder());

        $offering->setSortOrder(5);
        $this->assertEquals(5, $offering->getSortOrder());
    }
}


