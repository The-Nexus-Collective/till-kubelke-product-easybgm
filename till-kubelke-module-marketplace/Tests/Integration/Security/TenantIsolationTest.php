<?php

namespace TillKubelke\ModuleMarketplace\Tests\Integration\Security;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;
use TillKubelke\PlatformFoundation\Tenant\Entity\UserTenant;
use TillKubelke\ModuleMarketplace\EventSubscriber\TenantSecuritySubscriber;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Psr\Log\LoggerInterface;

/**
 * SECURITY INTEGRATION TESTS: Tenant Isolation
 * 
 * These tests verify that cross-tenant data access is completely blocked.
 * This is a CRITICAL security test suite that must pass before any deployment.
 */
#[Group('security')]
#[Group('tenant-isolation')]
class TenantIsolationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->tokenStorage = $container->get(TokenStorageInterface::class);
        $this->logger = $container->get(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clear any test data
        $this->entityManager->clear();
    }

    // ==================== CRITICAL: ID Spoofing Prevention ====================

    /**
     * SECURITY TEST: User cannot access tenant they don't belong to.
     * 
     * This test simulates an attacker trying to access another tenant's data
     * by manipulating the X-Tenant-ID header.
     */
    public function testUserCannotAccessUnauthorizedTenant(): void
    {
        // Create test data
        $user = $this->createTestUser('attacker@test.com');
        $ownTenant = $this->createTestTenant('AttackerTenant');
        $targetTenant = $this->createTestTenant('VictimTenant');
        
        // User belongs ONLY to their own tenant
        $this->createUserTenantMembership($user, $ownTenant);
        
        // Authenticate user
        $this->authenticateUser($user);
        
        // Create subscriber and request
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        // Attempt to access VICTIM tenant (ID spoofing attack)
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', (string) $targetTenant->getId());
        
        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
        
        // MUST be blocked
        $this->assertNotNull($event->getResponse(), 'Cross-tenant access MUST be blocked');
        $this->assertEquals(
            Response::HTTP_FORBIDDEN, 
            $event->getResponse()->getStatusCode(),
            'Cross-tenant access must return 403 Forbidden'
        );
        
        // Verify security event logged
        $content = json_decode($event->getResponse()->getContent(), true);
        $this->assertEquals('TENANT_ACCESS_DENIED', $content['code']);
    }

    /**
     * SECURITY TEST: User CAN access their own tenant.
     */
    public function testUserCanAccessOwnTenant(): void
    {
        $user = $this->createTestUser('legitimate@test.com');
        $ownTenant = $this->createTestTenant('LegitTenant');
        
        $this->createUserTenantMembership($user, $ownTenant);
        $this->authenticateUser($user);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', (string) $ownTenant->getId());
        
        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
        
        $this->assertNull(
            $event->getResponse(), 
            'User MUST be able to access their own tenant'
        );
    }

    /**
     * SECURITY TEST: User with multiple tenants can access each.
     */
    public function testUserWithMultipleTenantsCanAccessEach(): void
    {
        $user = $this->createTestUser('multiTenant@test.com');
        $tenant1 = $this->createTestTenant('Tenant1');
        $tenant2 = $this->createTestTenant('Tenant2');
        $tenant3 = $this->createTestTenant('Tenant3');
        $unauthorizedTenant = $this->createTestTenant('Unauthorized');
        
        // User belongs to tenants 1, 2, 3 but NOT "unauthorizedTenant"
        $this->createUserTenantMembership($user, $tenant1);
        $this->createUserTenantMembership($user, $tenant2);
        $this->createUserTenantMembership($user, $tenant3);
        
        $this->authenticateUser($user);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        // Should be able to access tenant1
        $request1 = Request::create('/api/marketplace/engagements');
        $request1->headers->set('X-Tenant-ID', (string) $tenant1->getId());
        $event1 = $this->createRequestEvent($request1);
        $subscriber->onKernelRequest($event1);
        $this->assertNull($event1->getResponse(), 'Should access tenant1');
        
        // Should be able to access tenant2
        $request2 = Request::create('/api/marketplace/engagements');
        $request2->headers->set('X-Tenant-ID', (string) $tenant2->getId());
        $event2 = $this->createRequestEvent($request2);
        $subscriber->onKernelRequest($event2);
        $this->assertNull($event2->getResponse(), 'Should access tenant2');
        
        // Should be able to access tenant3
        $request3 = Request::create('/api/marketplace/engagements');
        $request3->headers->set('X-Tenant-ID', (string) $tenant3->getId());
        $event3 = $this->createRequestEvent($request3);
        $subscriber->onKernelRequest($event3);
        $this->assertNull($event3->getResponse(), 'Should access tenant3');
        
        // Should NOT be able to access unauthorizedTenant
        $request4 = Request::create('/api/marketplace/engagements');
        $request4->headers->set('X-Tenant-ID', (string) $unauthorizedTenant->getId());
        $event4 = $this->createRequestEvent($request4);
        $subscriber->onKernelRequest($event4);
        $this->assertNotNull($event4->getResponse(), 'Should NOT access unauthorized tenant');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event4->getResponse()->getStatusCode());
    }

    /**
     * SECURITY TEST: Super-Admin can access any tenant.
     */
    public function testSuperAdminCanAccessAnyTenant(): void
    {
        $superAdmin = $this->createTestUser('superadmin@test.com', ['ROLE_SUPER_ADMIN']);
        $anyTenant = $this->createTestTenant('AnyTenant');
        
        // Super-admin has NO membership in this tenant
        $this->authenticateUser($superAdmin);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', (string) $anyTenant->getId());
        
        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
        
        $this->assertNull(
            $event->getResponse(), 
            'Super-Admin MUST be able to access any tenant'
        );
    }

    // ==================== Edge Cases ====================

    /**
     * SECURITY TEST: Invalid tenant ID is rejected.
     */
    public function testInvalidTenantIdIsRejected(): void
    {
        $user = $this->createTestUser('user@test.com');
        $this->authenticateUser($user);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', '999999999'); // Non-existent tenant
        
        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
        
        // Invalid tenant access should be blocked
        $this->assertNotNull($event->getResponse());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    /**
     * SECURITY TEST: Negative tenant ID is rejected.
     */
    public function testNegativeTenantIdIsRejected(): void
    {
        $user = $this->createTestUser('user@test.com');
        $this->authenticateUser($user);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', '-1');
        
        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
        
        $this->assertNotNull($event->getResponse());
    }

    /**
     * SECURITY TEST: Non-numeric tenant ID is rejected.
     */
    public function testNonNumericTenantIdIsRejected(): void
    {
        $user = $this->createTestUser('user@test.com');
        $this->authenticateUser($user);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', 'invalid; DROP TABLE users;--');
        
        $event = $this->createRequestEvent($request);
        $subscriber->onKernelRequest($event);
        
        $this->assertNotNull($event->getResponse());
    }

    /**
     * SECURITY TEST: SQL Injection in tenant ID is blocked.
     */
    public function testSqlInjectionInTenantIdIsBlocked(): void
    {
        $user = $this->createTestUser('user@test.com');
        $this->authenticateUser($user);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $maliciousInputs = [
            "1 OR 1=1",
            "1; DROP TABLE tenants;--",
            "1 UNION SELECT * FROM users",
            "1' OR '1'='1",
            "-1 OR id > 0",
        ];
        
        foreach ($maliciousInputs as $input) {
            $request = Request::create('/api/marketplace/engagements');
            $request->headers->set('X-Tenant-ID', $input);
            
            $event = $this->createRequestEvent($request);
            $subscriber->onKernelRequest($event);
            
            $this->assertNotNull(
                $event->getResponse(), 
                "SQL injection attempt with '{$input}' should be blocked"
            );
        }
    }

    // ==================== Public Routes ====================

    /**
     * SECURITY TEST: Public routes bypass tenant validation.
     */
    public function testPublicRoutesAreFreelyAccessible(): void
    {
        // No authentication
        $this->tokenStorage->setToken(null);
        
        $subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger
        );
        
        $publicRoutes = [
            '/api/auth/sign-in',
            '/api/auth/sign-up',
            '/api/marketplace/catalog',
            '/api/marketplace/providers',
            '/api/marketplace/reviews/provider/123',
        ];
        
        foreach ($publicRoutes as $route) {
            $request = Request::create($route);
            $event = $this->createRequestEvent($request);
            $subscriber->onKernelRequest($event);
            
            $this->assertNull(
                $event->getResponse(), 
                "Public route {$route} should be accessible without authentication"
            );
        }
    }

    // ==================== Helper Methods ====================

    private function createTestUser(string $email, array $roles = ['ROLE_USER']): User
    {
        // Make email unique per test run to avoid conflicts
        $uniqueEmail = uniqid() . '_' . $email;
        
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword('hashed_password');
        $user->setRoles($roles);
        $user->setFirstName('Test');
        $user->setLastName('User');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createTestTenant(string $name): Tenant
    {
        // Make name unique per test run
        $uniqueName = uniqid() . '_' . $name;
        
        $tenant = new Tenant();
        $tenant->setName($uniqueName);
        
        $this->entityManager->persist($tenant);
        $this->entityManager->flush();
        
        return $tenant;
    }

    private function createUserTenantMembership(User $user, Tenant $tenant, string $role = 'member'): UserTenant
    {
        $userTenant = new UserTenant();
        $userTenant->setUser($user);
        $userTenant->setTenant($tenant);
        $userTenant->setRole($role);
        
        $this->entityManager->persist($userTenant);
        $this->entityManager->flush();
        
        return $userTenant;
    }

    private function authenticateUser(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    private function createRequestEvent(Request $request): \Symfony\Component\HttpKernel\Event\RequestEvent
    {
        $kernel = self::$kernel;
        
        return new \Symfony\Component\HttpKernel\Event\RequestEvent(
            $kernel,
            $request,
            \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST
        );
    }
}

