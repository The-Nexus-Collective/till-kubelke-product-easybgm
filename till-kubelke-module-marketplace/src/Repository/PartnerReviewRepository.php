<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\PartnerReview;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * @extends ServiceEntityRepository<PartnerReview>
 */
class PartnerReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartnerReview::class);
    }

    /**
     * Find all approved reviews for a provider.
     *
     * @return PartnerReview[]
     */
    public function findApprovedByProvider(ServiceProvider $provider, int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.provider = :provider')
            ->andWhere('r.status = :status')
            ->setParameter('provider', $provider)
            ->setParameter('status', PartnerReview::STATUS_APPROVED)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all reviews for a provider (for admin).
     *
     * @return PartnerReview[]
     */
    public function findAllByProvider(ServiceProvider $provider): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews created by a tenant.
     *
     * @return PartnerReview[]
     */
    public function findByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find review by engagement ID.
     */
    public function findByEngagement(int $engagementId): ?PartnerReview
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.engagement = :engagementId')
            ->setParameter('engagementId', $engagementId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if tenant has already reviewed a provider.
     */
    public function hasReviewedProvider(Tenant $tenant, ServiceProvider $provider): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.tenant = :tenant')
            ->andWhere('r.provider = :provider')
            ->setParameter('tenant', $tenant)
            ->setParameter('provider', $provider)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Get rating aggregates for a provider.
     */
    public function getProviderRatingStats(ServiceProvider $provider): array
    {
        $result = $this->createQueryBuilder('r')
            ->select([
                'COUNT(r.id) as reviewCount',
                'AVG(r.overallRating) as averageRating',
                'AVG(r.communicationRating) as avgCommunication',
                'AVG(r.qualityRating) as avgQuality',
                'AVG(r.valueRating) as avgValue',
                'AVG(r.reliabilityRating) as avgReliability',
                'SUM(CASE WHEN r.wouldRecommend = true THEN 1 ELSE 0 END) as recommendCount',
            ])
            ->andWhere('r.provider = :provider')
            ->andWhere('r.status = :status')
            ->setParameter('provider', $provider)
            ->setParameter('status', PartnerReview::STATUS_APPROVED)
            ->getQuery()
            ->getSingleResult();

        $reviewCount = (int) $result['reviewCount'];

        return [
            'reviewCount' => $reviewCount,
            'averageRating' => $reviewCount > 0 ? round((float) $result['averageRating'], 1) : null,
            'avgCommunication' => $reviewCount > 0 ? round((float) $result['avgCommunication'], 1) : null,
            'avgQuality' => $reviewCount > 0 ? round((float) $result['avgQuality'], 1) : null,
            'avgValue' => $reviewCount > 0 ? round((float) $result['avgValue'], 1) : null,
            'avgReliability' => $reviewCount > 0 ? round((float) $result['avgReliability'], 1) : null,
            'recommendRate' => $reviewCount > 0 
                ? round((int) $result['recommendCount'] / $reviewCount * 100, 0) 
                : null,
        ];
    }

    /**
     * Get rating distribution for a provider.
     */
    public function getProviderRatingDistribution(ServiceProvider $provider): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.overallRating as rating', 'COUNT(r.id) as count')
            ->andWhere('r.provider = :provider')
            ->andWhere('r.status = :status')
            ->setParameter('provider', $provider)
            ->setParameter('status', PartnerReview::STATUS_APPROVED)
            ->groupBy('r.overallRating')
            ->getQuery()
            ->getResult();

        // Initialize distribution
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($results as $row) {
            $distribution[(int) $row['rating']] = (int) $row['count'];
        }

        return $distribution;
    }

    /**
     * Get pending reviews for moderation.
     *
     * @return PartnerReview[]
     */
    public function findPendingReviews(int $limit = 50): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', PartnerReview::STATUS_PENDING)
            ->orderBy('r.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending reviews.
     */
    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->setParameter('status', PartnerReview::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}







