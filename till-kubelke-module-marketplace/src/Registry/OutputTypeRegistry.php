<?php

namespace TillKubelke\ModuleMarketplace\Registry;

/**
 * OutputTypeRegistry - Central definition of all deliverable result types.
 * 
 * Defines what data/results a ServiceOffering can deliver back to the customer.
 * Each type has an integration point that determines where results "plug in".
 * 
 * @example
 *   // Get integration point for a result type
 *   $type = OutputTypeRegistry::get('copsoq_analysis');
 *   $integrationPoint = $type['integrationPoint']; // 'phase_2.analysis'
 */
final class OutputTypeRegistry
{
    /**
     * All available output types that partners can deliver.
     */
    public const TYPES = [
        // ========== Phase 2: Analysis Outputs ==========
        'copsoq_analysis' => [
            'label' => 'COPSOQ-Auswertung',
            'description' => 'Ergebnisse der COPSOQ-Mitarbeiterbefragung',
            'integrationPoint' => 'phase_2.analysis',
            'schema' => 'copsoq_v3',
            'format' => ['json', 'pdf'],
        ],
        'gbpsych_assessment' => [
            'label' => 'Gefährdungsbeurteilung psychischer Belastungen',
            'description' => 'Ergebnisse der GB Psych',
            'integrationPoint' => 'phase_2.analysis',
            'schema' => 'gbpsych_v1',
            'format' => ['json', 'pdf'],
        ],
        'ergonomic_assessment' => [
            'label' => 'Ergonomie-Bewertung',
            'description' => 'Ergebnisse der Arbeitsplatzanalyse',
            'integrationPoint' => 'phase_2.analysis',
            'schema' => 'ergonomic_v1',
            'format' => ['json', 'pdf'],
        ],
        'health_checkup_results' => [
            'label' => 'Gesundheits-Check Ergebnisse',
            'description' => 'Aggregierte Ergebnisse von Gesundheitschecks',
            'integrationPoint' => 'phase_2.analysis',
            'schema' => 'health_checkup_v1',
            'format' => ['json', 'pdf'],
        ],

        // ========== Phase 3: Planning Outputs ==========
        'intervention_plan' => [
            'label' => 'Maßnahmenplan',
            'description' => 'Vorgeschlagene Interventionen basierend auf Analyse',
            'integrationPoint' => 'phase_3.concept',
            'schema' => 'intervention_plan_v1',
            'format' => ['json', 'pdf'],
        ],
        'improvement_recommendations' => [
            'label' => 'Verbesserungsempfehlungen',
            'description' => 'Konkrete Handlungsempfehlungen',
            'integrationPoint' => 'phase_3.concept',
            'schema' => 'recommendations_v1',
            'format' => ['json', 'pdf'],
        ],
        'priority_matrix' => [
            'label' => 'Prioritäts-Matrix',
            'description' => 'Priorisierte Maßnahmen nach Dringlichkeit/Aufwand',
            'integrationPoint' => 'phase_3.concept',
            'schema' => 'priority_matrix_v1',
            'format' => ['json', 'pdf'],
        ],
        'cost_estimate' => [
            'label' => 'Kostenschätzung',
            'description' => 'Geschätzte Kosten für Maßnahmen',
            'integrationPoint' => 'phase_3.concept',
            'schema' => 'cost_estimate_v1',
            'format' => ['json', 'pdf'],
        ],
        'event_concept' => [
            'label' => 'Event-Konzept',
            'description' => 'Konzept für Gesundheitstag oder Event',
            'integrationPoint' => 'health_day.planning',
            'schema' => 'event_concept_v1',
            'format' => ['json', 'pdf'],
        ],
        'vendor_recommendations' => [
            'label' => 'Dienstleister-Empfehlungen',
            'description' => 'Empfohlene Module/Dienstleister für Event',
            'integrationPoint' => 'health_day.planning',
            'schema' => 'vendor_recommendations_v1',
            'format' => ['json'],
        ],
        'schedule' => [
            'label' => 'Ablaufplan',
            'description' => 'Detaillierter Zeitplan für Event',
            'integrationPoint' => 'health_day.planning',
            'schema' => 'schedule_v1',
            'format' => ['json', 'pdf', 'ical'],
        ],
        'budget_plan' => [
            'label' => 'Budgetplan',
            'description' => 'Detaillierte Budgetplanung',
            'integrationPoint' => 'health_day.planning',
            'schema' => 'budget_plan_v1',
            'format' => ['json', 'pdf'],
        ],

        // ========== Phase 4/5: Execution & Evaluation Outputs ==========
        'participation_stats' => [
            'label' => 'Teilnahme-Statistiken',
            'description' => 'Teilnehmerzahlen und -quoten',
            'integrationPoint' => 'kpi.custom',
            'schema' => 'participation_v1',
            'format' => ['json'],
        ],
        'health_scores' => [
            'label' => 'Gesundheits-Scores',
            'description' => 'Aggregierte Gesundheitswerte',
            'integrationPoint' => 'kpi.custom',
            'schema' => 'health_scores_v1',
            'format' => ['json'],
        ],
        'event_feedback' => [
            'label' => 'Event-Feedback',
            'description' => 'Feedback und Bewertungen von Teilnehmern',
            'integrationPoint' => 'phase_5.evaluation',
            'schema' => 'event_feedback_v1',
            'format' => ['json'],
        ],
        'attendance_report' => [
            'label' => 'Anwesenheits-Report',
            'description' => 'Teilnahme-Dokumentation',
            'integrationPoint' => 'phase_4.intervention',
            'schema' => 'attendance_v1',
            'format' => ['json', 'pdf'],
        ],
        'health_report' => [
            'label' => 'Gesundheitsbericht',
            'description' => 'Zusammenfassender Gesundheitsbericht',
            'integrationPoint' => 'phase_5.evaluation',
            'schema' => 'health_report_v1',
            'format' => ['pdf'],
        ],
        'event_report' => [
            'label' => 'Event-Report',
            'description' => 'Nachbericht zum Gesundheitstag',
            'integrationPoint' => 'phase_5.evaluation',
            'schema' => 'event_report_v1',
            'format' => ['json', 'pdf'],
        ],
        'resource_materials' => [
            'label' => 'Materialien & Ressourcen',
            'description' => 'PDFs, Tipps, Anleitungen zum Mitnehmen',
            'integrationPoint' => 'phase_4.intervention',
            'schema' => 'resources_v1',
            'format' => ['pdf', 'zip'],
        ],

        // ========== Legal/Compliance Outputs ==========
        'gefaehrdungsbeurteilung' => [
            'label' => 'Gefährdungsbeurteilung',
            'description' => 'Rechtlich verwertbare Gefährdungsbeurteilung',
            'integrationPoint' => 'legal.gefaehrdungsbeurteilung',
            'schema' => 'gefaehrdungsbeurteilung_v1',
            'format' => ['pdf'],
            'legal_document' => true,
        ],
    ];

    /**
     * All available integration points where results can be plugged in.
     */
    public const INTEGRATION_POINTS = [
        'phase_1.goals' => ['phase' => 1, 'label' => 'Zieldefinition'],
        'phase_1.health_group' => ['phase' => 1, 'label' => 'Arbeitskreis Gesundheit'],
        'phase_2.analysis' => ['phase' => 2, 'label' => 'Bedarfsanalyse'],
        'phase_2.data_collection' => ['phase' => 2, 'label' => 'Datenerhebung'],
        'phase_3.concept' => ['phase' => 3, 'label' => 'Maßnahmenplanung'],
        'phase_4.intervention' => ['phase' => 4, 'label' => 'Durchführung'],
        'phase_5.evaluation' => ['phase' => 5, 'label' => 'Wirksamkeitsprüfung'],
        'phase_6.sustainability' => ['phase' => 6, 'label' => 'Verstetigung'],
        'kpi.custom' => ['phase' => null, 'label' => 'KPI-Dashboard'],
        'health_day.planning' => ['phase' => null, 'label' => 'Gesundheitstag-Planung'],
        'health_day.execution' => ['phase' => null, 'label' => 'Gesundheitstag-Durchführung'],
        'legal.gefaehrdungsbeurteilung' => ['phase' => null, 'label' => 'Rechtliche Anforderungen'],
    ];

    /**
     * Check if an output type exists.
     */
    public static function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    /**
     * Get metadata for an output type.
     */
    public static function get(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }

    /**
     * Get all output types.
     */
    public static function all(): array
    {
        return self::TYPES;
    }

    /**
     * Get all type keys.
     */
    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * Get output types by integration point.
     */
    public static function byIntegrationPoint(string $point): array
    {
        return array_filter(
            self::TYPES,
            fn(array $type) => $type['integrationPoint'] === $point
        );
    }

    /**
     * Get the integration point for a type.
     */
    public static function getIntegrationPoint(string $type): ?string
    {
        return self::TYPES[$type]['integrationPoint'] ?? null;
    }

    /**
     * Check if an integration point exists.
     */
    public static function integrationPointExists(string $point): bool
    {
        return isset(self::INTEGRATION_POINTS[$point]);
    }

    /**
     * Get all integration points.
     */
    public static function allIntegrationPoints(): array
    {
        return self::INTEGRATION_POINTS;
    }

    /**
     * Get integration points for a specific BGM phase.
     */
    public static function integrationPointsForPhase(int $phase): array
    {
        return array_filter(
            self::INTEGRATION_POINTS,
            fn(array $point) => $point['phase'] === $phase
        );
    }

    /**
     * Get legal document types.
     */
    public static function legalDocuments(): array
    {
        return array_filter(
            self::TYPES,
            fn(array $type) => ($type['legal_document'] ?? false) === true
        );
    }
}




