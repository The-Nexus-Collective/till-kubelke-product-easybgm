<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Entity\Category;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Entity\Tag;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Geo\Entity\Market;

/**
 * Unit tests for ServiceProvider entity.
 */
class ServiceProviderTest extends TestCase
{
    public function testNewProviderHasPendingStatus(): void
    {
        $provider = new ServiceProvider();

        $this->assertEquals(ServiceProvider::STATUS_PENDING, $provider->getStatus());
        $this->assertTrue($provider->isPending());
        $this->assertFalse($provider->isApproved());
        $this->assertFalse($provider->isRejected());
    }

    public function testApproveChangesStatusAndSetsApprovedAt(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName('Test Provider');
        $provider->setContactEmail('test@example.com');
        $provider->setDescription('Test description');

        $provider->approve();

        $this->assertEquals(ServiceProvider::STATUS_APPROVED, $provider->getStatus());
        $this->assertTrue($provider->isApproved());
        $this->assertFalse($provider->isPending());
        $this->assertNotNull($provider->getApprovedAt());
        $this->assertNull($provider->getRejectionReason());
    }

    public function testRejectChangesStatusAndSetsReason(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName('Test Provider');
        $provider->setContactEmail('test@example.com');
        $provider->setDescription('Test description');

        $reason = 'Unvollständige Informationen';
        $provider->reject($reason);

        $this->assertEquals(ServiceProvider::STATUS_REJECTED, $provider->getStatus());
        $this->assertTrue($provider->isRejected());
        $this->assertFalse($provider->isPending());
        $this->assertEquals($reason, $provider->getRejectionReason());
        $this->assertNull($provider->getApprovedAt());
    }

    public function testAddCategory(): void
    {
        $provider = new ServiceProvider();
        $category = new Category();
        $category->setName('Bewegung');
        $category->setSlug('bewegung');

        $provider->addCategory($category);

        $this->assertCount(1, $provider->getCategories());
        $this->assertTrue($provider->getCategories()->contains($category));
    }

    public function testAddCategoryDoesNotDuplicate(): void
    {
        $provider = new ServiceProvider();
        $category = new Category();
        $category->setName('Bewegung');
        $category->setSlug('bewegung');

        $provider->addCategory($category);
        $provider->addCategory($category); // Add again

        $this->assertCount(1, $provider->getCategories());
    }

    public function testRemoveCategory(): void
    {
        $provider = new ServiceProvider();
        $category = new Category();
        $category->setName('Bewegung');
        $category->setSlug('bewegung');

        $provider->addCategory($category);
        $provider->removeCategory($category);

        $this->assertCount(0, $provider->getCategories());
    }

    public function testAddTag(): void
    {
        $provider = new ServiceProvider();
        $tag = new Tag();
        $tag->setName('Coaching');
        $tag->setSlug('coaching');

        $provider->addTag($tag);

        $this->assertCount(1, $provider->getTags());
        $this->assertTrue($provider->getTags()->contains($tag));
    }

    public function testToArrayBasic(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName('Test Provider');
        $provider->setContactEmail('test@example.com');
        $provider->setDescription('Test description');
        $provider->setShortDescription('Short desc');
        $provider->setIsNationwide(true);
        $provider->setOffersRemote(true);

        $array = $provider->toArray();

        $this->assertEquals('Test Provider', $array['companyName']);
        $this->assertEquals('Short desc', $array['shortDescription']);
        $this->assertTrue($array['isNationwide']);
        $this->assertTrue($array['offersRemote']);
        $this->assertEquals(ServiceProvider::STATUS_PENDING, $array['status']);
    }

    public function testToArrayWithDetails(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName('Test Provider');
        $provider->setContactEmail('test@example.com');
        $provider->setDescription('Test description');
        $provider->setContactPhone('+49 123 456789');
        $provider->setContactPerson('Max Mustermann');

        $array = $provider->toArray(includeDetails: true);

        $this->assertArrayHasKey('contactEmail', $array);
        $this->assertArrayHasKey('contactPhone', $array);
        $this->assertArrayHasKey('contactPerson', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertEquals('test@example.com', $array['contactEmail']);
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $provider = new ServiceProvider();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($provider->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $provider->getCreatedAt());
        $this->assertLessThanOrEqual($after, $provider->getCreatedAt());
    }

    public function testSetLocation(): void
    {
        $provider = new ServiceProvider();
        $location = [
            'city' => 'München',
            'region' => 'Bayern',
            'country' => 'Deutschland',
        ];

        $provider->setLocation($location);

        $this->assertEquals($location, $provider->getLocation());
    }

    public function testSetServiceRegions(): void
    {
        $provider = new ServiceProvider();
        $regions = ['Bayern', 'Baden-Württemberg', 'Hessen'];

        $provider->setServiceRegions($regions);

        $this->assertEquals($regions, $provider->getServiceRegions());
    }

    public function testSetOwner(): void
    {
        $provider = new ServiceProvider();
        $user = new User('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        
        // Use reflection to set ID for testing
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 123);

        $provider->setOwner($user);

        $this->assertEquals($user, $provider->getOwner());
        $this->assertTrue($provider->isOwnedBy($user));
    }

    public function testIsOwnedByReturnsFalseWhenNoOwner(): void
    {
        $provider = new ServiceProvider();
        $user = new User('test@example.com');
        
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 123);

        $this->assertFalse($provider->isOwnedBy($user));
        $this->assertFalse($provider->isOwnedBy(null));
    }

    public function testIsOwnedByReturnsFalseForDifferentUser(): void
    {
        $provider = new ServiceProvider();
        
        $owner = new User('owner@example.com');
        $reflection = new \ReflectionClass($owner);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($owner, 123);
        
        $otherUser = new User('other@example.com');
        $idProperty->setValue($otherUser, 456);

        $provider->setOwner($owner);

        $this->assertTrue($provider->isOwnedBy($owner));
        $this->assertFalse($provider->isOwnedBy($otherUser));
    }

    public function testToArrayIncludesOwnerIdWhenDetailsRequested(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName('Test Provider');
        $provider->setContactEmail('test@example.com');
        $provider->setDescription('Test description with at least 50 characters for validation purposes.');

        $user = new User('test@example.com');
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 789);

        $provider->setOwner($user);

        $array = $provider->toArray(includeDetails: true);

        $this->assertArrayHasKey('ownerId', $array);
        $this->assertEquals(789, $array['ownerId']);
    }

    // ========== Market Tests ==========

    public function testAddMarket(): void
    {
        $provider = new ServiceProvider();
        $market = new Market('DE', 'Germany', 'EUR', 'de_DE');

        $provider->addMarket($market);

        $this->assertCount(1, $provider->getMarkets());
        $this->assertTrue($provider->getMarkets()->contains($market));
    }

    public function testAddMarketDoesNotDuplicate(): void
    {
        $provider = new ServiceProvider();
        $market = new Market('DE', 'Germany', 'EUR', 'de_DE');

        $provider->addMarket($market);
        $provider->addMarket($market); // Add again

        $this->assertCount(1, $provider->getMarkets());
    }

    public function testAddMultipleMarkets(): void
    {
        $provider = new ServiceProvider();
        $de = new Market('DE', 'Germany', 'EUR', 'de_DE');
        $at = new Market('AT', 'Austria', 'EUR', 'de_AT');
        $ch = new Market('CH', 'Switzerland', 'CHF', 'de_CH');

        $provider->addMarket($de);
        $provider->addMarket($at);
        $provider->addMarket($ch);

        $this->assertCount(3, $provider->getMarkets());
    }

    public function testRemoveMarket(): void
    {
        $provider = new ServiceProvider();
        $market = new Market('DE', 'Germany', 'EUR', 'de_DE');

        $provider->addMarket($market);
        $provider->removeMarket($market);

        $this->assertCount(0, $provider->getMarkets());
    }

    public function testOperatesInMarket(): void
    {
        $provider = new ServiceProvider();
        $de = new Market('DE', 'Germany', 'EUR', 'de_DE');
        $at = new Market('AT', 'Austria', 'EUR', 'de_AT');

        $provider->addMarket($de);
        $provider->addMarket($at);

        $this->assertTrue($provider->operatesInMarket('DE'));
        $this->assertTrue($provider->operatesInMarket('de')); // Case-insensitive
        $this->assertTrue($provider->operatesInMarket('AT'));
        $this->assertFalse($provider->operatesInMarket('CH'));
    }

    public function testGetMarketCodes(): void
    {
        $provider = new ServiceProvider();
        $de = new Market('DE', 'Germany', 'EUR', 'de_DE');
        $at = new Market('AT', 'Austria', 'EUR', 'de_AT');

        $provider->addMarket($de);
        $provider->addMarket($at);

        $codes = $provider->getMarketCodes();

        $this->assertCount(2, $codes);
        $this->assertContains('DE', $codes);
        $this->assertContains('AT', $codes);
    }

    public function testToArrayIncludesMarkets(): void
    {
        $provider = new ServiceProvider();
        $provider->setCompanyName('Test Provider');
        $provider->setContactEmail('test@example.com');
        $provider->setDescription('Test description with at least 50 characters for validation purposes.');

        $de = new Market('DE', 'Germany', 'EUR', 'de_DE');
        $provider->addMarket($de);

        $array = $provider->toArray();

        $this->assertArrayHasKey('markets', $array);
        $this->assertArrayHasKey('marketCodes', $array);
        $this->assertCount(1, $array['markets']);
        $this->assertContains('DE', $array['marketCodes']);
    }

    public function testNewProviderHasEmptyMarkets(): void
    {
        $provider = new ServiceProvider();

        $this->assertCount(0, $provider->getMarkets());
        $this->assertEmpty($provider->getMarketCodes());
    }
}

