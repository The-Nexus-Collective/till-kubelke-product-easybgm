<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Entity\ServiceInquiry;

/**
 * Unit tests for ServiceInquiry entity.
 * 
 * Note: These tests focus on the entity behavior without requiring 
 * the Tenant entity from platform-foundation.
 */
class ServiceInquiryTest extends TestCase
{
    public function testDefaultStatusIsNew(): void
    {
        $inquiry = new ServiceInquiry();

        $this->assertEquals(ServiceInquiry::STATUS_NEW, $inquiry->getStatus());
        $this->assertTrue($inquiry->isNew());
    }

    public function testMarkAsContacted(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->markAsContacted();

        $this->assertEquals(ServiceInquiry::STATUS_CONTACTED, $inquiry->getStatus());
        $this->assertTrue($inquiry->isContacted());
        $this->assertFalse($inquiry->isNew());
        $this->assertNotNull($inquiry->getRespondedAt());
    }

    public function testMarkAsInProgress(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->markAsInProgress();

        $this->assertEquals(ServiceInquiry::STATUS_IN_PROGRESS, $inquiry->getStatus());
        $this->assertTrue($inquiry->isInProgress());
    }

    public function testMarkAsCompleted(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->markAsCompleted();

        $this->assertEquals(ServiceInquiry::STATUS_COMPLETED, $inquiry->getStatus());
        $this->assertTrue($inquiry->isCompleted());
    }

    public function testMarkAsDeclined(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->markAsDeclined();

        $this->assertEquals(ServiceInquiry::STATUS_DECLINED, $inquiry->getStatus());
        $this->assertTrue($inquiry->isDeclined());
    }

    public function testRespondedAtIsOnlySetOnce(): void
    {
        $inquiry = new ServiceInquiry();

        // First status change
        $inquiry->markAsContacted();
        $firstRespondedAt = $inquiry->getRespondedAt();

        // Wait a tiny bit and change again
        usleep(1000);
        $inquiry->markAsInProgress();

        // Should still be the first respondedAt time
        $this->assertEquals($firstRespondedAt, $inquiry->getRespondedAt());
    }

    public function testSetContactInfo(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->setContactName('Max Mustermann');
        $inquiry->setContactEmail('max@example.com');
        $inquiry->setContactPhone('+49 123 456789');

        $this->assertEquals('Max Mustermann', $inquiry->getContactName());
        $this->assertEquals('max@example.com', $inquiry->getContactEmail());
        $this->assertEquals('+49 123 456789', $inquiry->getContactPhone());
    }

    public function testSetMessage(): void
    {
        $inquiry = new ServiceInquiry();
        $message = 'Ich interessiere mich für Ihre Leistungen.';
        $inquiry->setMessage($message);

        $this->assertEquals($message, $inquiry->getMessage());
    }

    public function testSetProviderNotes(): void
    {
        $inquiry = new ServiceInquiry();
        $notes = 'Kunde wurde am 15.12. telefonisch kontaktiert.';
        $inquiry->setProviderNotes($notes);

        $this->assertEquals($notes, $inquiry->getProviderNotes());
    }

    public function testSetMetadata(): void
    {
        $inquiry = new ServiceInquiry();
        $metadata = [
            'source' => 'bgm_phase_4',
            'urgency' => 'high',
        ];
        $inquiry->setMetadata($metadata);

        $this->assertEquals($metadata, $inquiry->getMetadata());
    }

    public function testSetBgmProjectId(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->setBgmProjectId(42);

        $this->assertEquals(42, $inquiry->getBgmProjectId());
    }

    public function testToArray(): void
    {
        $inquiry = new ServiceInquiry();
        $inquiry->setContactName('Max Mustermann');
        $inquiry->setContactEmail('max@example.com');
        $inquiry->setMessage('Test message');
        $inquiry->setBgmProjectId(123);

        $array = $inquiry->toArray();

        $this->assertEquals('Max Mustermann', $array['contactName']);
        $this->assertEquals('max@example.com', $array['contactEmail']);
        $this->assertEquals('Test message', $array['message']);
        $this->assertEquals(123, $array['bgmProjectId']);
        $this->assertEquals(ServiceInquiry::STATUS_NEW, $array['status']);
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $inquiry = new ServiceInquiry();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($inquiry->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $inquiry->getCreatedAt());
        $this->assertLessThanOrEqual($after, $inquiry->getCreatedAt());
    }
}

