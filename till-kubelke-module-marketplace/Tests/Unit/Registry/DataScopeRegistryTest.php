<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Registry\DataScopeRegistry;

/**
 * Unit tests for DataScopeRegistry.
 */
class DataScopeRegistryTest extends TestCase
{
    // ========== Existence Tests ==========

    public function testExistsReturnsTrueForValidScope(): void
    {
        $this->assertTrue(DataScopeRegistry::exists('employee_list'));
        $this->assertTrue(DataScopeRegistry::exists('goals'));
        $this->assertTrue(DataScopeRegistry::exists('survey_results'));
    }

    public function testExistsReturnsFalseForInvalidScope(): void
    {
        $this->assertFalse(DataScopeRegistry::exists('invalid_scope'));
        $this->assertFalse(DataScopeRegistry::exists(''));
        $this->assertFalse(DataScopeRegistry::exists('random_string'));
    }

    // ========== Get Tests ==========

    public function testGetReturnsArrayForValidScope(): void
    {
        $scope = DataScopeRegistry::get('employee_list');

        $this->assertIsArray($scope);
        $this->assertArrayHasKey('label', $scope);
        $this->assertArrayHasKey('description', $scope);
        $this->assertArrayHasKey('sensitivity', $scope);
        $this->assertArrayHasKey('source', $scope);
    }

    public function testGetReturnsNullForInvalidScope(): void
    {
        $scope = DataScopeRegistry::get('invalid_scope');

        $this->assertNull($scope);
    }

    public function testGetEmployeeListScopeHasCorrectSensitivity(): void
    {
        $scope = DataScopeRegistry::get('employee_list');

        $this->assertEquals(DataScopeRegistry::SENSITIVITY_HIGH, $scope['sensitivity']);
    }

    public function testGetGoalsScopeHasCorrectSensitivity(): void
    {
        $scope = DataScopeRegistry::get('goals');

        $this->assertEquals(DataScopeRegistry::SENSITIVITY_LOW, $scope['sensitivity']);
    }

    public function testGetSurveyResultsScopeHasCorrectSensitivity(): void
    {
        $scope = DataScopeRegistry::get('survey_results');

        $this->assertEquals(DataScopeRegistry::SENSITIVITY_MEDIUM, $scope['sensitivity']);
    }

    // ========== All & Keys Tests ==========

    public function testAllReturnsNonEmptyArray(): void
    {
        $all = DataScopeRegistry::all();

        $this->assertIsArray($all);
        $this->assertNotEmpty($all);
    }

    public function testKeysReturnsAllScopeKeys(): void
    {
        $keys = DataScopeRegistry::keys();

        $this->assertIsArray($keys);
        $this->assertContains('employee_list', $keys);
        $this->assertContains('goals', $keys);
        $this->assertContains('survey_results', $keys);
    }

    // ========== Filter by Sensitivity Tests ==========

    public function testBySensitivityLow(): void
    {
        $lowScopes = DataScopeRegistry::bySensitivity(DataScopeRegistry::SENSITIVITY_LOW);

        $this->assertIsArray($lowScopes);
        $this->assertNotEmpty($lowScopes);

        foreach ($lowScopes as $scope) {
            $this->assertEquals(DataScopeRegistry::SENSITIVITY_LOW, $scope['sensitivity']);
        }
    }

    public function testBySensitivityMedium(): void
    {
        $mediumScopes = DataScopeRegistry::bySensitivity(DataScopeRegistry::SENSITIVITY_MEDIUM);

        $this->assertIsArray($mediumScopes);
        $this->assertNotEmpty($mediumScopes);

        foreach ($mediumScopes as $scope) {
            $this->assertEquals(DataScopeRegistry::SENSITIVITY_MEDIUM, $scope['sensitivity']);
        }
    }

    public function testBySensitivityHigh(): void
    {
        $highScopes = DataScopeRegistry::bySensitivity(DataScopeRegistry::SENSITIVITY_HIGH);

        $this->assertIsArray($highScopes);
        $this->assertNotEmpty($highScopes);

        foreach ($highScopes as $scope) {
            $this->assertEquals(DataScopeRegistry::SENSITIVITY_HIGH, $scope['sensitivity']);
        }
    }

    // ========== GDPR Relevant Tests ==========

    public function testGdprRelevantReturnsOnlyMarkedScopes(): void
    {
        $gdprScopes = DataScopeRegistry::gdprRelevant();

        $this->assertIsArray($gdprScopes);
        $this->assertNotEmpty($gdprScopes);

        // All GDPR-relevant scopes should have the flag
        foreach ($gdprScopes as $scope) {
            $this->assertTrue($scope['gdpr_relevant'] ?? false);
        }

        // Employee list should be GDPR relevant
        $this->assertArrayHasKey('employee_list', $gdprScopes);
    }

    // ========== Validation Tests ==========

    public function testValidateScopesReturnsEmptyForValidScopes(): void
    {
        $invalid = DataScopeRegistry::validateScopes(['employee_list', 'goals']);

        $this->assertEmpty($invalid);
    }

    public function testValidateScopesReturnsInvalidScopes(): void
    {
        $invalid = DataScopeRegistry::validateScopes([
            'employee_list',
            'invalid_scope',
            'goals',
            'another_invalid',
        ]);

        $this->assertCount(2, $invalid);
        $this->assertContains('invalid_scope', $invalid);
        $this->assertContains('another_invalid', $invalid);
    }

    public function testValidateScopesWithEmptyArray(): void
    {
        $invalid = DataScopeRegistry::validateScopes([]);

        $this->assertEmpty($invalid);
    }

    // ========== Labels Tests ==========

    public function testGetLabelsReturnsCorrectLabels(): void
    {
        $labels = DataScopeRegistry::getLabels(['employee_list', 'goals']);

        $this->assertArrayHasKey('employee_list', $labels);
        $this->assertArrayHasKey('goals', $labels);
        $this->assertEquals('Mitarbeiterliste', $labels['employee_list']);
        $this->assertEquals('BGM-Ziele', $labels['goals']);
    }

    public function testGetLabelsSkipsInvalidScopes(): void
    {
        $labels = DataScopeRegistry::getLabels(['employee_list', 'invalid_scope', 'goals']);

        $this->assertCount(2, $labels);
        $this->assertArrayNotHasKey('invalid_scope', $labels);
    }

    // ========== Completeness Tests ==========

    public function testAllScopesHaveRequiredFields(): void
    {
        $requiredFields = ['label', 'description', 'sensitivity', 'source'];

        foreach (DataScopeRegistry::all() as $key => $scope) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $scope,
                    "Scope '{$key}' is missing required field '{$field}'"
                );
            }
        }
    }

    public function testAllScopesHaveValidSensitivity(): void
    {
        $validSensitivities = [
            DataScopeRegistry::SENSITIVITY_LOW,
            DataScopeRegistry::SENSITIVITY_MEDIUM,
            DataScopeRegistry::SENSITIVITY_HIGH,
        ];

        foreach (DataScopeRegistry::all() as $key => $scope) {
            $this->assertContains(
                $scope['sensitivity'],
                $validSensitivities,
                "Scope '{$key}' has invalid sensitivity '{$scope['sensitivity']}'"
            );
        }
    }
}





