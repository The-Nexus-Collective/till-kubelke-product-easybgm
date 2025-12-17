<?php

namespace TillKubelke\ModuleMarketplace\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use TillKubelke\ModuleMarketplace\Registry\OutputTypeRegistry;

/**
 * Unit tests for OutputTypeRegistry.
 */
class OutputTypeRegistryTest extends TestCase
{
    // ========== Existence Tests ==========

    public function testExistsReturnsTrueForValidType(): void
    {
        $this->assertTrue(OutputTypeRegistry::exists('copsoq_analysis'));
        $this->assertTrue(OutputTypeRegistry::exists('intervention_plan'));
        $this->assertTrue(OutputTypeRegistry::exists('health_report'));
    }

    public function testExistsReturnsFalseForInvalidType(): void
    {
        $this->assertFalse(OutputTypeRegistry::exists('invalid_type'));
        $this->assertFalse(OutputTypeRegistry::exists(''));
        $this->assertFalse(OutputTypeRegistry::exists('random_string'));
    }

    // ========== Get Tests ==========

    public function testGetReturnsArrayForValidType(): void
    {
        $type = OutputTypeRegistry::get('copsoq_analysis');

        $this->assertIsArray($type);
        $this->assertArrayHasKey('label', $type);
        $this->assertArrayHasKey('description', $type);
        $this->assertArrayHasKey('integrationPoint', $type);
        $this->assertArrayHasKey('schema', $type);
        $this->assertArrayHasKey('format', $type);
    }

    public function testGetReturnsNullForInvalidType(): void
    {
        $type = OutputTypeRegistry::get('invalid_type');

        $this->assertNull($type);
    }

    // ========== All & Keys Tests ==========

    public function testAllReturnsNonEmptyArray(): void
    {
        $all = OutputTypeRegistry::all();

        $this->assertIsArray($all);
        $this->assertNotEmpty($all);
    }

    public function testKeysReturnsAllTypeKeys(): void
    {
        $keys = OutputTypeRegistry::keys();

        $this->assertIsArray($keys);
        $this->assertContains('copsoq_analysis', $keys);
        $this->assertContains('intervention_plan', $keys);
        $this->assertContains('health_report', $keys);
    }

    // ========== Integration Point Tests ==========

    public function testGetIntegrationPointForValidType(): void
    {
        $point = OutputTypeRegistry::getIntegrationPoint('copsoq_analysis');

        $this->assertEquals('phase_2.analysis', $point);
    }

    public function testGetIntegrationPointForInvalidType(): void
    {
        $point = OutputTypeRegistry::getIntegrationPoint('invalid_type');

        $this->assertNull($point);
    }

    public function testByIntegrationPointReturnsMatchingTypes(): void
    {
        $types = OutputTypeRegistry::byIntegrationPoint('phase_2.analysis');

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);

        // All returned types should have this integration point
        foreach ($types as $type) {
            $this->assertEquals('phase_2.analysis', $type['integrationPoint']);
        }

        // COPSOQ should be in there
        $this->assertArrayHasKey('copsoq_analysis', $types);
    }

    public function testByIntegrationPointReturnsEmptyForUnknownPoint(): void
    {
        $types = OutputTypeRegistry::byIntegrationPoint('unknown.point');

        $this->assertIsArray($types);
        $this->assertEmpty($types);
    }

    // ========== Integration Points Registry Tests ==========

    public function testIntegrationPointExists(): void
    {
        $this->assertTrue(OutputTypeRegistry::integrationPointExists('phase_2.analysis'));
        $this->assertTrue(OutputTypeRegistry::integrationPointExists('kpi.custom'));
        $this->assertTrue(OutputTypeRegistry::integrationPointExists('health_day.planning'));
    }

    public function testIntegrationPointDoesNotExist(): void
    {
        $this->assertFalse(OutputTypeRegistry::integrationPointExists('invalid.point'));
    }

    public function testAllIntegrationPointsReturnsNonEmptyArray(): void
    {
        $points = OutputTypeRegistry::allIntegrationPoints();

        $this->assertIsArray($points);
        $this->assertNotEmpty($points);
        $this->assertArrayHasKey('phase_2.analysis', $points);
    }

    public function testIntegrationPointsHaveRequiredFields(): void
    {
        $points = OutputTypeRegistry::allIntegrationPoints();

        foreach ($points as $key => $point) {
            $this->assertArrayHasKey('phase', $point, "Point '{$key}' missing 'phase' field");
            $this->assertArrayHasKey('label', $point, "Point '{$key}' missing 'label' field");
        }
    }

    // ========== Integration Points for Phase Tests ==========

    public function testIntegrationPointsForPhase2(): void
    {
        $points = OutputTypeRegistry::integrationPointsForPhase(2);

        $this->assertIsArray($points);
        $this->assertNotEmpty($points);
        $this->assertArrayHasKey('phase_2.analysis', $points);

        foreach ($points as $point) {
            $this->assertEquals(2, $point['phase']);
        }
    }

    public function testIntegrationPointsForPhase4(): void
    {
        $points = OutputTypeRegistry::integrationPointsForPhase(4);

        $this->assertIsArray($points);
        $this->assertArrayHasKey('phase_4.intervention', $points);
    }

    public function testIntegrationPointsForNonExistentPhase(): void
    {
        $points = OutputTypeRegistry::integrationPointsForPhase(99);

        $this->assertIsArray($points);
        $this->assertEmpty($points);
    }

    // ========== Legal Documents Tests ==========

    public function testLegalDocumentsReturnsOnlyLegalTypes(): void
    {
        $legal = OutputTypeRegistry::legalDocuments();

        $this->assertIsArray($legal);

        foreach ($legal as $type) {
            $this->assertTrue($type['legal_document'] ?? false);
        }

        // Gefährdungsbeurteilung should be a legal document
        $this->assertArrayHasKey('gefaehrdungsbeurteilung', $legal);
    }

    // ========== Completeness Tests ==========

    public function testAllTypesHaveRequiredFields(): void
    {
        $requiredFields = ['label', 'description', 'integrationPoint', 'schema', 'format'];

        foreach (OutputTypeRegistry::all() as $key => $type) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $type,
                    "Type '{$key}' is missing required field '{$field}'"
                );
            }
        }
    }

    public function testAllTypesHaveValidIntegrationPoint(): void
    {
        foreach (OutputTypeRegistry::all() as $key => $type) {
            $integrationPoint = $type['integrationPoint'];
            $this->assertTrue(
                OutputTypeRegistry::integrationPointExists($integrationPoint),
                "Type '{$key}' has invalid integration point '{$integrationPoint}'"
            );
        }
    }

    public function testAllTypesHaveValidFormat(): void
    {
        $validFormats = ['json', 'pdf', 'ical', 'zip'];

        foreach (OutputTypeRegistry::all() as $key => $type) {
            $this->assertIsArray(
                $type['format'],
                "Type '{$key}' format should be an array"
            );

            foreach ($type['format'] as $format) {
                $this->assertContains(
                    $format,
                    $validFormats,
                    "Type '{$key}' has invalid format '{$format}'"
                );
            }
        }
    }
}

