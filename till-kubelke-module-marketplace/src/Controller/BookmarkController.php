<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TillKubelke\ModuleMarketplace\Entity\PartnerBookmark;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Repository\PartnerBookmarkRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\PlatformFoundation\Tenant\Controller\AbstractTenantController;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * Controller for managing partner bookmarks.
 * Allows tenants to manually mark providers as "their partners".
 * 
 * SECURITY: Uses AbstractTenantController for validated tenant access.
 */
#[Route('/api/marketplace/bookmarks', name: 'api_marketplace_bookmarks_')]
#[IsGranted('ROLE_USER')]
class BookmarkController extends AbstractTenantController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PartnerBookmarkRepository $bookmarkRepository,
        private readonly ServiceProviderRepository $providerRepository,
    ) {
    }

    /**
     * Get all bookmarked partners for the current tenant.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $bookmarks = $this->bookmarkRepository->findByTenant($tenant);

        return new JsonResponse([
            'bookmarks' => array_map(fn(PartnerBookmark $b) => $b->toArray(), $bookmarks),
            'providerIds' => array_map(fn(PartnerBookmark $b) => $b->getProvider()->getId(), $bookmarks),
        ]);
    }

    /**
     * Add a provider as partner (bookmark).
     */
    #[Route('/{providerId}', name: 'add', methods: ['POST'], requirements: ['providerId' => '\d+'])]
    public function add(int $providerId, Request $request): JsonResponse
    {
        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $provider = $this->providerRepository->find($providerId);
        if (!$provider) {
            return new JsonResponse(['error' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if already bookmarked
        $existing = $this->bookmarkRepository->findOneByTenantAndProvider($tenant, $providerId);
        if ($existing) {
            return new JsonResponse([
                'message' => 'Provider already bookmarked',
                'bookmark' => $existing->toArray(),
            ]);
        }

        // Get optional note from request body
        $data = json_decode($request->getContent(), true) ?? [];
        $note = $data['note'] ?? null;

        $bookmark = new PartnerBookmark();
        $bookmark->setTenant($tenant);
        $bookmark->setProvider($provider);
        if ($note) {
            $bookmark->setNote($note);
        }

        $this->entityManager->persist($bookmark);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Partner hinzugefügt',
            'bookmark' => $bookmark->toArray(),
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove a provider bookmark.
     */
    #[Route('/{providerId}', name: 'remove', methods: ['DELETE'], requirements: ['providerId' => '\d+'])]
    public function remove(int $providerId, Request $request): JsonResponse
    {
        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $bookmark = $this->bookmarkRepository->findOneByTenantAndProvider($tenant, $providerId);
        if (!$bookmark) {
            return new JsonResponse(['error' => 'Bookmark not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($bookmark);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Partner entfernt',
        ]);
    }

    /**
     * Update note for a bookmark.
     */
    #[Route('/{providerId}', name: 'update', methods: ['PATCH'], requirements: ['providerId' => '\d+'])]
    public function update(int $providerId, Request $request): JsonResponse
    {
        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $bookmark = $this->bookmarkRepository->findOneByTenantAndProvider($tenant, $providerId);
        if (!$bookmark) {
            return new JsonResponse(['error' => 'Bookmark not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        
        if (array_key_exists('note', $data)) {
            $bookmark->setNote($data['note']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'bookmark' => $bookmark->toArray(),
        ]);
    }

    // ========== Security: Validated Tenant Access ==========

    /**
     * Validate tenant from request header with access control.
     * 
     * SECURITY: Uses AbstractTenantController::getValidatedTenant() which
     * checks that the current user has membership in the requested tenant.
     * This prevents ID spoofing attacks.
     */
    private function validateTenant(Request $request): Tenant|JsonResponse
    {
        try {
            $tenant = $this->getValidatedTenant($request, $this->entityManager);
            if (!$tenant) {
                return new JsonResponse(
                    ['error' => 'X-Tenant-ID header is required'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            return $tenant;
        } catch (AccessDeniedException $e) {
            return new JsonResponse(
                ['error' => 'Access to this tenant denied'],
                Response::HTTP_FORBIDDEN
            );
        }
    }
}



