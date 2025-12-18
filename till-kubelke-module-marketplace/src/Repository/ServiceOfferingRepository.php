<?php

namespace TillKubelke\ModuleMarketplace\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;

/**
 * @extends ServiceEntityRepository<ServiceOffering>
 */
class ServiceOfferingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceOffering::class);
    }

    /**
     * Find all active offerings for a provider.
     * 
     * @return ServiceOffering[]
     */
    public function findActiveByProvider(ServiceProvider $provider): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.provider = :provider')
            ->andWhere('o.isActive = :active')
            ->setParameter('provider', $provider)
            ->setParameter('active', true)
            ->orderBy('o.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find offerings relevant for a specific BGM phase.
     * 
     * @return ServiceOffering[]
     */
    public function findByRelevantPhase(int $phase): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isActive = :active')
            ->andWhere('JSON_CONTAINS(o.relevantPhases, :phase) = 1')
            ->setParameter('active', true)
            ->setParameter('phase', json_encode($phase))
            ->orderBy('o.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find certified offerings.
     * 
     * @return ServiceOffering[]
     */
    public function findCertified(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isActive = :active')
            ->andWhere('o.isCertified = :certified')
            ->setParameter('active', true)
            ->setParameter('certified', true)
            ->orderBy('o.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orchestrator services (like health day planners).
     * 
     * @return ServiceOffering[]
     */
    public function findOrchestrators(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isActive = :active')
            ->andWhere('o.isOrchestratorService = :orchestrator')
            ->setParameter('active', true)
            ->setParameter('orchestrator', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find offerings that integrate at a specific point.
     * 
     * @return ServiceOffering[]
     */
    public function findByIntegrationPoint(string $integrationPoint): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isActive = :active')
            ->andWhere('JSON_CONTAINS(o.integrationPoints, :point) = 1')
            ->setParameter('active', true)
            ->setParameter('point', json_encode($integrationPoint))
            ->getQuery()
            ->getResult();
    }

    /**
     * Find offerings that deliver a specific output type.
     * 
     * @return ServiceOffering[]
     */
    public function findByOutputType(string $outputType): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isActive = :active')
            ->andWhere('JSON_CONTAINS(o.outputDataTypes, :type) = 1')
            ->setParameter('active', true)
            ->setParameter('type', json_encode($outputType))
            ->getQuery()
            ->getResult();
    }

    /**
     * Search offerings by title and description.
     * 
     * @return ServiceOffering[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isActive = :active')
            ->andWhere('o.title LIKE :query OR o.description LIKE :query')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('o.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}


