<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;

/**
 * @extends ServiceEntityRepository<ServiceProvider>
 */
class ServiceProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceProvider::class);
    }

    /**
     * Find approved providers with optional filters.
     *
     * @param array<string> $categoryIds
     * @param array<string> $tagIds
     * @param string|null $marketCode Filter by market code (e.g., 'DE', 'AT', 'CH')
     */
    public function findApprovedProviders(
        array $categoryIds = [],
        array $tagIds = [],
        ?string $search = null,
        bool $nationwideOnly = false,
        bool $remoteOnly = false,
        bool $certifiedOnly = false,
        ?string $marketCode = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', ServiceProvider::STATUS_APPROVED)
            ->orderBy('p.companyName', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if (!empty($categoryIds)) {
            $qb->innerJoin('p.categories', 'c')
                ->andWhere('c.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        if (!empty($tagIds)) {
            $qb->innerJoin('p.tags', 't')
                ->andWhere('t.id IN (:tagIds)')
                ->setParameter('tagIds', $tagIds);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('(p.companyName LIKE :search OR p.description LIKE :search OR p.shortDescription LIKE :search)')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($nationwideOnly) {
            $qb->andWhere('p.isNationwide = :nationwide')
                ->setParameter('nationwide', true);
        }

        if ($remoteOnly) {
            $qb->andWhere('p.offersRemote = :remote')
                ->setParameter('remote', true);
        }

        if ($certifiedOnly) {
            // Filter for providers with at least one certified offering (§20 SGB V)
            $qb->innerJoin('p.offerings', 'o')
                ->andWhere('o.isCertified = :certified')
                ->setParameter('certified', true);
        }

        if ($marketCode !== null && $marketCode !== '') {
            // Filter by market - provider must operate in this market
            $qb->innerJoin('p.markets', 'm')
                ->andWhere('m.code = :marketCode')
                ->setParameter('marketCode', strtoupper($marketCode));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count approved providers with optional filters.
     * 
     * @param string|null $marketCode Filter by market code (e.g., 'DE', 'AT', 'CH')
     */
    public function countApprovedProviders(
        array $categoryIds = [],
        array $tagIds = [],
        ?string $search = null,
        bool $nationwideOnly = false,
        bool $remoteOnly = false,
        bool $certifiedOnly = false,
        ?string $marketCode = null
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.id)')
            ->where('p.status = :status')
            ->setParameter('status', ServiceProvider::STATUS_APPROVED);

        if (!empty($categoryIds)) {
            $qb->innerJoin('p.categories', 'c')
                ->andWhere('c.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        if (!empty($tagIds)) {
            $qb->innerJoin('p.tags', 't')
                ->andWhere('t.id IN (:tagIds)')
                ->setParameter('tagIds', $tagIds);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('(p.companyName LIKE :search OR p.description LIKE :search OR p.shortDescription LIKE :search)')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($nationwideOnly) {
            $qb->andWhere('p.isNationwide = :nationwide')
                ->setParameter('nationwide', true);
        }

        if ($remoteOnly) {
            $qb->andWhere('p.offersRemote = :remote')
                ->setParameter('remote', true);
        }

        if ($certifiedOnly) {
            // Filter for providers with at least one certified offering (§20 SGB V)
            $qb->innerJoin('p.offerings', 'o')
                ->andWhere('o.isCertified = :certified')
                ->setParameter('certified', true);
        }

        if ($marketCode !== null && $marketCode !== '') {
            // Filter by market - provider must operate in this market
            $qb->innerJoin('p.markets', 'm')
                ->andWhere('m.code = :marketCode')
                ->setParameter('marketCode', strtoupper($marketCode));
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find pending providers for admin approval.
     */
    public function findPendingProviders(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', ServiceProvider::STATUS_PENDING)
            ->orderBy('p.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count pending providers.
     */
    public function countPendingProviders(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', ServiceProvider::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find providers by category slugs for BGM phase integration.
     * 
     * @param array<string> $categorySlugs
     * @param array<string> $tagSlugs
     */
    public function findByCategorySlugsAndTagSlugs(
        array $categorySlugs = [],
        array $tagSlugs = [],
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', ServiceProvider::STATUS_APPROVED)
            ->setMaxResults($limit);

        if (!empty($categorySlugs)) {
            $qb->innerJoin('p.categories', 'c')
                ->andWhere('c.slug IN (:categorySlugs)')
                ->setParameter('categorySlugs', $categorySlugs);
        }

        if (!empty($tagSlugs)) {
            $qb->innerJoin('p.tags', 't')
                ->andWhere('t.slug IN (:tagSlugs)')
                ->setParameter('tagSlugs', $tagSlugs);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(ServiceProvider $provider, bool $flush = false): void
    {
        $this->getEntityManager()->persist($provider);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ServiceProvider $provider, bool $flush = false): void
    {
        $this->getEntityManager()->remove($provider);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}






