<?php

namespace TillKubelke\ModuleMarketplace\Registry;

/**
 * DataScopeRegistry - Central definition of all shareable data types.
 * 
 * Defines what data a ServiceOffering can request from a customer.
 * Each scope has metadata about sensitivity, source, and description.
 * 
 * @example
 *   // Check if a scope exists
 *   DataScopeRegistry::exists('employee_list'); // true
 *   
 *   // Get scope metadata
 *   $scope = DataScopeRegistry::get('employee_list');
 *   // ['label' => 'Mitarbeiterliste', 'sensitivity' => 'high', ...]
 */
final class DataScopeRegistry
{
    public const SENSITIVITY_LOW = 'low';
    public const SENSITIVITY_MEDIUM = 'medium';
    public const SENSITIVITY_HIGH = 'high';

    /**
     * All available data scopes that can be shared with partners.
     */
    public const SCOPES = [
        // ========== Low Sensitivity (Metadata) ==========
        'employee_count' => [
            'label' => 'Mitarbeiteranzahl',
            'description' => 'Anzahl der Mitarbeiter (keine Namen)',
            'sensitivity' => self::SENSITIVITY_LOW,
            'source' => 'tenant',
            'example' => '120 Mitarbeiter',
        ],
        'location' => [
            'label' => 'Standort',
            'description' => 'Firmenstandort für Vor-Ort-Termine',
            'sensitivity' => self::SENSITIVITY_LOW,
            'source' => 'tenant',
            'example' => 'Musterstraße 123, 80331 München',
        ],
        'goals' => [
            'label' => 'BGM-Ziele',
            'description' => 'Definierte Gesundheitsziele aus Phase 1',
            'sensitivity' => self::SENSITIVITY_LOW,
            'source' => 'bgm_project',
            'example' => 'Krankenstand senken, Rückengesundheit fördern',
        ],
        'budget' => [
            'label' => 'Budget',
            'description' => 'Verfügbares Budget für Maßnahmen',
            'sensitivity' => self::SENSITIVITY_LOW,
            'source' => 'bgm_project',
            'example' => '5.000 € für Gesundheitstag',
        ],
        'dietary_preferences' => [
            'label' => 'Ernährungspräferenzen',
            'description' => 'Aggregierte Diätanforderungen (vegan, vegetarisch, etc.)',
            'sensitivity' => self::SENSITIVITY_LOW,
            'source' => 'aggregated',
            'example' => '3x vegan, 2x vegetarisch, 1x glutenfrei',
        ],
        'workstation_types' => [
            'label' => 'Arbeitsplatztypen',
            'description' => 'Arten von Arbeitsplätzen (Büro, Produktion, Homeoffice)',
            'sensitivity' => self::SENSITIVITY_LOW,
            'source' => 'tenant',
            'example' => '80 Büro, 30 Produktion, 10 Homeoffice',
        ],

        // ========== Medium Sensitivity (Anonymized Data) ==========
        'survey_results' => [
            'label' => 'Umfrage-Ergebnisse',
            'description' => 'Anonymisierte Umfrageergebnisse für Analyse',
            'sensitivity' => self::SENSITIVITY_MEDIUM,
            'source' => 'module_survey',
            'example' => 'COPSOQ-Ergebnisse (aggregiert)',
        ],
        'kpi_baseline' => [
            'label' => 'KPI-Ausgangswerte',
            'description' => 'Krankenstand, Fluktuation als Baseline',
            'sensitivity' => self::SENSITIVITY_MEDIUM,
            'source' => 'bgm_project',
            'example' => 'Krankenstand: 5.2%, Fluktuation: 12%',
        ],
        'complaint_data' => [
            'label' => 'Beschwerdedaten',
            'description' => 'Anonymisierte Gesundheitsbeschwerden',
            'sensitivity' => self::SENSITIVITY_MEDIUM,
            'source' => 'module_survey',
            'example' => '38% klagen über Rückenschmerzen',
        ],
        'previous_events' => [
            'label' => 'Frühere Maßnahmen',
            'description' => 'Welche Maßnahmen wurden bereits durchgeführt',
            'sensitivity' => self::SENSITIVITY_MEDIUM,
            'source' => 'bgm_project',
            'example' => '2023: Gesundheitstag, 2024: Rückenkurs',
        ],
        'floor_plan' => [
            'label' => 'Grundriss',
            'description' => 'Grundriss für Begehungsplanung',
            'sensitivity' => self::SENSITIVITY_MEDIUM,
            'source' => 'tenant',
            'example' => 'PDF mit Büro-Grundriss',
        ],

        // ========== High Sensitivity (Personal Data) ==========
        'employee_list' => [
            'label' => 'Mitarbeiterliste',
            'description' => 'Namen und E-Mail-Adressen für Einladungen',
            'sensitivity' => self::SENSITIVITY_HIGH,
            'source' => 'hr_integration',
            'example' => 'Liste mit 120 E-Mail-Adressen',
            'gdpr_relevant' => true,
        ],
        'employee_emails' => [
            'label' => 'Mitarbeiter-E-Mails',
            'description' => 'E-Mail-Adressen für Umfrage-Einladungen',
            'sensitivity' => self::SENSITIVITY_HIGH,
            'source' => 'hr_integration',
            'example' => '120 E-Mail-Adressen',
            'gdpr_relevant' => true,
        ],
    ];

    /**
     * Check if a scope exists.
     */
    public static function exists(string $scope): bool
    {
        return isset(self::SCOPES[$scope]);
    }

    /**
     * Get metadata for a scope.
     */
    public static function get(string $scope): ?array
    {
        return self::SCOPES[$scope] ?? null;
    }

    /**
     * Get all scopes.
     */
    public static function all(): array
    {
        return self::SCOPES;
    }

    /**
     * Get all scope keys.
     */
    public static function keys(): array
    {
        return array_keys(self::SCOPES);
    }

    /**
     * Get scopes filtered by sensitivity level.
     */
    public static function bySensitivity(string $sensitivity): array
    {
        return array_filter(
            self::SCOPES,
            fn(array $scope) => $scope['sensitivity'] === $sensitivity
        );
    }

    /**
     * Get scopes that are GDPR relevant.
     */
    public static function gdprRelevant(): array
    {
        return array_filter(
            self::SCOPES,
            fn(array $scope) => ($scope['gdpr_relevant'] ?? false) === true
        );
    }

    /**
     * Validate an array of scope keys.
     * 
     * @return string[] Invalid scope keys
     */
    public static function validateScopes(array $scopes): array
    {
        return array_filter(
            $scopes,
            fn(string $scope) => !self::exists($scope)
        );
    }

    /**
     * Get human-readable labels for an array of scope keys.
     */
    public static function getLabels(array $scopes): array
    {
        $labels = [];
        foreach ($scopes as $scope) {
            if (self::exists($scope)) {
                $labels[$scope] = self::SCOPES[$scope]['label'];
            }
        }
        return $labels;
    }
}







