<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use TillKubelke\ModuleMarketplace\Entity\ServiceInquiry;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Repository\ServiceInquiryRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Service\InquiryService;
use TillKubelke\PlatformFoundation\Auth\Entity\User;
use TillKubelke\PlatformFoundation\Tenant\Controller\AbstractTenantController;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * Controller for managing service inquiries.
 * Allows tenants to send inquiries to providers and view their inquiry history.
 */
#[Route('/api/marketplace/inquiries', name: 'api_marketplace_inquiries_')]
class InquiryController extends AbstractTenantController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly ServiceInquiryRepository $inquiryRepository,
        private readonly InquiryService $inquiryService,
    ) {
    }

    /**
     * Send an inquiry to a service provider.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createInquiry(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (empty($data['providerId'])) {
            return new JsonResponse(['error' => 'Dienstleister-ID ist erforderlich.'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['contactName']) || empty($data['contactEmail']) || empty($data['message'])) {
            return new JsonResponse([
                'error' => 'Name, E-Mail und Nachricht sind erforderlich.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate email
        if (!filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate message length
        if (strlen($data['message']) < 20) {
            return new JsonResponse([
                'error' => 'Die Nachricht muss mindestens 20 Zeichen lang sein.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Find provider
        $provider = $this->providerRepository->find($data['providerId']);
        if (!$provider || !$provider->isApproved()) {
            return new JsonResponse(['error' => 'Dienstleister nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        // Find offering if provided
        $offering = null;
        if (!empty($data['offeringId'])) {
            $offering = $this->entityManager->getRepository(ServiceOffering::class)->find($data['offeringId']);
            if (!$offering || $offering->getProvider() !== $provider) {
                return new JsonResponse(['error' => 'Angebot nicht gefunden.'], Response::HTTP_NOT_FOUND);
            }
        }

        // Get BGM project ID if provided
        $bgmProjectId = $data['bgmProjectId'] ?? null;

        try {
            $inquiry = $this->inquiryService->createInquiry(
                $tenant,
                $provider,
                $data,
                $offering,
                $bgmProjectId,
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Ihre Anfrage wurde erfolgreich gesendet.',
                'inquiry' => $inquiry->toArray(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Anfrage konnte nicht gesendet werden: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List inquiries for the current tenant.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function listInquiries(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $offset = ($page - 1) * $limit;

        $inquiries = $this->inquiryService->getInquiriesForTenant($tenant, $limit, $offset);

        return new JsonResponse([
            'inquiries' => array_map(fn(ServiceInquiry $i) => $i->toArray(), $inquiries),
        ]);
    }

    /**
     * Get a specific inquiry.
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getInquiry(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $inquiry = $this->inquiryRepository->find($id);

        if (!$inquiry || $inquiry->getTenant() !== $tenant) {
            return new JsonResponse(['error' => 'Anfrage nicht gefunden.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'inquiry' => $inquiry->toArray(),
        ]);
    }

    /**
     * Get inquiries linked to a specific BGM project.
     */
    #[Route('/project/{projectId}', name: 'by_project', methods: ['GET'], requirements: ['projectId' => '\d+'])]
    public function getInquiriesByProject(int $projectId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $tenantResult = $this->validateTenant($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $inquiries = $this->inquiryService->getInquiriesForBgmProject($projectId);

        // Filter to only include inquiries from this tenant
        $filteredInquiries = array_filter(
            $inquiries,
            fn(ServiceInquiry $i) => $i->getTenant() === $tenant
        );

        return new JsonResponse([
            'inquiries' => array_map(fn(ServiceInquiry $i) => $i->toArray(), $filteredInquiries),
        ]);
    }

    /**
     * Validate tenant from request header with access control.
     */
    private function validateTenant(Request $request): Tenant|JsonResponse
    {
        try {
            $tenant = $this->getValidatedTenant($request, $this->entityManager);
            if (!$tenant) {
                return new JsonResponse(['error' => 'Tenant ID fehlt.'], Response::HTTP_BAD_REQUEST);
            }
            return $tenant;
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['error' => 'Zugriff auf diesen Tenant verweigert.'], Response::HTTP_FORBIDDEN);
        }
    }
}



