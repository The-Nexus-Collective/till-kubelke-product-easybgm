<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\InterventionParticipation;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * @extends ServiceEntityRepository<InterventionParticipation>
 */
class InterventionParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterventionParticipation::class);
    }

    /**
     * Find all participations for a tenant.
     * 
     * @return InterventionParticipation[]
     */
    public function findByTenant(Tenant $tenant): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('p.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find participations for a specific engagement.
     * 
     * @return InterventionParticipation[]
     */
    public function findByEngagement(PartnerEngagement $engagement): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.engagement = :engagement')
            ->setParameter('engagement', $engagement)
            ->orderBy('p.employeeName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find participations by employee email.
     * 
     * @return InterventionParticipation[]
     */
    public function findByEmployeeEmail(Tenant $tenant, string $email): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.employeeEmail = :email')
            ->setParameter('tenant', $tenant)
            ->setParameter('email', $email)
            ->orderBy('p.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find participations by category within a date range.
     * 
     * @return InterventionParticipation[]
     */
    public function findByCategoryAndDateRange(
        Tenant $tenant,
        string $category,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.category = :category')
            ->andWhere('p.eventDate >= :from')
            ->andWhere('p.eventDate <= :to')
            ->setParameter('tenant', $tenant)
            ->setParameter('category', $category)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('p.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ========== Aggregation Methods (for partner reports) ==========

    /**
     * Get aggregated stats for an engagement (NO personal data!).
     */
    public function getAggregatedStatsForEngagement(PartnerEngagement $engagement): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'COUNT(p.id) as totalRegistered',
                'SUM(CASE WHEN p.status = :attended THEN 1 ELSE 0 END) as totalAttended',
                'SUM(CASE WHEN p.status = :noShow THEN 1 ELSE 0 END) as totalNoShow',
                'SUM(CASE WHEN p.status = :cancelled THEN 1 ELSE 0 END) as totalCancelled',
                'AVG(p.rating) as averageRating',
                'COUNT(p.rating) as ratingCount',
            ])
            ->andWhere('p.engagement = :engagement')
            ->setParameter('engagement', $engagement)
            ->setParameter('attended', InterventionParticipation::STATUS_ATTENDED)
            ->setParameter('noShow', InterventionParticipation::STATUS_NO_SHOW)
            ->setParameter('cancelled', InterventionParticipation::STATUS_CANCELLED);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'registeredCount' => (int) $result['totalRegistered'],
            'attendedCount' => (int) $result['totalAttended'],
            'noShowCount' => (int) $result['totalNoShow'],
            'cancelledCount' => (int) $result['totalCancelled'],
            'attendanceRate' => $result['totalRegistered'] > 0 
                ? round((int) $result['totalAttended'] / (int) $result['totalRegistered'], 3) 
                : 0,
            'averageRating' => $result['averageRating'] !== null 
                ? round((float) $result['averageRating'], 1) 
                : null,
            'ratingCount' => (int) $result['ratingCount'],
        ];
    }

    /**
     * Get aggregated dietary requirements for an engagement.
     */
    public function getAggregatedDietaryRequirements(PartnerEngagement $engagement): array
    {
        $participations = $this->findByEngagement($engagement);
        
        $requirements = [];
        foreach ($participations as $p) {
            $reqs = $p->getSpecialRequirements() ?? [];
            foreach ($reqs as $req) {
                $requirements[$req] = ($requirements[$req] ?? 0) + 1;
            }
        }
        
        return $requirements;
    }

    /**
     * Get participation stats by category for a tenant.
     */
    public function getStatsByCategory(Tenant $tenant, int $year): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'p.category',
                'COUNT(p.id) as totalParticipations',
                'COUNT(DISTINCT p.employeeEmail) as uniqueParticipants',
            ])
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.status = :attended')
            ->andWhere('YEAR(p.eventDate) = :year')
            ->setParameter('tenant', $tenant)
            ->setParameter('attended', InterventionParticipation::STATUS_ATTENDED)
            ->setParameter('year', $year)
            ->groupBy('p.category');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get participation stats by department for a tenant.
     */
    public function getStatsByDepartment(Tenant $tenant, int $year): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'p.department',
                'COUNT(p.id) as totalParticipations',
                'COUNT(DISTINCT p.employeeEmail) as uniqueParticipants',
            ])
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.status = :attended')
            ->andWhere('YEAR(p.eventDate) = :year')
            ->andWhere('p.department IS NOT NULL')
            ->setParameter('tenant', $tenant)
            ->setParameter('attended', InterventionParticipation::STATUS_ATTENDED)
            ->setParameter('year', $year)
            ->groupBy('p.department');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get monthly participation trend for a tenant.
     */
    public function getMonthlyTrend(Tenant $tenant, int $year): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'MONTH(p.eventDate) as month',
                'COUNT(p.id) as totalParticipations',
                'COUNT(DISTINCT p.employeeEmail) as uniqueParticipants',
            ])
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.status = :attended')
            ->andWhere('YEAR(p.eventDate) = :year')
            ->setParameter('tenant', $tenant)
            ->setParameter('attended', InterventionParticipation::STATUS_ATTENDED)
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Count unique participants for a tenant in a given year.
     */
    public function countUniqueParticipants(Tenant $tenant, int $year): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.employeeEmail)')
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.status = :attended')
            ->andWhere('YEAR(p.eventDate) = :year')
            ->setParameter('tenant', $tenant)
            ->setParameter('attended', InterventionParticipation::STATUS_ATTENDED)
            ->setParameter('year', $year);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get top participants for a tenant (employees who participated most).
     * Returns aggregated data - useful for internal reporting.
     */
    public function getTopParticipants(Tenant $tenant, int $year, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'p.employeeEmail',
                'p.employeeName',
                'p.department',
                'COUNT(p.id) as participationCount',
            ])
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.status = :attended')
            ->andWhere('YEAR(p.eventDate) = :year')
            ->setParameter('tenant', $tenant)
            ->setParameter('attended', InterventionParticipation::STATUS_ATTENDED)
            ->setParameter('year', $year)
            ->groupBy('p.employeeEmail')
            ->addGroupBy('p.employeeName')
            ->addGroupBy('p.department')
            ->orderBy('participationCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}





