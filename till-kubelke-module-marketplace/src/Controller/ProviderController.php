<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Service\ProviderService;
use TillKubelke\PlatformFoundation\Auth\Entity\User;

/**
 * Controller for service provider registration and management.
 */
#[Route('/api/marketplace/providers', name: 'api_marketplace_providers_')]
class ProviderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly ProviderService $providerService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Register a new service provider (public endpoint).
     * Provider will be in pending status until approved by admin.
     */
    #[Route('', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $requiredFields = ['companyName', 'contactEmail', 'description'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return new JsonResponse([
                    'error' => "Das Feld '{$field}' ist erforderlich.",
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate email
        if (!filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate description length
        if (strlen($data['description']) < 50) {
            return new JsonResponse([
                'error' => 'Die Beschreibung muss mindestens 50 Zeichen lang sein.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate URL fields if provided
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            return new JsonResponse([
                'error' => 'Bitte geben Sie eine gültige Website-URL an.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!empty($data['logoUrl']) && !filter_var($data['logoUrl'], FILTER_VALIDATE_URL)) {
            return new JsonResponse([
                'error' => 'Bitte geben Sie eine gültige Logo-URL an.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Get current user if authenticated (optional)
            $user = $this->getUser();
            $owner = $user instanceof User ? $user : null;

            // If authenticated, add ROLE_PROVIDER to user's roles
            if ($owner !== null) {
                // Ensure user is managed by EntityManager
                if (!$this->entityManager->contains($owner)) {
                    $owner = $this->entityManager->find(User::class, $owner->getId());
                }
                
                $roles = $owner->getRoles();
                if (!in_array('ROLE_PROVIDER', $roles, true)) {
                    $roles[] = 'ROLE_PROVIDER';
                    $owner->setRoles($roles);
                    $this->entityManager->flush();
                }
            }

            $provider = $this->providerService->register($data, $owner);
            
            // Ensure owner is set (workaround for Doctrine issue)
            if ($owner !== null && $provider->getOwner() === null) {
                $provider->setOwner($owner);
                $this->entityManager->flush();
                
                // Double-check: if still not set, update directly in database
                if ($provider->getOwner() === null) {
                    $connection = $this->entityManager->getConnection();
                    $connection->executeStatement(
                        'UPDATE marketplace_service_providers SET owner_id = ? WHERE id = ?',
                        [$owner->getId(), $provider->getId()]
                    );
                    $this->entityManager->refresh($provider);
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Ihre Registrierung wurde eingereicht. Sie erhalten eine E-Mail, sobald Ihr Profil freigeschaltet wurde.',
                'provider' => [
                    'id' => $provider->getId(),
                    'companyName' => $provider->getCompanyName(),
                    'status' => $provider->getStatus(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Registrierung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the current user's provider profile.
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMyProvider(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Nicht authentifiziert.'], Response::HTTP_UNAUTHORIZED);
        }

        $provider = $this->providerService->findByOwner($user);

        if (!$provider) {
            return new JsonResponse([
                'hasProvider' => false,
                'provider' => null,
            ]);
        }

        return new JsonResponse([
            'hasProvider' => true,
            'provider' => $provider->toArray(includeDetails: true),
        ]);
    }

    /**
     * Get provider by ID (for provider management - may require auth in future).
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getProvider(int $id): JsonResponse
    {
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'provider' => $provider->toArray(includeDetails: true),
        ]);
    }

    /**
     * Update provider details.
     * Requires authentication and ownership verification.
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function updateProvider(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Nicht authentifiziert.'], Response::HTTP_UNAUTHORIZED);
        }

        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        // Verify ownership (unless user is super admin)
        if (!$user->isSuperAdmin() && !$provider->isOwnedBy($user)) {
            return new JsonResponse(['error' => 'Sie haben keine Berechtigung, dieses Profil zu bearbeiten.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validate email if provided
        if (isset($data['contactEmail']) && !filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate description length if provided
        if (isset($data['description']) && strlen($data['description']) < 50) {
            return new JsonResponse([
                'error' => 'Die Beschreibung muss mindestens 50 Zeichen lang sein.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $provider = $this->providerService->update($provider, $data);

            return new JsonResponse([
                'success' => true,
                'provider' => $provider->toArray(includeDetails: true),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Aktualisierung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add a new service offering to a provider.
     */
    #[Route('/{id}/offerings', name: 'add_offering', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addOffering(int $id, Request $request): JsonResponse
    {
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (empty($data['title']) || empty($data['description'])) {
            return new JsonResponse([
                'error' => 'Titel und Beschreibung sind erforderlich.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $offering = $this->providerService->addOffering($provider, $data);

            return new JsonResponse([
                'success' => true,
                'offering' => $offering->toArray(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Angebot konnte nicht erstellt werden: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a service offering.
     */
    #[Route('/{providerId}/offerings/{offeringId}', name: 'update_offering', methods: ['PUT', 'PATCH'], requirements: ['providerId' => '\d+', 'offeringId' => '\d+'])]
    public function updateOffering(int $providerId, int $offeringId, Request $request): JsonResponse
    {
        $provider = $this->providerRepository->find($providerId);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $offering = $this->entityManager->getRepository(ServiceOffering::class)->find($offeringId);

        if (!$offering || $offering->getProvider() !== $provider) {
            return new JsonResponse(['error' => 'Angebot nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $offering = $this->providerService->updateOffering($offering, $data);

            return new JsonResponse([
                'success' => true,
                'offering' => $offering->toArray(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Aktualisierung fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a service offering.
     */
    #[Route('/{providerId}/offerings/{offeringId}', name: 'delete_offering', methods: ['DELETE'], requirements: ['providerId' => '\d+', 'offeringId' => '\d+'])]
    public function deleteOffering(int $providerId, int $offeringId): JsonResponse
    {
        $provider = $this->providerRepository->find($providerId);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $offering = $this->entityManager->getRepository(ServiceOffering::class)->find($offeringId);

        if (!$offering || $offering->getProvider() !== $provider) {
            return new JsonResponse(['error' => 'Angebot nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->providerService->removeOffering($offering);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Löschen fehlgeschlagen: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all offerings for a provider.
     */
    #[Route('/{id}/offerings', name: 'list_offerings', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listOfferings(int $id): JsonResponse
    {
        $provider = $this->providerRepository->find($id);

        if (!$provider) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        $offerings = $provider->getOfferings()->filter(fn($o) => $o->isActive())->toArray();
        
        // Sort by sortOrder
        usort($offerings, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());

        return new JsonResponse([
            'offerings' => array_map(fn(ServiceOffering $o) => $o->toArray(), $offerings),
        ]);
    }
}

