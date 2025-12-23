<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use TillKubelke\ModuleMarketplace\EventSubscriber\TenantSecuritySubscriber;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Tenant\Entity\UserTenant;

/**
 * Tests for TenantSecuritySubscriber.
 * 
 * SECURITY TESTS: These tests ensure tenant ID spoofing is blocked.
 */
class TenantSecuritySubscriberTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private TokenStorageInterface&MockObject $tokenStorage;
    private LoggerInterface&MockObject $logger;
    private TenantSecuritySubscriber $subscriber;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new TenantSecuritySubscriber(
            $this->entityManager,
            $this->tokenStorage,
            $this->logger,
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = TenantSecuritySubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
    }

    public function testAllowsRequestWithoutTenantHeader(): void
    {
        $request = Request::create('/api/marketplace/engagements');
        $event = $this->createRequestEvent($request);

        // No tenant header - should pass through
        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testAllowsExemptRoutes(): void
    {
        $exemptPaths = [
            '/api/auth/sign-in',
            '/api/marketplace/catalog',
            '/api/marketplace/reviews/provider/123',
            '/_profiler/abcd1234',
        ];

        foreach ($exemptPaths as $path) {
            $request = Request::create($path);
            $request->headers->set('X-Tenant-ID', '999');
            $event = $this->createRequestEvent($request);

            $this->subscriber->onKernelRequest($event);

            $this->assertNull(
                $event->getResponse(),
                "Route {$path} should be exempt from tenant validation"
            );
        }
    }

    public function testBlocksUnauthorizedTenantAccess(): void
    {
        $user = $this->createMockUser(userId: 1, isSuperAdmin: false);
        $this->setupAuthenticatedUser($user);
        $this->setupNoTenantMembership();

        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', '999');
        $event = $this->createRequestEvent($request);

        // Expect security warning to be logged
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Tenant ID spoofing attempt blocked',
                $this->callback(function (array $context) {
                    return $context['user_id'] === 1
                        && $context['attempted_tenant_id'] === 999
                        && $context['security_event'] === 'TENANT_SPOOFING_ATTEMPT';
                })
            );

        $this->subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response, 'Unauthorized tenant access should be blocked');
        $this->assertEquals(403, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('TENANT_ACCESS_DENIED', $content['code']);
    }

    public function testAllowsSuperAdminToAccessAnyTenant(): void
    {
        $user = $this->createMockUser(userId: 1, isSuperAdmin: true);
        $this->setupAuthenticatedUser($user);
        // No need to check membership for super admin

        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', '999');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse(), 'Super admin should have access to any tenant');
    }

    public function testAllowsUserWithValidTenantMembership(): void
    {
        $user = $this->createMockUser(userId: 1, isSuperAdmin: false);
        $this->setupAuthenticatedUser($user);
        $this->setupTenantMembership(userId: 1, tenantId: 42);

        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', '42');
        $event = $this->createRequestEvent($request);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse(), 'User with valid membership should have access');
    }

    public function testIgnoresNonMainRequests(): void
    {
        $request = Request::create('/api/marketplace/engagements');
        $request->headers->set('X-Tenant-ID', '999');
        
        // Sub-request (not main)
        $event = $this->createRequestEvent($request, isMainRequest: false);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    // ========== Helper Methods ==========

    private function createRequestEvent(Request $request, bool $isMainRequest = true): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        return new RequestEvent(
            $kernel,
            $request,
            $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST
        );
    }

    private function createMockUser(int $userId, bool $isSuperAdmin): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('isSuperAdmin')->willReturn($isSuperAdmin);
        
        return $user;
    }

    private function setupAuthenticatedUser(User $user): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        
        $this->tokenStorage->method('getToken')->willReturn($token);
    }

    private function setupNoTenantMembership(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);
        
        $this->entityManager->method('getRepository')
            ->with(UserTenant::class)
            ->willReturn($repo);
    }

    private function setupTenantMembership(int $userId, int $tenantId): void
    {
        $userTenant = $this->createMock(UserTenant::class);
        
        $repo = $this->createMock(EntityRepository::class);
        // Use a callback to match the criteria array since the user object varies
        $repo->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($tenantId, $userTenant) {
                // Verify the tenant ID matches and user is present
                if (isset($criteria['tenant']) && $criteria['tenant'] === $tenantId && isset($criteria['user'])) {
                    return $userTenant;
                }
                return null;
            });
        
        $this->entityManager->method('getRepository')
            ->with(UserTenant::class)
            ->willReturn($repo);
    }
}

