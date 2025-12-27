<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;

/**
 * Unit tests for PartnerEngagement entity.
 */
class PartnerEngagementTest extends TestCase
{
    // ========== Status Tests ==========

    public function testDefaultStatusIsDraft(): void
    {
        $engagement = new PartnerEngagement();

        $this->assertEquals(PartnerEngagement::STATUS_DRAFT, $engagement->getStatus());
        $this->assertTrue($engagement->isDraft());
    }

    public function testSetStatus(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->setStatus(PartnerEngagement::STATUS_ACTIVE);
        $this->assertEquals(PartnerEngagement::STATUS_ACTIVE, $engagement->getStatus());
        $this->assertTrue($engagement->isActive());
    }

    public function testSetInvalidStatusThrowsException(): void
    {
        $engagement = new PartnerEngagement();

        $this->expectException(\InvalidArgumentException::class);
        $engagement->setStatus('invalid_status');
    }

    public function testAllStatusHelpers(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->setStatus(PartnerEngagement::STATUS_DRAFT);
        $this->assertTrue($engagement->isDraft());
        $this->assertFalse($engagement->isActive());

        $engagement->setStatus(PartnerEngagement::STATUS_ACTIVE);
        $this->assertTrue($engagement->isActive());
        $this->assertFalse($engagement->isDraft());

        $engagement->setStatus(PartnerEngagement::STATUS_DATA_SHARED);
        $this->assertTrue($engagement->isDataShared());

        $engagement->setStatus(PartnerEngagement::STATUS_PROCESSING);
        $this->assertTrue($engagement->isProcessing());

        $engagement->setStatus(PartnerEngagement::STATUS_DELIVERED);
        $this->assertTrue($engagement->isDelivered());

        $engagement->setStatus(PartnerEngagement::STATUS_COMPLETED);
        $this->assertTrue($engagement->isCompleted());

        $engagement->setStatus(PartnerEngagement::STATUS_CANCELLED);
        $this->assertTrue($engagement->isCancelled());
    }

    // ========== Status Transition Tests ==========

    public function testActivate(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->activate();

        $this->assertTrue($engagement->isActive());
        $this->assertNotNull($engagement->getActivatedAt());
    }

    public function testMarkDataShared(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->markDataShared();

        $this->assertTrue($engagement->isDataShared());
    }

    public function testMarkProcessing(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->markProcessing();

        $this->assertTrue($engagement->isProcessing());
    }

    public function testMarkDelivered(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->markDelivered();

        $this->assertTrue($engagement->isDelivered());
    }

    public function testComplete(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->complete();

        $this->assertTrue($engagement->isCompleted());
        $this->assertNotNull($engagement->getCompletedAt());
    }

    public function testCancel(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->cancel();

        $this->assertTrue($engagement->isCancelled());
        $this->assertNotNull($engagement->getCancelledAt());
    }

    public function testIsOngoing(): void
    {
        $engagement = new PartnerEngagement();

        // Draft is ongoing
        $this->assertTrue($engagement->isOngoing());

        // Active is ongoing
        $engagement->activate();
        $this->assertTrue($engagement->isOngoing());

        // Completed is NOT ongoing
        $engagement->complete();
        $this->assertFalse($engagement->isOngoing());

        // Cancelled is NOT ongoing
        $engagement2 = new PartnerEngagement();
        $engagement2->cancel();
        $this->assertFalse($engagement2->isOngoing());
    }

    // ========== Data Scope Tests ==========

    public function testGrantDataScope(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->grantDataScope('employee_list');
        $engagement->grantDataScope('goals');

        $this->assertTrue($engagement->hasGrantedScope('employee_list'));
        $this->assertTrue($engagement->hasGrantedScope('goals'));
        $this->assertFalse($engagement->hasGrantedScope('survey_results'));
    }

    public function testGetGrantedScopeKeys(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->grantDataScope('employee_list');
        $engagement->grantDataScope('goals');

        $keys = $engagement->getGrantedScopeKeys();

        $this->assertContains('employee_list', $keys);
        $this->assertContains('goals', $keys);
        $this->assertCount(2, $keys);
    }

    public function testRevokeDataScope(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->grantDataScope('employee_list');

        $this->assertTrue($engagement->hasGrantedScope('employee_list'));

        $engagement->revokeDataScope('employee_list');

        $this->assertFalse($engagement->hasGrantedScope('employee_list'));
    }

    public function testGetGrantedScopeKeysExcludesRevoked(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->grantDataScope('employee_list');
        $engagement->grantDataScope('goals');
        $engagement->revokeDataScope('employee_list');

        $keys = $engagement->getGrantedScopeKeys();

        $this->assertNotContains('employee_list', $keys);
        $this->assertContains('goals', $keys);
        $this->assertCount(1, $keys);
    }

    public function testGrantedDataScopesStructure(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->grantDataScope('employee_list');

        $scopes = $engagement->getGrantedDataScopes();

        $this->assertArrayHasKey('employee_list', $scopes);
        $this->assertArrayHasKey('granted_at', $scopes['employee_list']);
        $this->assertArrayHasKey('status', $scopes['employee_list']);
        $this->assertEquals('granted', $scopes['employee_list']['status']);
    }

    // ========== Deliverables Tests ==========

    public function testAddDeliveredOutput(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->addDeliveredOutput('copsoq_analysis', [
            'file_url' => '/uploads/copsoq_result.pdf',
            'summary' => 'Analysis complete',
        ]);

        $this->assertTrue($engagement->hasDeliveredOutput('copsoq_analysis'));
        $this->assertFalse($engagement->hasDeliveredOutput('health_report'));
    }

    public function testDeliveredOutputsStructure(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->addDeliveredOutput('copsoq_analysis', ['summary' => 'Done']);

        $outputs = $engagement->getDeliveredOutputs();

        $this->assertArrayHasKey('copsoq_analysis', $outputs);
        $this->assertArrayHasKey('delivered_at', $outputs['copsoq_analysis']);
        $this->assertArrayHasKey('data', $outputs['copsoq_analysis']);
        $this->assertEquals(['summary' => 'Done'], $outputs['copsoq_analysis']['data']);
    }

    public function testMarkOutputIntegrated(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->addDeliveredOutput('copsoq_analysis', ['summary' => 'Done']);

        $engagement->markOutputIntegrated('copsoq_analysis', 'phase_2.analysis');

        $status = $engagement->getIntegrationStatus();

        $this->assertArrayHasKey('copsoq_analysis', $status);
        $this->assertArrayHasKey('integrated_at', $status['copsoq_analysis']);
        $this->assertEquals('phase_2.analysis', $status['copsoq_analysis']['integration_point']);
    }

    // ========== Partner Contact Tests ==========

    public function testPartnerContact(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->setPartnerContactName('Max Mustermann');
        $engagement->setPartnerContactEmail('max@example.com');
        $engagement->setPartnerContactPhone('+49 123 456789');

        $this->assertEquals('Max Mustermann', $engagement->getPartnerContactName());
        $this->assertEquals('max@example.com', $engagement->getPartnerContactEmail());
        $this->assertEquals('+49 123 456789', $engagement->getPartnerContactPhone());
    }

    // ========== Notes Tests ==========

    public function testCustomerNotes(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->setCustomerNotes('Internal note about this engagement');

        $this->assertEquals('Internal note about this engagement', $engagement->getCustomerNotes());
    }

    public function testPartnerNotes(): void
    {
        $engagement = new PartnerEngagement();

        $engagement->setPartnerNotes('Note from partner');

        $this->assertEquals('Note from partner', $engagement->getPartnerNotes());
    }

    // ========== Pricing & Scheduling Tests ==========

    public function testAgreedPricing(): void
    {
        $engagement = new PartnerEngagement();
        $pricing = [
            'amount' => 1500,
            'currency' => 'EUR',
            'type' => 'fixed',
        ];

        $engagement->setAgreedPricing($pricing);

        $this->assertEquals($pricing, $engagement->getAgreedPricing());
    }

    public function testScheduledDate(): void
    {
        $engagement = new PartnerEngagement();
        $date = new \DateTime('2025-03-15');

        $engagement->setScheduledDate($date);

        $this->assertEquals($date, $engagement->getScheduledDate());
    }

    // ========== Timestamps Tests ==========

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $engagement = new PartnerEngagement();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($engagement->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $engagement->getCreatedAt());
        $this->assertLessThanOrEqual($after, $engagement->getCreatedAt());
    }

    // ========== toArray Tests ==========

    public function testToArray(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->setStatus(PartnerEngagement::STATUS_ACTIVE);
        $engagement->grantDataScope('goals');
        $engagement->addDeliveredOutput('copsoq_analysis', ['done' => true]);
        $engagement->setPartnerContactName('Max Mustermann');
        $engagement->setPartnerContactEmail('max@example.com');

        $array = $engagement->toArray();

        $this->assertEquals(PartnerEngagement::STATUS_ACTIVE, $array['status']);
        $this->assertContains('goals', $array['grantedDataScopes']);
        $this->assertContains('copsoq_analysis', $array['deliveredOutputs']);
        $this->assertEquals('Max Mustermann', $array['partnerContact']['name']);
        $this->assertEquals('max@example.com', $array['partnerContact']['email']);
    }

    public function testToArrayWithNotes(): void
    {
        $engagement = new PartnerEngagement();
        $engagement->setCustomerNotes('Internal note');
        $engagement->setPartnerNotes('Partner note');

        $arrayWithoutNotes = $engagement->toArray(includeNotes: false);
        $arrayWithNotes = $engagement->toArray(includeNotes: true);

        $this->assertArrayNotHasKey('customerNotes', $arrayWithoutNotes);
        $this->assertArrayNotHasKey('partnerNotes', $arrayWithoutNotes);

        $this->assertArrayHasKey('customerNotes', $arrayWithNotes);
        $this->assertArrayHasKey('partnerNotes', $arrayWithNotes);
        $this->assertEquals('Internal note', $arrayWithNotes['customerNotes']);
        $this->assertEquals('Partner note', $arrayWithNotes['partnerNotes']);
    }
}







