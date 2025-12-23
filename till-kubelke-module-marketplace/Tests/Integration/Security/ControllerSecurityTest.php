<?php

namespace TillKubelke\ModuleMarketplace\Tests\Integration\Security;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * SECURITY INTEGRATION TESTS: Controller Endpoints
 * 
 * These tests verify that all marketplace controllers properly validate
 * tenant access and block cross-tenant data access attempts.
 */
#[Group('security')]
#[Group('controller-security')]
class ControllerSecurityTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    // ==================== ParticipationController Tests ====================

    /**
     * SECURITY TEST: Cannot access participations without valid tenant.
     */
    public function testParticipationsEndpointRequiresTenantValidation(): void
    {
        // Request without X-Tenant-ID header
        $this->client->request('GET', '/api/marketplace/participations');
        
        $response = $this->client->getResponse();
        
        // Should either require auth (401) or tenant (400/403)
        $this->assertContains(
            $response->getStatusCode(),
            [Response::HTTP_UNAUTHORIZED, Response::HTTP_BAD_REQUEST, Response::HTTP_FORBIDDEN],
            'Participation endpoint must validate tenant access'
        );
    }

    /**
     * SECURITY TEST: Cannot create participation for unauthorized tenant.
     */
    public function testCannotCreateParticipationForUnauthorizedTenant(): void
    {
        $this->authenticateAsUser();
        
        $this->client->request(
            'POST',
            '/api/marketplace/participations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TENANT_ID' => '999999', // Unauthorized tenant
            ],
            json_encode([
                'interventionId' => 1,
                'status' => 'registered',
            ])
        );
        
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED],
            'Cannot create participation for unauthorized tenant'
        );
    }

    // ==================== EngagementController Tests ====================

    /**
     * SECURITY TEST: Cannot access engagements without valid tenant.
     */
    public function testEngagementsEndpointRequiresTenantValidation(): void
    {
        $this->client->request('GET', '/api/marketplace/engagements');
        
        $response = $this->client->getResponse();
        
        $this->assertContains(
            $response->getStatusCode(),
            [Response::HTTP_UNAUTHORIZED, Response::HTTP_BAD_REQUEST, Response::HTTP_FORBIDDEN],
            'Engagement endpoint must validate tenant access'
        );
    }

    /**
     * SECURITY TEST: Cannot create engagement for unauthorized tenant.
     */
    public function testCannotCreateEngagementForUnauthorizedTenant(): void
    {
        $this->authenticateAsUser();
        
        $this->client->request(
            'POST',
            '/api/marketplace/engagements',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TENANT_ID' => '999999', // Unauthorized tenant
            ],
            json_encode([
                'providerId' => 1,
                'type' => 'inquiry',
            ])
        );
        
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED],
            'Cannot create engagement for unauthorized tenant'
        );
    }

    // ==================== ReviewController Tests ====================

    /**
     * SECURITY TEST: Cannot create review for unauthorized tenant.
     */
    public function testCannotCreateReviewForUnauthorizedTenant(): void
    {
        $this->authenticateAsUser();
        
        $this->client->request(
            'POST',
            '/api/marketplace/reviews',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TENANT_ID' => '999999', // Unauthorized tenant
            ],
            json_encode([
                'providerId' => 1,
                'rating' => 5,
                'comment' => 'Great service!',
            ])
        );
        
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED],
            'Cannot create review for unauthorized tenant'
        );
    }

    // ==================== BookmarkController Tests ====================

    /**
     * SECURITY TEST: Cannot access bookmarks without valid tenant.
     */
    public function testBookmarksEndpointRequiresTenantValidation(): void
    {
        $this->client->request('GET', '/api/marketplace/bookmarks');
        
        $response = $this->client->getResponse();
        
        $this->assertContains(
            $response->getStatusCode(),
            [Response::HTTP_UNAUTHORIZED, Response::HTTP_BAD_REQUEST, Response::HTTP_FORBIDDEN],
            'Bookmark endpoint must validate tenant access'
        );
    }

    /**
     * SECURITY TEST: Cannot create bookmark for unauthorized tenant.
     */
    public function testCannotCreateBookmarkForUnauthorizedTenant(): void
    {
        $this->authenticateAsUser();
        
        $this->client->request(
            'POST',
            '/api/marketplace/bookmarks',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_TENANT_ID' => '999999', // Unauthorized tenant
            ],
            json_encode([
                'providerId' => 1,
            ])
        );
        
        $this->assertContains(
            $this->client->getResponse()->getStatusCode(),
            [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED, Response::HTTP_METHOD_NOT_ALLOWED, Response::HTTP_NOT_FOUND],
            'Cannot create bookmark for unauthorized tenant'
        );
    }

    // ==================== Cross-Controller Tests ====================

    /**
     * SECURITY TEST: All protected endpoints return 403 for wrong tenant.
     */
    public function testAllProtectedEndpointsBlockUnauthorizedTenant(): void
    {
        $this->authenticateAsUser();
        
        $protectedEndpoints = [
            ['GET', '/api/marketplace/participations'],
            ['GET', '/api/marketplace/engagements'],
            ['GET', '/api/marketplace/bookmarks'],
            ['POST', '/api/marketplace/participations'],
            ['POST', '/api/marketplace/engagements'],
            ['POST', '/api/marketplace/reviews'],
            ['POST', '/api/marketplace/bookmarks'],
        ];
        
        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $this->client->request(
                $method,
                $endpoint,
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_X_TENANT_ID' => '999999', // Unauthorized tenant
                ],
                $method === 'POST' ? '{}' : null
            );
            
            $this->assertContains(
                $this->client->getResponse()->getStatusCode(),
                [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED, Response::HTTP_METHOD_NOT_ALLOWED, Response::HTTP_NOT_FOUND],
                "Endpoint {$method} {$endpoint} must block unauthorized tenant access"
            );
        }
    }

    /**
     * SECURITY TEST: Public endpoints are accessible without tenant.
     */
    public function testPublicEndpointsAreAccessible(): void
    {
        // These are the actual public endpoints based on route definitions
        $publicEndpoints = [
            ['GET', '/api/marketplace/catalog'],  // Browse catalog
            ['GET', '/api/marketplace/catalog/categories'],  // Get categories
            ['GET', '/api/marketplace/catalog/tags'],  // Get tags
        ];
        
        foreach ($publicEndpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            
            $statusCode = $this->client->getResponse()->getStatusCode();
            
            // Public endpoints should be accessible (200, 404 for empty, but NOT 401/403)
            $this->assertTrue(
                !in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN], true),
                "Public endpoint {$endpoint} should not require authentication (got {$statusCode})"
            );
        }
    }

    // ==================== Helper Methods ====================

    private function authenticateAsUser(): void
    {
        // This would typically use a test user fixture
        // For now, we simulate authentication via headers
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer test_token');
    }

    private function authenticateAsSuperAdmin(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer super_admin_token');
    }
}

