<?php

namespace TillKubelke\ModuleMarketplace\Service;

use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Repository\InterventionParticipationRepository;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * ParticipationAggregationService - Generates anonymous statistics for partners.
 * 
 * This service is the ONLY way partner data should be shared.
 * It ensures NO personal data (names, emails) ever leaves the platform.
 * 
 * Key principle: Partners get COUNTS and PERCENTAGES, never LISTS.
 */
class ParticipationAggregationService
{
    public function __construct(
        private InterventionParticipationRepository $participationRepository,
    ) {}

    /**
     * Get aggregated statistics for a partner engagement.
     * This is what the partner can see - NO personal data!
     */
    public function getAggregatedStatsForPartner(PartnerEngagement $engagement): array
    {
        $stats = $this->participationRepository->getAggregatedStatsForEngagement($engagement);
        $dietaryRequirements = $this->participationRepository->getAggregatedDietaryRequirements($engagement);

        return [
            // Counts - OK to share
            'registeredCount' => $stats['registeredCount'],
            'attendedCount' => $stats['attendedCount'],
            'noShowCount' => $stats['noShowCount'],
            'cancelledCount' => $stats['cancelledCount'],
            
            // Rates - OK to share
            'attendanceRate' => $stats['attendanceRate'],
            
            // Feedback aggregates - OK to share (anonymous)
            'averageRating' => $stats['averageRating'],
            'ratingCount' => $stats['ratingCount'],
            
            // Dietary requirements - OK to share (aggregated)
            'dietaryRequirements' => $dietaryRequirements,
            
            // Metadata
            'aggregatedAt' => (new \DateTimeImmutable())->format('c'),
            
            // NEVER include:
            // - participant names
            // - participant emails
            // - individual feedback comments
            // - department breakdowns (could identify individuals in small companies)
        ];
    }

    /**
     * Get dietary requirements summary for an engagement.
     * Useful for event planning (catering).
     */
    public function getDietaryRequirementsSummary(PartnerEngagement $engagement): array
    {
        return $this->participationRepository->getAggregatedDietaryRequirements($engagement);
    }

    /**
     * Generate a participation report for internal use.
     * This CAN include personal data - for tenant's internal documentation.
     */
    public function generateInternalReport(Tenant $tenant, int $year): array
    {
        $byCategory = $this->participationRepository->getStatsByCategory($tenant, $year);
        $byDepartment = $this->participationRepository->getStatsByDepartment($tenant, $year);
        $monthlyTrend = $this->participationRepository->getMonthlyTrend($tenant, $year);
        $uniqueParticipants = $this->participationRepository->countUniqueParticipants($tenant, $year);
        $topParticipants = $this->participationRepository->getTopParticipants($tenant, $year, 10);

        return [
            'year' => $year,
            'summary' => [
                'uniqueParticipants' => $uniqueParticipants,
                'totalParticipations' => array_sum(array_column($byCategory, 'totalParticipations')),
            ],
            'byCategory' => $byCategory,
            'byDepartment' => $byDepartment,
            'monthlyTrend' => $monthlyTrend,
            'topParticipants' => $topParticipants, // Internal only!
            'generatedAt' => (new \DateTimeImmutable())->format('c'),
        ];
    }

    /**
     * Generate a report suitable for health insurance documentation.
     * Contains aggregate numbers, no personal data.
     */
    public function generateInsuranceReport(Tenant $tenant, int $year): array
    {
        $byCategory = $this->participationRepository->getStatsByCategory($tenant, $year);
        $uniqueParticipants = $this->participationRepository->countUniqueParticipants($tenant, $year);

        // Calculate totals
        $totalParticipations = 0;
        $categoryBreakdown = [];

        foreach ($byCategory as $cat) {
            $category = $cat['category'] ?? 'unknown';
            $count = (int) ($cat['totalParticipations'] ?? 0);
            $unique = (int) ($cat['uniqueParticipants'] ?? 0);

            $categoryBreakdown[$category] = [
                'participations' => $count,
                'uniqueParticipants' => $unique,
                'categoryLabel' => $this->getCategoryLabel($category),
            ];
            $totalParticipations += $count;
        }

        return [
            'reportType' => 'insurance_documentation',
            'year' => $year,
            'tenantName' => $tenant->getName(),
            'summary' => [
                'totalUniqueParticipants' => $uniqueParticipants,
                'totalParticipations' => $totalParticipations,
            ],
            'byCategory' => $categoryBreakdown,
            'generatedAt' => (new \DateTimeImmutable())->format('c'),
            'disclaimer' => 'Dieser Bericht enthält aggregierte, anonymisierte Daten für die Dokumentation gegenüber der Krankenkasse.',
        ];
    }

    /**
     * Get participation trend data for KPI dashboard.
     */
    public function getKpiTrendData(Tenant $tenant, int $year): array
    {
        $monthlyTrend = $this->participationRepository->getMonthlyTrend($tenant, $year);
        
        // Fill in missing months with zeros
        $trendData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthData = array_filter(
                $monthlyTrend,
                fn($m) => (int) $m['month'] === $month
            );
            $monthData = reset($monthData);

            $trendData[] = [
                'month' => $month,
                'monthName' => $this->getMonthName($month),
                'participations' => (int) ($monthData['totalParticipations'] ?? 0),
                'uniqueParticipants' => (int) ($monthData['uniqueParticipants'] ?? 0),
            ];
        }

        return [
            'year' => $year,
            'trend' => $trendData,
            'totalUniqueParticipants' => $this->participationRepository->countUniqueParticipants($tenant, $year),
        ];
    }

    /**
     * Get engagement rate (% of employees who participated).
     */
    public function getEngagementRate(Tenant $tenant, int $year, int $totalEmployees): array
    {
        $uniqueParticipants = $this->participationRepository->countUniqueParticipants($tenant, $year);
        
        $rate = $totalEmployees > 0 
            ? round($uniqueParticipants / $totalEmployees * 100, 1) 
            : 0;

        return [
            'year' => $year,
            'uniqueParticipants' => $uniqueParticipants,
            'totalEmployees' => $totalEmployees,
            'engagementRate' => $rate,
            'engagementRateFormatted' => $rate . '%',
        ];
    }

    // ========== Private Helpers ==========

    private function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'bewegung' => 'Bewegung',
            'ernaehrung' => 'Ernährung',
            'mental' => 'Mentale Gesundheit',
            'sucht' => 'Suchtprävention',
            'ergonomie' => 'Ergonomie',
            'allgemein' => 'Allgemein',
            default => ucfirst($category),
        };
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März',
            4 => 'April', 5 => 'Mai', 6 => 'Juni',
            7 => 'Juli', 8 => 'August', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
        ];
        return $months[$month] ?? '';
    }
}



