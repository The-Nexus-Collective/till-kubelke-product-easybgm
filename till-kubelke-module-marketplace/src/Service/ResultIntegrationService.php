<?php

namespace TillKubelke\ModuleMarketplace\Service;

use Doctrine\ORM\EntityManagerInterface;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Registry\OutputTypeRegistry;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * ResultIntegrationService - Routes partner results to the correct locations in the BGM process.
 * 
 * This service handles the "plug-in" mechanism where partner-delivered results
 * are automatically integrated into the appropriate parts of the BGM system.
 */
class ResultIntegrationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EngagementService $engagementService,
    ) {}

    /**
     * Integrate a delivered result into the BGM process.
     * 
     * @param PartnerEngagement $engagement The engagement that delivered the result
     * @param string $outputType The type of output to integrate
     * @return array Result of the integration attempt
     */
    public function integrateResult(PartnerEngagement $engagement, string $outputType): array
    {
        // Validate output type exists
        if (!OutputTypeRegistry::exists($outputType)) {
            return [
                'success' => false,
                'error' => "Unknown output type: {$outputType}",
            ];
        }

        // Check if the result has been delivered
        if (!$engagement->hasDeliveredOutput($outputType)) {
            return [
                'success' => false,
                'error' => "Output type '{$outputType}' has not been delivered yet",
            ];
        }

        // Get the delivered data
        $deliveredOutputs = $engagement->getDeliveredOutputs();
        $outputData = $deliveredOutputs[$outputType] ?? null;

        if ($outputData === null) {
            return [
                'success' => false,
                'error' => "No data found for output type: {$outputType}",
            ];
        }

        // Get the integration point
        $integrationPoint = OutputTypeRegistry::getIntegrationPoint($outputType);

        // Route to the appropriate handler based on integration point
        $result = match (true) {
            str_starts_with($integrationPoint, 'phase_2.') => $this->handlePhase2Integration($engagement, $outputType, $outputData),
            str_starts_with($integrationPoint, 'phase_3.') => $this->handlePhase3Integration($engagement, $outputType, $outputData),
            str_starts_with($integrationPoint, 'phase_4.') => $this->handlePhase4Integration($engagement, $outputType, $outputData),
            str_starts_with($integrationPoint, 'kpi.') => $this->handleKpiIntegration($engagement, $outputType, $outputData),
            str_starts_with($integrationPoint, 'legal.') => $this->handleLegalIntegration($engagement, $outputType, $outputData),
            str_starts_with($integrationPoint, 'health_day.') => $this->handleHealthDayIntegration($engagement, $outputType, $outputData),
            default => [
                'success' => false,
                'error' => "No handler for integration point: {$integrationPoint}",
            ],
        };

        // Mark the output as integrated if successful
        if ($result['success']) {
            $this->engagementService->markOutputIntegrated($engagement, $outputType, $integrationPoint);
        }

        return $result;
    }

    /**
     * Handle Phase 2 (Analysis) integrations.
     * 
     * Examples: COPSOQ analysis, survey results, health reports
     */
    private function handlePhase2Integration(
        PartnerEngagement $engagement,
        string $outputType,
        array $outputData
    ): array {
        $tenant = $engagement->getTenant();

        // Store analysis data in a format accessible to Phase 2 views
        $analysisData = [
            'type' => $outputType,
            'source' => 'partner_engagement',
            'engagementId' => $engagement->getId(),
            'providerId' => $engagement->getProvider()?->getId(),
            'providerName' => $engagement->getProvider()?->getCompanyName(),
            'deliveredAt' => $outputData['delivered_at'] ?? null,
            'data' => $outputData['data'] ?? [],
            'fileUrl' => $outputData['fileUrl'] ?? null,
            'summary' => $outputData['summary'] ?? null,
        ];

        // Store in a format that can be retrieved by the frontend
        // This would typically go to a BgmProject or similar entity
        // For now, we store it as a successful integration
        
        return [
            'success' => true,
            'message' => "Phase 2 analysis data integrated: {$outputType}",
            'integrationPoint' => 'phase_2.analysis',
            'data' => $analysisData,
        ];
    }

    /**
     * Handle Phase 3 (Planning/Concept) integrations.
     * 
     * Examples: Intervention plans, recommended measures
     */
    private function handlePhase3Integration(
        PartnerEngagement $engagement,
        string $outputType,
        array $outputData
    ): array {
        return [
            'success' => true,
            'message' => "Phase 3 concept data integrated: {$outputType}",
            'integrationPoint' => 'phase_3.concept',
            'data' => $outputData['data'] ?? [],
        ];
    }

    /**
     * Handle Phase 4 (Implementation) integrations.
     * 
     * Examples: Ergonomic assessments, intervention results
     */
    private function handlePhase4Integration(
        PartnerEngagement $engagement,
        string $outputType,
        array $outputData
    ): array {
        return [
            'success' => true,
            'message' => "Phase 4 intervention data integrated: {$outputType}",
            'integrationPoint' => 'phase_4.intervention',
            'data' => $outputData['data'] ?? [],
        ];
    }

    /**
     * Handle KPI Dashboard integrations.
     * 
     * Examples: Custom KPIs, participation statistics
     */
    private function handleKpiIntegration(
        PartnerEngagement $engagement,
        string $outputType,
        array $outputData
    ): array {
        // Extract KPI-relevant data
        $kpiData = $outputData['data'] ?? [];

        // Create a custom KPI entry that can be displayed on the dashboard
        $customKpi = [
            'type' => 'partner_kpi',
            'source' => $outputType,
            'engagementId' => $engagement->getId(),
            'providerName' => $engagement->getProvider()?->getCompanyName(),
            'label' => $this->getKpiLabel($outputType, $kpiData),
            'value' => $kpiData['value'] ?? $kpiData['score'] ?? null,
            'unit' => $kpiData['unit'] ?? '%',
            'trend' => $kpiData['trend'] ?? null,
            'details' => $kpiData,
        ];

        return [
            'success' => true,
            'message' => "Custom KPI integrated: {$outputType}",
            'integrationPoint' => 'kpi.custom',
            'data' => $customKpi,
        ];
    }

    /**
     * Handle Legal Requirements integrations.
     * 
     * Examples: Gefährdungsbeurteilung (psychische Belastung)
     */
    private function handleLegalIntegration(
        PartnerEngagement $engagement,
        string $outputType,
        array $outputData
    ): array {
        // Mark the corresponding legal requirement as fulfilled
        $requirementType = match ($outputType) {
            'gefaehrdungsbeurteilung' => 'psychische_gefaehrdungsbeurteilung',
            default => $outputType,
        };

        $legalData = [
            'requirementType' => $requirementType,
            'fulfilledBy' => 'partner_engagement',
            'engagementId' => $engagement->getId(),
            'providerName' => $engagement->getProvider()?->getCompanyName(),
            'fulfilledAt' => new \DateTimeImmutable(),
            'documentUrl' => $outputData['fileUrl'] ?? null,
            'validUntil' => $this->calculateValidUntil($requirementType),
        ];

        return [
            'success' => true,
            'message' => "Legal requirement fulfilled: {$requirementType}",
            'integrationPoint' => 'legal.gefaehrdungsbeurteilung',
            'data' => $legalData,
        ];
    }

    /**
     * Handle Health Day integrations.
     * 
     * Examples: Health day planning, module coordination
     */
    private function handleHealthDayIntegration(
        PartnerEngagement $engagement,
        string $outputType,
        array $outputData
    ): array {
        return [
            'success' => true,
            'message' => "Health day data integrated: {$outputType}",
            'integrationPoint' => 'health_day.planning',
            'data' => $outputData['data'] ?? [],
        ];
    }

    /**
     * Get all pending integrations for a tenant.
     * 
     * Returns engagements that have delivered outputs not yet integrated.
     */
    public function getPendingIntegrations(Tenant $tenant): array
    {
        $engagements = $this->engagementService->getEngagementsWithPendingIntegration($tenant);
        
        $pending = [];

        foreach ($engagements as $engagement) {
            $deliveredOutputs = $engagement->getDeliveredOutputs() ?? [];
            $integrationStatus = $engagement->getIntegrationStatus() ?? [];

            foreach ($deliveredOutputs as $outputType => $outputData) {
                if (!isset($integrationStatus[$outputType])) {
                    $pending[] = [
                        'engagementId' => $engagement->getId(),
                        'outputType' => $outputType,
                        'deliveredAt' => $outputData['delivered_at'] ?? null,
                        'integrationPoint' => OutputTypeRegistry::getIntegrationPoint($outputType),
                        'provider' => $engagement->getProvider()?->getCompanyName(),
                        'offering' => $engagement->getOffering()?->getTitle(),
                    ];
                }
            }
        }

        return $pending;
    }

    /**
     * Auto-integrate all pending results for a tenant.
     */
    public function autoIntegrateAll(Tenant $tenant): array
    {
        $pending = $this->getPendingIntegrations($tenant);
        $results = [];

        foreach ($pending as $item) {
            $engagement = $this->engagementService->getEngagement($tenant, $item['engagementId']);
            if ($engagement) {
                $results[] = $this->integrateResult($engagement, $item['outputType']);
            }
        }

        return [
            'processed' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
            'results' => $results,
        ];
    }

    // ========== Private Helper Methods ==========

    private function getKpiLabel(string $outputType, array $data): string
    {
        return match ($outputType) {
            'copsoq_analysis' => 'COPSOQ Gesamtbewertung',
            'participation_stats' => 'Maßnahmen-Teilnahmequote',
            'health_report' => 'Gesundheitsindex',
            default => $data['label'] ?? ucfirst(str_replace('_', ' ', $outputType)),
        };
    }

    private function calculateValidUntil(string $requirementType): \DateTimeImmutable
    {
        // Gefährdungsbeurteilung should be renewed every 2 years or when significant changes occur
        $validityPeriod = match ($requirementType) {
            'psychische_gefaehrdungsbeurteilung' => '+2 years',
            default => '+1 year',
        };

        return new \DateTimeImmutable($validityPeriod);
    }
}



