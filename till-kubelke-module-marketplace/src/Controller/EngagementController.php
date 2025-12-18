<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Repository\ServiceOfferingRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\ModuleMarketplace\Service\EngagementService;
use TillKubelke\ModuleMarketplace\Service\ParticipationAggregationService;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * EngagementController - API endpoints for partner engagement management.
 * 
 * Handles the full lifecycle of partner engagements from the customer's perspective.
 */
#[Route('/api/marketplace/engagements', name: 'api_marketplace_engagements_')]
#[IsGranted('ROLE_USER')]
class EngagementController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EngagementService $engagementService,
        private readonly ParticipationAggregationService $aggregationService,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly ServiceOfferingRepository $offeringRepository,
    ) {}

    /**
     * Get all engagements for the current tenant.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $activeOnly = $request->query->getBoolean('active', false);

        $engagements = $activeOnly
            ? $this->engagementService->getActiveEngagements($tenant)
            : $this->engagementService->getAllEngagements($tenant);

        return new JsonResponse([
            'engagements' => array_map(
                fn(PartnerEngagement $e) => $e->toArray(),
                $engagements
            ),
            'count' => count($engagements),
        ]);
    }

    /**
     * Get a specific engagement by ID.
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse([
            'engagement' => $engagement->toArray(includeNotes: true),
            'dataAccessStatus' => $this->engagementService->getDataAccessStatus($engagement),
        ]);
    }

    /**
     * Create a new engagement directly with a provider/offering.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['providerId'], $data['offeringId'])) {
            return new JsonResponse(
                ['error' => 'providerId and offeringId are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $provider = $this->providerRepository->find($data['providerId']);
        if ($provider === null || !$provider->isApproved()) {
            return new JsonResponse(
                ['error' => 'Provider not found or not approved'],
                Response::HTTP_NOT_FOUND
            );
        }

        $offering = $this->offeringRepository->find($data['offeringId']);
        if ($offering === null || !$offering->isActive()) {
            return new JsonResponse(
                ['error' => 'Offering not found or not active'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Validate offering belongs to provider
        if ($offering->getProvider()?->getId() !== $provider->getId()) {
            return new JsonResponse(
                ['error' => 'Offering does not belong to provider'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $engagement = $this->engagementService->create(
            tenant: $tenant,
            provider: $provider,
            offering: $offering,
            agreedPricing: $data['agreedPricing'] ?? null,
            scheduledDate: isset($data['scheduledDate']) 
                ? new \DateTime($data['scheduledDate']) 
                : null,
        );

        return new JsonResponse(
            ['engagement' => $engagement->toArray()],
            Response::HTTP_CREATED
        );
    }

    /**
     * Activate an engagement (move from draft to active).
     */
    #[Route('/{id}/activate', name: 'activate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function activate(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $engagement = $this->engagementService->activate($engagement);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['engagement' => $engagement->toArray()]);
    }

    /**
     * Cancel an engagement.
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $engagement = $this->engagementService->cancel($engagement);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['engagement' => $engagement->toArray()]);
    }

    /**
     * Complete an engagement.
     */
    #[Route('/{id}/complete', name: 'complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function complete(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $engagement = $this->engagementService->complete($engagement);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(['engagement' => $engagement->toArray()]);
    }

    // ========== Data Sharing ==========

    /**
     * Get data access status for an engagement.
     */
    #[Route('/{id}/data-access', name: 'data_access', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getDataAccess(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse(
            $this->engagementService->getDataAccessStatus($engagement)
        );
    }

    /**
     * Grant data access for specific scopes.
     */
    #[Route('/{id}/grant-data', name: 'grant_data', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function grantData(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        $scopes = $data['scopes'] ?? [];

        if (empty($scopes) || !is_array($scopes)) {
            return new JsonResponse(
                ['error' => 'scopes array is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = $this->engagementService->grantDataScopes($engagement, $scopes);

        return new JsonResponse(
            $result,
            $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Revoke data access for a specific scope.
     */
    #[Route('/{id}/revoke-data', name: 'revoke_data', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function revokeData(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        $scope = $data['scope'] ?? null;

        if (empty($scope)) {
            return new JsonResponse(
                ['error' => 'scope is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = $this->engagementService->revokeDataScope($engagement, $scope);

        return new JsonResponse(
            $result,
            $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
        );
    }

    // ========== Notes ==========

    /**
     * Update customer notes (internal).
     */
    #[Route('/{id}/notes', name: 'update_notes', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        $notes = $data['notes'] ?? '';

        $this->engagementService->updateCustomerNotes($engagement, $notes);

        return new JsonResponse(['success' => true]);
    }

    // ========== Result Upload ==========

    /**
     * Get all delivered results for an engagement.
     */
    #[Route('/{id}/results', name: 'list_results', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listResults(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse([
            'results' => $engagement->getDeliveredOutputs() ?? [],
            'count' => count($engagement->getDeliveredOutputs() ?? []),
        ]);
    }

    /**
     * Upload a result for an engagement.
     * 
     * Expected JSON payload:
     * {
     *   "outputType": "copsoq_analysis",
     *   "data": { ... structured data ... },
     *   "fileUrl": "/uploads/result.pdf" (optional),
     *   "summary": "Brief description" (optional)
     * }
     */
    #[Route('/{id}/results', name: 'upload_result', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadResult(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Check engagement is in a state where results can be uploaded
        if (!in_array($engagement->getStatus(), [
            PartnerEngagement::STATUS_DATA_SHARED,
            PartnerEngagement::STATUS_PROCESSING,
            PartnerEngagement::STATUS_DELIVERED,
        ], true)) {
            return new JsonResponse(
                ['error' => 'Results can only be uploaded after data has been shared'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['outputType'])) {
            return new JsonResponse(
                ['error' => 'outputType is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $outputType = $data['outputType'];
        $outputData = $data['data'] ?? [];
        $fileUrl = $data['fileUrl'] ?? null;
        $summary = $data['summary'] ?? null;

        // Build the result payload
        $resultPayload = [
            'data' => $outputData,
        ];

        if ($fileUrl !== null) {
            $resultPayload['fileUrl'] = $fileUrl;
        }

        if ($summary !== null) {
            $resultPayload['summary'] = $summary;
        }

        try {
            $result = $this->engagementService->recordDelivery(
                $engagement,
                $outputType,
                $resultPayload
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(
            $result,
            $result['success'] ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Mark a result as integrated into the BGM process.
     */
    #[Route('/{id}/results/{outputType}/integrate', name: 'integrate_result', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function integrateResult(Request $request, int $id, string $outputType): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementService->getEngagement($tenant, $id);
        if ($engagement === null) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$engagement->hasDeliveredOutput($outputType)) {
            return new JsonResponse(
                ['error' => "Output type '{$outputType}' has not been delivered"],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);
        $integrationPoint = $data['integrationPoint'] ?? null;

        if ($integrationPoint === null) {
            return new JsonResponse(
                ['error' => 'integrationPoint is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->engagementService->markOutputIntegrated($engagement, $outputType, $integrationPoint);

        return new JsonResponse([
            'success' => true,
            'message' => "Output '{$outputType}' marked as integrated at '{$integrationPoint}'",
        ]);
    }

    // ========== Aggregated Stats (for dashboard) ==========

    /**
     * Get engagement statistics for the tenant.
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function getStats(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $activeCount = $this->engagementService->countActiveEngagements($tenant);
        $pendingIntegration = $this->engagementService->getEngagementsWithPendingIntegration($tenant);

        return new JsonResponse([
            'activeEngagements' => $activeCount,
            'pendingIntegration' => count($pendingIntegration),
        ]);
    }

    // ========== Helper Methods ==========

    private function getTenantFromRequest(Request $request): ?Tenant
    {
        $tenantId = $request->headers->get('X-Tenant-ID');
        if ($tenantId === null) {
            return null;
        }

        return $this->entityManager->find(Tenant::class, (int) $tenantId);
    }

    private function missingTenantError(): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'X-Tenant-ID header is required'],
            Response::HTTP_BAD_REQUEST
        );
    }
}


