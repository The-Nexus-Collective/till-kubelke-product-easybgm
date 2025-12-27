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

    // ========== Partner Integration Field Tests ==========

    public function testRequiredDataScopes(): void
    {
        $offering = new ServiceOffering();
        $scopes = ['employee_list', 'goals', 'survey_results'];

        $offering->setRequiredDataScopes($scopes);

        $this->assertEquals($scopes, $offering->getRequiredDataScopes());
    }

    public function testRequiresDataScope(): void
    {
        $offering = new ServiceOffering();
        $offering->setRequiredDataScopes(['employee_list', 'goals']);

        $this->assertTrue($offering->requiresDataScope('employee_list'));
        $this->assertTrue($offering->requiresDataScope('goals'));
        $this->assertFalse($offering->requiresDataScope('survey_results'));
    }

    public function testRequiresDataScopeWithNullScopes(): void
    {
        $offering = new ServiceOffering();

        $this->assertFalse($offering->requiresDataScope('employee_list'));
    }

    public function testOutputDataTypes(): void
    {
        $offering = new ServiceOffering();
        $types = ['copsoq_analysis', 'intervention_plan'];

        $offering->setOutputDataTypes($types);

        $this->assertEquals($types, $offering->getOutputDataTypes());
    }

    public function testDeliversOutputType(): void
    {
        $offering = new ServiceOffering();
        $offering->setOutputDataTypes(['copsoq_analysis', 'health_report']);

        $this->assertTrue($offering->deliversOutputType('copsoq_analysis'));
        $this->assertTrue($offering->deliversOutputType('health_report'));
        $this->assertFalse($offering->deliversOutputType('intervention_plan'));
    }

    public function testDeliversOutputTypeWithNullTypes(): void
    {
        $offering = new ServiceOffering();

        $this->assertFalse($offering->deliversOutputType('copsoq_analysis'));
    }

    public function testIntegrationPoints(): void
    {
        $offering = new ServiceOffering();
        $points = ['phase_2.analysis', 'kpi.custom'];

        $offering->setIntegrationPoints($points);

        $this->assertEquals($points, $offering->getIntegrationPoints());
    }

    public function testIntegratesAt(): void
    {
        $offering = new ServiceOffering();
        $offering->setIntegrationPoints(['phase_2.analysis', 'kpi.custom']);

        $this->assertTrue($offering->integratesAt('phase_2.analysis'));
        $this->assertTrue($offering->integratesAt('kpi.custom'));
        $this->assertFalse($offering->integratesAt('phase_3.concept'));
    }

    public function testIntegratesAtWithNullPoints(): void
    {
        $offering = new ServiceOffering();

        $this->assertFalse($offering->integratesAt('phase_2.analysis'));
    }

    public function testRelevantPhases(): void
    {
        $offering = new ServiceOffering();
        $phases = [2, 3, 4];

        $offering->setRelevantPhases($phases);

        $this->assertEquals($phases, $offering->getRelevantPhases());
    }

    public function testIsRelevantForPhase(): void
    {
        $offering = new ServiceOffering();
        $offering->setRelevantPhases([2, 3, 4]);

        $this->assertFalse($offering->isRelevantForPhase(1));
        $this->assertTrue($offering->isRelevantForPhase(2));
        $this->assertTrue($offering->isRelevantForPhase(3));
        $this->assertTrue($offering->isRelevantForPhase(4));
        $this->assertFalse($offering->isRelevantForPhase(5));
        $this->assertFalse($offering->isRelevantForPhase(6));
    }

    public function testIsRelevantForPhaseWithNullPhases(): void
    {
        $offering = new ServiceOffering();

        $this->assertFalse($offering->isRelevantForPhase(2));
    }

    public function testDefaultIsOrchestratorServiceFalse(): void
    {
        $offering = new ServiceOffering();

        $this->assertFalse($offering->isOrchestratorService());
    }

    public function testSetIsOrchestratorService(): void
    {
        $offering = new ServiceOffering();
        $offering->setIsOrchestratorService(true);

        $this->assertTrue($offering->isOrchestratorService());
    }

    public function testToArrayIncludesPartnerIntegrationFields(): void
    {
        $offering = new ServiceOffering();
        $offering->setTitle('COPSOQ Survey');
        $offering->setDescription('Full COPSOQ analysis');
        $offering->setRequiredDataScopes(['employee_emails', 'goals']);
        $offering->setOutputDataTypes(['copsoq_analysis']);
        $offering->setIntegrationPoints(['phase_2.analysis']);
        $offering->setRelevantPhases([2, 3]);
        $offering->setIsOrchestratorService(false);

        $array = $offering->toArray();

        $this->assertArrayHasKey('requiredDataScopes', $array);
        $this->assertArrayHasKey('outputDataTypes', $array);
        $this->assertArrayHasKey('integrationPoints', $array);
        $this->assertArrayHasKey('relevantPhases', $array);
        $this->assertArrayHasKey('isOrchestratorService', $array);

        $this->assertEquals(['employee_emails', 'goals'], $array['requiredDataScopes']);
        $this->assertEquals(['copsoq_analysis'], $array['outputDataTypes']);
        $this->assertEquals(['phase_2.analysis'], $array['integrationPoints']);
        $this->assertEquals([2, 3], $array['relevantPhases']);
        $this->assertFalse($array['isOrchestratorService']);
    }
}









