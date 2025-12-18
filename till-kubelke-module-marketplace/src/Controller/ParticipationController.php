<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TillKubelke\ModuleMarketplace\Entity\InterventionParticipation;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Repository\InterventionParticipationRepository;
use TillKubelke\ModuleMarketplace\Repository\PartnerEngagementRepository;
use TillKubelke\ModuleMarketplace\Service\ParticipationAggregationService;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * ParticipationController - API endpoints for tracking intervention participation.
 * 
 * Key principle: Personal data stays internal, partners only get aggregates.
 */
#[Route('/api/marketplace/participations', name: 'api_marketplace_participations_')]
#[IsGranted('ROLE_USER')]
class ParticipationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InterventionParticipationRepository $participationRepository,
        private readonly PartnerEngagementRepository $engagementRepository,
        private readonly ParticipationAggregationService $aggregationService,
    ) {}

    /**
     * Get all participations for the current tenant.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagementId = $request->query->getInt('engagementId');
        
        if ($engagementId > 0) {
            $engagement = $this->engagementRepository->find($engagementId);
            if ($engagement === null || $engagement->getTenant()?->getId() !== $tenant->getId()) {
                return new JsonResponse(
                    ['error' => 'Engagement not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            $participations = $this->participationRepository->findByEngagement($engagement);
        } else {
            $participations = $this->participationRepository->findByTenant($tenant);
        }

        return new JsonResponse([
            'participations' => array_map(
                fn(InterventionParticipation $p) => $p->toArray(),
                $participations
            ),
            'count' => count($participations),
        ]);
    }

    /**
     * Get a specific participation by ID.
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $participation = $this->participationRepository->find($id);
        if ($participation === null || $participation->getTenant()?->getId() !== $tenant->getId()) {
            return new JsonResponse(
                ['error' => 'Participation not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse(['participation' => $participation->toArray()]);
    }

    /**
     * Register a participant for an engagement.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $data = json_decode($request->getContent(), true);

        $participation = new InterventionParticipation();
        $participation->setTenant($tenant);

        // Link to engagement if provided
        if (isset($data['engagementId'])) {
            $engagement = $this->engagementRepository->find($data['engagementId']);
            if ($engagement === null || $engagement->getTenant()?->getId() !== $tenant->getId()) {
                return new JsonResponse(
                    ['error' => 'Engagement not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
            $participation->setEngagement($engagement);
        } else {
            // Internal intervention
            $participation->setInterventionType(InterventionParticipation::TYPE_INTERNAL);
            $participation->setInterventionTitle($data['interventionTitle'] ?? 'Interne Maßnahme');
            $participation->setInterventionDescription($data['interventionDescription'] ?? null);
        }

        // Employee data (INTERNAL ONLY!)
        $participation->setEmployeeId($data['employeeId'] ?? null);
        $participation->setEmployeeEmail($data['employeeEmail'] ?? null);
        $participation->setEmployeeName($data['employeeName'] ?? null);
        $participation->setDepartment($data['department'] ?? null);

        // Event details
        if (isset($data['eventDate'])) {
            $participation->setEventDate(new \DateTime($data['eventDate']));
        }
        $participation->setCategory($data['category'] ?? null);
        $participation->setSpecialRequirements($data['specialRequirements'] ?? null);

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        return new JsonResponse(
            ['participation' => $participation->toArray()],
            Response::HTTP_CREATED
        );
    }

    /**
     * Bulk register multiple participants.
     */
    #[Route('/bulk', name: 'bulk_create', methods: ['POST'])]
    public function bulkCreate(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $data = json_decode($request->getContent(), true);
        $participants = $data['participants'] ?? [];
        $engagementId = $data['engagementId'] ?? null;

        if (empty($participants)) {
            return new JsonResponse(
                ['error' => 'participants array is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $engagement = null;
        if ($engagementId !== null) {
            $engagement = $this->engagementRepository->find($engagementId);
            if ($engagement === null || $engagement->getTenant()?->getId() !== $tenant->getId()) {
                return new JsonResponse(
                    ['error' => 'Engagement not found'],
                    Response::HTTP_NOT_FOUND
                );
            }
        }

        $created = [];
        foreach ($participants as $participantData) {
            $participation = new InterventionParticipation();
            $participation->setTenant($tenant);

            if ($engagement !== null) {
                $participation->setEngagement($engagement);
            } else {
                $participation->setInterventionType(InterventionParticipation::TYPE_INTERNAL);
                $participation->setInterventionTitle($data['interventionTitle'] ?? 'Interne Maßnahme');
            }

            $participation->setEmployeeId($participantData['employeeId'] ?? null);
            $participation->setEmployeeEmail($participantData['employeeEmail'] ?? null);
            $participation->setEmployeeName($participantData['employeeName'] ?? null);
            $participation->setDepartment($participantData['department'] ?? null);

            if (isset($data['eventDate'])) {
                $participation->setEventDate(new \DateTime($data['eventDate']));
            }
            $participation->setCategory($data['category'] ?? null);
            $participation->setSpecialRequirements($participantData['specialRequirements'] ?? null);

            $this->entityManager->persist($participation);
            $created[] = $participation;
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'created' => count($created),
            'participations' => array_map(fn($p) => $p->toArray(), $created),
        ], Response::HTTP_CREATED);
    }

    /**
     * Mark a participant as attended.
     */
    #[Route('/{id}/attend', name: 'attend', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markAttended(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $participation = $this->participationRepository->find($id);
        if ($participation === null || $participation->getTenant()?->getId() !== $tenant->getId()) {
            return new JsonResponse(
                ['error' => 'Participation not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $participation->markAttended();
        $this->entityManager->flush();

        return new JsonResponse(['participation' => $participation->toArray()]);
    }

    /**
     * Mark a participant as no-show.
     */
    #[Route('/{id}/no-show', name: 'no_show', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markNoShow(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $participation = $this->participationRepository->find($id);
        if ($participation === null || $participation->getTenant()?->getId() !== $tenant->getId()) {
            return new JsonResponse(
                ['error' => 'Participation not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $participation->markNoShow();
        $this->entityManager->flush();

        return new JsonResponse(['participation' => $participation->toArray()]);
    }

    /**
     * Cancel a participation.
     */
    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $participation = $this->participationRepository->find($id);
        if ($participation === null || $participation->getTenant()?->getId() !== $tenant->getId()) {
            return new JsonResponse(
                ['error' => 'Participation not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $participation->markCancelled();
        $this->entityManager->flush();

        return new JsonResponse(['participation' => $participation->toArray()]);
    }

    /**
     * Add feedback for a participation.
     */
    #[Route('/{id}/feedback', name: 'feedback', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addFeedback(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $participation = $this->participationRepository->find($id);
        if ($participation === null || $participation->getTenant()?->getId() !== $tenant->getId()) {
            return new JsonResponse(
                ['error' => 'Participation not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['rating'])) {
            try {
                $participation->setRating((int) $data['rating']);
            } catch (\InvalidArgumentException $e) {
                return new JsonResponse(
                    ['error' => $e->getMessage()],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        if (isset($data['comment'])) {
            $participation->setFeedbackComment($data['comment']);
        }

        $this->entityManager->flush();

        return new JsonResponse(['participation' => $participation->toArray()]);
    }

    // ========== Aggregated Reports (GDPR-safe for partners) ==========

    /**
     * Get aggregated stats for an engagement (safe to share with partners).
     */
    #[Route('/engagement/{engagementId}/stats', name: 'engagement_stats', methods: ['GET'], requirements: ['engagementId' => '\d+'])]
    public function getEngagementStats(Request $request, int $engagementId): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $engagement = $this->engagementRepository->find($engagementId);
        if ($engagement === null || $engagement->getTenant()?->getId() !== $tenant->getId()) {
            return new JsonResponse(
                ['error' => 'Engagement not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $stats = $this->aggregationService->getAggregatedStatsForPartner($engagement);

        return new JsonResponse($stats);
    }

    /**
     * Get internal participation report (includes personal data).
     */
    #[Route('/reports/internal', name: 'internal_report', methods: ['GET'])]
    public function getInternalReport(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $year = $request->query->getInt('year', (int) date('Y'));
        $report = $this->aggregationService->generateInternalReport($tenant, $year);

        return new JsonResponse($report);
    }

    /**
     * Get insurance documentation report (anonymous).
     */
    #[Route('/reports/insurance', name: 'insurance_report', methods: ['GET'])]
    public function getInsuranceReport(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $year = $request->query->getInt('year', (int) date('Y'));
        $report = $this->aggregationService->generateInsuranceReport($tenant, $year);

        return new JsonResponse($report);
    }

    /**
     * Get KPI trend data.
     */
    #[Route('/reports/kpi-trend', name: 'kpi_trend', methods: ['GET'])]
    public function getKpiTrend(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if ($tenant === null) {
            return $this->missingTenantError();
        }

        $year = $request->query->getInt('year', (int) date('Y'));
        $trend = $this->aggregationService->getKpiTrendData($tenant, $year);

        return new JsonResponse($trend);
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


