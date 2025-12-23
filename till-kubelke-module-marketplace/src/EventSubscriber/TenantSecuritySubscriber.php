<?php

namespace TillKubelke\ModuleMarketplace\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;
use TillKubelke\PlatformFoundation\Tenant\Entity\UserTenant;

/**
 * TenantSecuritySubscriber - SECOND LINE OF DEFENSE against tenant ID spoofing.
 * 
 * This subscriber validates EVERY request with an X-Tenant-ID header to ensure
 * the authenticated user actually has access to the requested tenant.
 * 
 * Even if a controller forgets to use AbstractTenantController, this subscriber
 * will catch unauthorized tenant access attempts.
 * 
 * SECURITY CRITICAL:
 * - Runs on EVERY request with X-Tenant-ID header
 * - Blocks requests where user doesn't have tenant membership
 * - Logs attempted violations for audit trail
 * 
 * @see AbstractTenantController for first line of defense at controller level
 * 
 * TODO: Consider moving this to PlatformFoundation for all products to benefit.
 */
class TenantSecuritySubscriber implements EventSubscriberInterface
{
    private const HEADER_TENANT_ID = 'X-Tenant-ID';
    
    /**
     * Routes that are exempt from tenant validation.
     * These are typically public or system routes.
     */
    private const EXEMPT_ROUTE_PREFIXES = [
        '/api/auth/',           // Authentication
        '/api/marketplace/catalog',  // Public catalog
        '/api/marketplace/reviews/provider/',  // Public provider reviews
        '/api/marketplace/reviews/stats',  // Public stats
        '/_profiler',           // Symfony profiler
        '/_wdt',                // Symfony web debug toolbar
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Run early in the request lifecycle, but after authentication
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $tenantId = $request->headers->get(self::HEADER_TENANT_ID);

        // No tenant header = no validation needed
        if ($tenantId === null || $tenantId === '') {
            return;
        }

        // Check if route is exempt
        $path = $request->getPathInfo();
        foreach (self::EXEMPT_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        // Get authenticated user
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            // Not authenticated yet - let other security handle it
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            // Not a valid user - let other security handle it
            return;
        }

        // Validate tenant access
        if (!$this->userHasTenantAccess($user, (int) $tenantId)) {
            $this->logSecurityViolation($user, (int) $tenantId, $path);
            
            $event->setResponse(new JsonResponse(
                [
                    'error' => 'Access to this tenant denied',
                    'code' => 'TENANT_ACCESS_DENIED',
                ],
                Response::HTTP_FORBIDDEN
            ));
        }
    }

    /**
     * Check if user has access to the specified tenant.
     */
    private function userHasTenantAccess(User $user, int $tenantId): bool
    {
        // Super-admins can access any tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check UserTenant relationship
        $userTenant = $this->entityManager->getRepository(UserTenant::class)->findOneBy([
            'user' => $user,
            'tenant' => $tenantId,
        ]);

        return $userTenant !== null;
    }

    /**
     * Log security violation attempt for audit trail.
     */
    private function logSecurityViolation(User $user, int $tenantId, string $path): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->warning('Tenant ID spoofing attempt blocked', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'attempted_tenant_id' => $tenantId,
            'path' => $path,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
            'security_event' => 'TENANT_SPOOFING_ATTEMPT',
        ]);
    }
}

