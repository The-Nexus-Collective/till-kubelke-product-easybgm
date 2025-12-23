<?php

namespace TillKubelke\ModuleMarketplace\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Entity\PartnerReview;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Repository\PartnerEngagementRepository;
use TillKubelke\ModuleMarketplace\Repository\PartnerReviewRepository;
use TillKubelke\ModuleMarketplace\Repository\ServiceProviderRepository;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * Controller for partner reviews.
 * 
 * Public reviews are visible to everyone, but only authenticated users
 * with completed engagements can submit reviews.
 */
#[Route('/api/marketplace/reviews', name: 'api_marketplace_reviews_')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PartnerReviewRepository $reviewRepository,
        private readonly ServiceProviderRepository $providerRepository,
        private readonly PartnerEngagementRepository $engagementRepository,
    ) {
    }

    // ========== Public Endpoints ==========

    /**
     * Get approved reviews for a provider (public).
     */
    #[Route('/provider/{providerId}', name: 'by_provider', methods: ['GET'], requirements: ['providerId' => '\d+'])]
    public function getProviderReviews(int $providerId, Request $request): JsonResponse
    {
        $provider = $this->providerRepository->find($providerId);
        if (!$provider) {
            return new JsonResponse(['error' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        $limit = min(50, (int) $request->query->get('limit', 20));
        $reviews = $this->reviewRepository->findApprovedByProvider($provider, $limit);
        $stats = $this->reviewRepository->getProviderRatingStats($provider);
        $distribution = $this->reviewRepository->getProviderRatingDistribution($provider);

        return new JsonResponse([
            'reviews' => array_map(fn(PartnerReview $r) => $r->toArray(), $reviews),
            'stats' => $stats,
            'distribution' => $distribution,
        ]);
    }

    /**
     * Get rating stats for multiple providers (for catalog cards).
     */
    #[Route('/stats', name: 'batch_stats', methods: ['POST'])]
    public function getBatchStats(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $providerIds = $data['providerIds'] ?? [];

        if (empty($providerIds) || !is_array($providerIds)) {
            return new JsonResponse(['error' => 'providerIds array required'], Response::HTTP_BAD_REQUEST);
        }

        // Limit to 50 providers per request
        $providerIds = array_slice($providerIds, 0, 50);

        $stats = [];
        foreach ($providerIds as $id) {
            $provider = $this->providerRepository->find($id);
            if ($provider) {
                $providerStats = $this->reviewRepository->getProviderRatingStats($provider);
                $stats[$id] = [
                    'reviewCount' => $providerStats['reviewCount'],
                    'averageRating' => $providerStats['averageRating'],
                    'recommendRate' => $providerStats['recommendRate'],
                ];
            }
        }

        return new JsonResponse(['stats' => $stats]);
    }

    // ========== Authenticated User Endpoints ==========

    /**
     * Get my submitted reviews.
     */
    #[Route('/my', name: 'my_reviews', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMyReviews(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if (!$tenant) {
            return new JsonResponse(['error' => 'Tenant not found'], Response::HTTP_BAD_REQUEST);
        }

        $reviews = $this->reviewRepository->findByTenant($tenant);

        return new JsonResponse([
            'reviews' => array_map(fn(PartnerReview $r) => $r->toArray(true), $reviews),
        ]);
    }

    /**
     * Check if user can review a provider (has completed engagement).
     */
    #[Route('/can-review/{providerId}', name: 'can_review', methods: ['GET'], requirements: ['providerId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function canReview(int $providerId, Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if (!$tenant) {
            return new JsonResponse(['error' => 'Tenant not found'], Response::HTTP_BAD_REQUEST);
        }

        $provider = $this->providerRepository->find($providerId);
        if (!$provider) {
            return new JsonResponse(['error' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if already reviewed
        $hasReviewed = $this->reviewRepository->hasReviewedProvider($tenant, $provider);
        if ($hasReviewed) {
            return new JsonResponse([
                'canReview' => false,
                'reason' => 'already_reviewed',
                'message' => 'Sie haben diesen Anbieter bereits bewertet.',
            ]);
        }

        // Find completed engagements for this provider
        $completedEngagements = $this->engagementRepository->findBy([
            'tenant' => $tenant,
            'provider' => $provider,
            'status' => PartnerEngagement::STATUS_COMPLETED,
        ]);

        if (empty($completedEngagements)) {
            return new JsonResponse([
                'canReview' => false,
                'reason' => 'no_engagement',
                'message' => 'Sie können nur Anbieter bewerten, mit denen Sie eine abgeschlossene Zusammenarbeit haben.',
            ]);
        }

        // Find engagement that hasn't been reviewed yet
        $availableEngagement = null;
        foreach ($completedEngagements as $eng) {
            $existingReview = $this->reviewRepository->findByEngagement($eng->getId());
            if (!$existingReview) {
                $availableEngagement = $eng;
                break;
            }
        }

        if (!$availableEngagement) {
            return new JsonResponse([
                'canReview' => false,
                'reason' => 'all_reviewed',
                'message' => 'Alle Ihre Zusammenarbeiten mit diesem Anbieter wurden bereits bewertet.',
            ]);
        }

        return new JsonResponse([
            'canReview' => true,
            'engagementId' => $availableEngagement->getId(),
            'engagementTitle' => $availableEngagement->getOffering()?->getTitle(),
        ]);
    }

    /**
     * Submit a new review.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createReview(Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if (!$tenant) {
            return new JsonResponse(['error' => 'Tenant not found'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Validate required fields
        $providerId = $data['providerId'] ?? null;
        $overallRating = $data['overallRating'] ?? null;

        if (!$providerId || !$overallRating) {
            return new JsonResponse([
                'error' => 'providerId and overallRating are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $provider = $this->providerRepository->find($providerId);
        if (!$provider) {
            return new JsonResponse(['error' => 'Provider not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if already reviewed
        if ($this->reviewRepository->hasReviewedProvider($tenant, $provider)) {
            return new JsonResponse([
                'error' => 'Sie haben diesen Anbieter bereits bewertet.',
            ], Response::HTTP_CONFLICT);
        }

        // Find or validate engagement
        $engagement = null;
        $engagementId = $data['engagementId'] ?? null;
        if ($engagementId) {
            $engagement = $this->engagementRepository->find($engagementId);
            if (!$engagement || $engagement->getTenant() !== $tenant || $engagement->getProvider() !== $provider) {
                return new JsonResponse(['error' => 'Invalid engagement'], Response::HTTP_BAD_REQUEST);
            }
            if (!$engagement->isCompleted()) {
                return new JsonResponse([
                    'error' => 'Die Zusammenarbeit muss abgeschlossen sein, um eine Bewertung abzugeben.',
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            // Auto-find a completed engagement without a review
            $completedEngagements = $this->engagementRepository->findBy([
                'tenant' => $tenant,
                'provider' => $provider,
                'status' => PartnerEngagement::STATUS_COMPLETED,
            ]);
            foreach ($completedEngagements as $eng) {
                if (!$this->reviewRepository->findByEngagement($eng->getId())) {
                    $engagement = $eng;
                    break;
                }
            }
        }

        // Create review
        $review = new PartnerReview();
        $review->setTenant($tenant);
        $review->setProvider($provider);
        $review->setEngagement($engagement);
        $review->setAuthor($this->getUser());
        $review->setOverallRating((int) $overallRating);

        // Optional ratings
        if (isset($data['communicationRating'])) {
            $review->setCommunicationRating((int) $data['communicationRating']);
        }
        if (isset($data['qualityRating'])) {
            $review->setQualityRating((int) $data['qualityRating']);
        }
        if (isset($data['valueRating'])) {
            $review->setValueRating((int) $data['valueRating']);
        }
        if (isset($data['reliabilityRating'])) {
            $review->setReliabilityRating((int) $data['reliabilityRating']);
        }

        // Text content
        if (isset($data['title'])) {
            $review->setTitle($data['title']);
        }
        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }
        if (isset($data['pros']) && is_array($data['pros'])) {
            $review->setPros($data['pros']);
        }
        if (isset($data['cons']) && is_array($data['cons'])) {
            $review->setCons($data['cons']);
        }

        // Meta
        if (isset($data['serviceUsed'])) {
            $review->setServiceUsed($data['serviceUsed']);
        }
        if (isset($data['wouldRecommend'])) {
            $review->setWouldRecommend((bool) $data['wouldRecommend']);
        }
        if (isset($data['showCompanyName'])) {
            $review->setShowCompanyName((bool) $data['showCompanyName']);
        }

        // Auto-approve for now (can add moderation later)
        $review->approve();

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Vielen Dank für Ihre Bewertung!',
            'review' => $review->toArray(true),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update own review.
     */
    #[Route('/{reviewId}', name: 'update', methods: ['PATCH'], requirements: ['reviewId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function updateReview(int $reviewId, Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if (!$tenant) {
            return new JsonResponse(['error' => 'Tenant not found'], Response::HTTP_BAD_REQUEST);
        }

        $review = $this->reviewRepository->find($reviewId);
        if (!$review || $review->getTenant() !== $tenant) {
            return new JsonResponse(['error' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Update allowed fields
        if (isset($data['overallRating'])) {
            $review->setOverallRating((int) $data['overallRating']);
        }
        if (isset($data['communicationRating'])) {
            $review->setCommunicationRating((int) $data['communicationRating']);
        }
        if (isset($data['qualityRating'])) {
            $review->setQualityRating((int) $data['qualityRating']);
        }
        if (isset($data['valueRating'])) {
            $review->setValueRating((int) $data['valueRating']);
        }
        if (isset($data['reliabilityRating'])) {
            $review->setReliabilityRating((int) $data['reliabilityRating']);
        }
        if (isset($data['title'])) {
            $review->setTitle($data['title']);
        }
        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }
        if (isset($data['pros']) && is_array($data['pros'])) {
            $review->setPros($data['pros']);
        }
        if (isset($data['cons']) && is_array($data['cons'])) {
            $review->setCons($data['cons']);
        }
        if (isset($data['wouldRecommend'])) {
            $review->setWouldRecommend((bool) $data['wouldRecommend']);
        }
        if (isset($data['showCompanyName'])) {
            $review->setShowCompanyName((bool) $data['showCompanyName']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'review' => $review->toArray(true),
        ]);
    }

    /**
     * Delete own review.
     */
    #[Route('/{reviewId}', name: 'delete', methods: ['DELETE'], requirements: ['reviewId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function deleteReview(int $reviewId, Request $request): JsonResponse
    {
        $tenant = $this->getTenantFromRequest($request);
        if (!$tenant) {
            return new JsonResponse(['error' => 'Tenant not found'], Response::HTTP_BAD_REQUEST);
        }

        $review = $this->reviewRepository->find($reviewId);
        if (!$review || $review->getTenant() !== $tenant) {
            return new JsonResponse(['error' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($review);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Bewertung gelöscht.',
        ]);
    }

    // ========== Helper Methods ==========

    private function getTenantFromRequest(Request $request): ?Tenant
    {
        $tenantId = $request->headers->get('X-Tenant-ID');
        if (!$tenantId) {
            return null;
        }

        return $this->entityManager->getRepository(Tenant::class)->find($tenantId);
    }
}





