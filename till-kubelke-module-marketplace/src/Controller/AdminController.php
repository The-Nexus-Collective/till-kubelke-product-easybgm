<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Service\ProviderService;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Tenant\Controller\AbstractTenantController;

/**
 * Admin controller for managing service provider approvals.
 * Requires system admin authentication.
 */
#[Route('/api/admin/marketplace', name: 'api_admin_marketplace_')]
class AdminController extends AbstractTenantController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly ProviderService $providerService,
    ) {
    }

    /**
     * List all pending providers for approval.
     */
    #[Route('/providers/pending', name: 'pending_providers', methods: ['GET'])]
    public function listPendingProviders(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is system admin (has platform-level admin role)
        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $providers = $this->providerRepository->findPendingProviders($limit, $offset);
        $total = $this->providerRepository->countPendingProviders();

        return new JsonResponse([
            'providers' => array_map(fn(ServiceProvider $p) => $p->toArray(includeDetails: true), $providers),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    /**
     * List all providers with optional status filter.
     */
    #[Route('/providers', name: 'list_providers', methods: ['GET'])]
    public function listAllProviders(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $status = $request->query->get('status');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $criteria = [];
        if ($status !== null && in_array($status, [ServiceProvider::STATUS_PENDING, ServiceProvider::STATUS_APPROVED, ServiceProvider::STATUS_REJECTED])) {
            $criteria['status'] = $status;
        }

        $providers = $this->providerRepository->findBy($criteria, ['createdAt' => 'DESC'], $limit, $offset);
        $total = $this->providerRepository->count($criteria);

        return new JsonResponse([
            'providers' => array_map(fn(ServiceProvider $p) => $p->toArray(includeDetails: true), $providers),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Create a new service provider (admin only).
     */
    #[Route('/providers', name: 'create_provider', methods: ['POST'])]
    public function createProvider(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Ungültige Daten.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        $requiredFields = ['companyName', 'contactEmail', 'description'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Feld '$field' ist erforderlich."], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate email
        if (!filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate description length
        if (strlen($data['description']) < 50) {
            return new JsonResponse(['error' => 'Die Beschreibung muss mindestens 50 Zeichen lang sein.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Set status to approved if not specified (admin-created providers are auto-approved)
            if (!isset($data['status'])) {
                $data['status'] = ServiceProvider::STATUS_APPROVED;
            }

            // Create provider using the service
            $provider = $this->providerService->register($data, null);

            // If status should be approved, approve it
            if ($data['status'] === ServiceProvider::STATUS_APPROVED && !$provider->isApproved()) {
                $provider = $this->providerService->approve($provider);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Dienstleister wurde erstellt.',
                'provider' => $provider->toArray(includeDetails: true),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erstellung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get provider details for review.
     */
    #[Route('/providers/{id}', name: 'get_provider', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProvider(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'provider' => $provider->toArray(includeDetails: true),
        ]);
    }

    /**
     * Update a service provider (admin only).
     */
    #[Route('/providers/{id}', name: 'update_provider', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function updateProvider(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Ungültige Daten.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate email if provided
        if (isset($data['contactEmail']) && !filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.'], Response::HTTP_BAD_REQUEST);
        }

        // Validate description length if provided
        if (isset($data['description']) && strlen($data['description']) < 50) {
            return new JsonResponse(['error' => 'Die Beschreibung muss mindestens 50 Zeichen lang sein.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Update provider using the service
            $provider = $this->providerService->update($provider, $data);

            return new JsonResponse([
                'success' => true,
                'message' => 'Dienstleister wurde aktualisiert.',
                'provider' => $provider->toArray(includeDetails: true),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Aktualisierung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approve a pending provider.
     */
    #[Route('/providers/{id}/approve', name: 'approve_provider', methods: ['POST', 'PATCH'], requirements: ['id' => '\d+'])]
    public function approveProvider(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        if ($provider->isApproved()) {
            return new JsonResponse(['error' => 'Dienstleister ist bereits freigeschaltet.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $provider = $this->providerService->approve($provider);

            // TODO: Send approval email to provider

            return new JsonResponse([
                'success' => true,
                'message' => 'Dienstleister wurde freigeschaltet.',
                'provider' => $provider->toArray(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Freischaltung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reject a pending provider.
     */
    #[Route('/providers/{id}/reject', name: 'reject_provider', methods: ['POST', 'PATCH'], requirements: ['id' => '\d+'])]
    public function rejectProvider(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? '';

        if (empty($reason)) {
            return new JsonResponse(['error' => 'Bitte geben Sie einen Ablehnungsgrund an.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $provider = $this->providerService->reject($provider, $reason);

            // TODO: Send rejection email to provider with reason

            return new JsonResponse([
                'success' => true,
                'message' => 'Dienstleister wurde abgelehnt.',
                'provider' => $provider->toArray(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Ablehnung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a provider.
     */
    #[Route('/providers/{id}', name: 'delete_provider', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteProvider(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($provider);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Dienstleister wurde gelöscht.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Löschen fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get statistics for the admin dashboard.
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSystemAdmin($user)) {
            return new JsonResponse(['error' => 'Keine Berechtigung.'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'stats' => [
                'pending' => $this->providerRepository->count(['status' => ServiceProvider::STATUS_PENDING]),
                'approved' => $this->providerRepository->count(['status' => ServiceProvider::STATUS_APPROVED]),
                'rejected' => $this->providerRepository->count(['status' => ServiceProvider::STATUS_REJECTED]),
                'total' => $this->providerRepository->count([]),
            ],
        ]);
    }

    /**
     * Check if user is a system admin.
     * In a real implementation, this would check for platform-level admin roles.
     */
    private function isSystemAdmin(User $user): bool
    {
        // Check if user has ROLE_ADMIN or ROLE_SUPER_ADMIN in their Symfony roles
        $roles = $user->getRoles();
        return in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_SUPER_ADMIN', $roles, true) || $user->isSuperAdmin();
    }
}

