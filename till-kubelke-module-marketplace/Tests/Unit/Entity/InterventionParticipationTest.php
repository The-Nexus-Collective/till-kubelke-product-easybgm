<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Entity\InterventionParticipation;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;

/**
 * Unit tests for InterventionParticipation entity.
 */
class InterventionParticipationTest extends TestCase
{
    // ========== Status Tests ==========

    public function testDefaultStatusIsRegistered(): void
    {
        $participation = new InterventionParticipation();

        $this->assertEquals(InterventionParticipation::STATUS_REGISTERED, $participation->getStatus());
        $this->assertTrue($participation->isRegistered());
    }

    public function testSetStatus(): void
    {
        $participation = new InterventionParticipation();

        $participation->setStatus(InterventionParticipation::STATUS_ATTENDED);
        $this->assertEquals(InterventionParticipation::STATUS_ATTENDED, $participation->getStatus());
        $this->assertTrue($participation->isAttended());
    }

    public function testSetInvalidStatusThrowsException(): void
    {
        $participation = new InterventionParticipation();

        $this->expectException(\InvalidArgumentException::class);
        $participation->setStatus('invalid_status');
    }

    public function testAllStatusHelpers(): void
    {
        $participation = new InterventionParticipation();

        $this->assertTrue($participation->isRegistered());
        $this->assertFalse($participation->isAttended());
        $this->assertFalse($participation->isNoShow());
        $this->assertFalse($participation->isCancelled());

        $participation->setStatus(InterventionParticipation::STATUS_ATTENDED);
        $this->assertTrue($participation->isAttended());

        $participation->setStatus(InterventionParticipation::STATUS_NO_SHOW);
        $this->assertTrue($participation->isNoShow());

        $participation->setStatus(InterventionParticipation::STATUS_CANCELLED);
        $this->assertTrue($participation->isCancelled());
    }

    // ========== Status Transitions Tests ==========

    public function testMarkAttended(): void
    {
        $participation = new InterventionParticipation();

        $participation->markAttended();

        $this->assertTrue($participation->isAttended());
        $this->assertNotNull($participation->getAttendedAt());
    }

    public function testMarkNoShow(): void
    {
        $participation = new InterventionParticipation();

        $participation->markNoShow();

        $this->assertTrue($participation->isNoShow());
    }

    public function testMarkCancelled(): void
    {
        $participation = new InterventionParticipation();

        $participation->markCancelled();

        $this->assertTrue($participation->isCancelled());
        $this->assertNotNull($participation->getCancelledAt());
    }

    // ========== Intervention Type Tests ==========

    public function testDefaultInterventionType(): void
    {
        $participation = new InterventionParticipation();

        $this->assertEquals(
            InterventionParticipation::TYPE_PARTNER_ENGAGEMENT,
            $participation->getInterventionType()
        );
    }

    public function testSetInterventionType(): void
    {
        $participation = new InterventionParticipation();

        $participation->setInterventionType(InterventionParticipation::TYPE_INTERNAL);

        $this->assertEquals(
            InterventionParticipation::TYPE_INTERNAL,
            $participation->getInterventionType()
        );
    }

    public function testSetEngagementSetsType(): void
    {
        $participation = new InterventionParticipation();
        $participation->setInterventionType(InterventionParticipation::TYPE_INTERNAL);

        $engagement = new PartnerEngagement();
        $participation->setEngagement($engagement);

        $this->assertEquals(
            InterventionParticipation::TYPE_PARTNER_ENGAGEMENT,
            $participation->getInterventionType()
        );
    }

    // ========== Employee Data Tests ==========

    public function testEmployeeData(): void
    {
        $participation = new InterventionParticipation();

        $participation->setEmployeeId(123);
        $participation->setEmployeeEmail('max@example.com');
        $participation->setEmployeeName('Max Mustermann');
        $participation->setDepartment('Marketing');

        $this->assertEquals(123, $participation->getEmployeeId());
        $this->assertEquals('max@example.com', $participation->getEmployeeEmail());
        $this->assertEquals('Max Mustermann', $participation->getEmployeeName());
        $this->assertEquals('Marketing', $participation->getDepartment());
    }

    // ========== Event Details Tests ==========

    public function testEventDate(): void
    {
        $participation = new InterventionParticipation();
        $date = new \DateTime('2025-03-15');

        $participation->setEventDate($date);

        $this->assertEquals($date, $participation->getEventDate());
    }

    public function testCategory(): void
    {
        $participation = new InterventionParticipation();

        $participation->setCategory(InterventionParticipation::CATEGORY_BEWEGUNG);

        $this->assertEquals(InterventionParticipation::CATEGORY_BEWEGUNG, $participation->getCategory());
    }

    public function testInterventionTitle(): void
    {
        $participation = new InterventionParticipation();

        $participation->setInterventionTitle('Rückenfit Workshop');

        $this->assertEquals('Rückenfit Workshop', $participation->getInterventionTitle());
    }

    public function testInterventionDescription(): void
    {
        $participation = new InterventionParticipation();

        $participation->setInterventionDescription('A workshop for back health');

        $this->assertEquals('A workshop for back health', $participation->getInterventionDescription());
    }

    // ========== Feedback Tests ==========

    public function testRating(): void
    {
        $participation = new InterventionParticipation();

        $participation->setRating(4);

        $this->assertEquals(4, $participation->getRating());
    }

    public function testRatingValidation(): void
    {
        $participation = new InterventionParticipation();

        // Valid ratings
        $participation->setRating(1);
        $this->assertEquals(1, $participation->getRating());

        $participation->setRating(5);
        $this->assertEquals(5, $participation->getRating());

        // Invalid rating
        $this->expectException(\InvalidArgumentException::class);
        $participation->setRating(6);
    }

    public function testRatingTooLow(): void
    {
        $participation = new InterventionParticipation();

        $this->expectException(\InvalidArgumentException::class);
        $participation->setRating(0);
    }

    public function testFeedbackComment(): void
    {
        $participation = new InterventionParticipation();

        $participation->setFeedbackComment('Great workshop!');

        $this->assertEquals('Great workshop!', $participation->getFeedbackComment());
    }

    public function testHasFeedback(): void
    {
        $participation = new InterventionParticipation();

        $this->assertFalse($participation->hasFeedback());

        $participation->setRating(5);
        $this->assertTrue($participation->hasFeedback());

        $participation2 = new InterventionParticipation();
        $participation2->setFeedbackComment('Nice!');
        $this->assertTrue($participation2->hasFeedback());
    }

    // ========== Special Requirements Tests ==========

    public function testSpecialRequirements(): void
    {
        $participation = new InterventionParticipation();
        $requirements = ['vegan', 'gluten_free'];

        $participation->setSpecialRequirements($requirements);

        $this->assertEquals($requirements, $participation->getSpecialRequirements());
    }

    // ========== Timestamps Tests ==========

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $participation = new InterventionParticipation();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($participation->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $participation->getCreatedAt());
        $this->assertLessThanOrEqual($after, $participation->getCreatedAt());
    }

    public function testRegisteredAtIsSetOnConstruction(): void
    {
        $participation = new InterventionParticipation();

        $this->assertNotNull($participation->getRegisteredAt());
    }

    // ========== toArray Tests ==========

    public function testToArray(): void
    {
        $participation = new InterventionParticipation();
        $participation->setEmployeeId(123);
        $participation->setEmployeeEmail('max@example.com');
        $participation->setEmployeeName('Max Mustermann');
        $participation->setDepartment('Marketing');
        $participation->setInterventionTitle('Rückenfit');
        $participation->setCategory('bewegung');
        $participation->setRating(5);

        $array = $participation->toArray();

        $this->assertEquals(123, $array['employeeId']);
        $this->assertEquals('max@example.com', $array['employeeEmail']);
        $this->assertEquals('Max Mustermann', $array['employeeName']);
        $this->assertEquals('Marketing', $array['department']);
        $this->assertEquals('Rückenfit', $array['interventionTitle']);
        $this->assertEquals('bewegung', $array['category']);
        $this->assertEquals(5, $array['rating']);
    }

    public function testToAnonymizedArray(): void
    {
        $participation = new InterventionParticipation();
        $participation->setEmployeeId(123);
        $participation->setEmployeeEmail('max@example.com');
        $participation->setEmployeeName('Max Mustermann');
        $participation->setFeedbackComment('Private feedback');
        $participation->setRating(5);
        $participation->setCategory('bewegung');

        $anonymized = $participation->toAnonymizedArray();

        // Should NOT contain personal data
        $this->assertArrayNotHasKey('employeeId', $anonymized);
        $this->assertArrayNotHasKey('employeeEmail', $anonymized);
        $this->assertArrayNotHasKey('employeeName', $anonymized);
        $this->assertArrayNotHasKey('feedbackComment', $anonymized);

        // Should contain anonymous data
        $this->assertEquals('bewegung', $anonymized['category']);
        $this->assertEquals(5, $anonymized['rating']);
        $this->assertTrue($anonymized['hasFeedback']);
    }
}

