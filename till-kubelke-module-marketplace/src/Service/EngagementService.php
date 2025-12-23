<?php

namespace TillKubelke\ModuleMarketplace\Service;

use Doctrine\ORM\EntityManagerInterface;
use TillKubelke\ModuleMarketplace\Entity\PartnerEngagement;
use TillKubelke\ModuleMarketplace\Entity\ServiceInquiry;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Registry\DataScopeRegistry;
use TillKubelke\ModuleMarketplace\Registry\OutputTypeRegistry;
use TillKubelke\ModuleMarketplace\Repository\PartnerEngagementRepository;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

/**
 * EngagementService - Business logic for partner engagement workflow.
 * 
 * Handles the full lifecycle of partner engagements:
 * - Creating engagements from inquiries
 * - Managing data scope grants
 * - Processing deliverables
 * - Status transitions
 */
class EngagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PartnerEngagementRepository $engagementRepository,
    ) {}

    // ========== Engagement Lifecycle ==========

    /**
     * Create a new engagement from an accepted inquiry.
     */
    public function createFromInquiry(ServiceInquiry $inquiry): PartnerEngagement
    {
        $engagement = new PartnerEngagement();
        $engagement->setTenant($inquiry->getTenant());
        $engagement->setProvider($inquiry->getProvider());
        $engagement->setOffering($inquiry->getOffering());
        $engagement->setInquiry($inquiry);

        // Copy contact info from inquiry
        $engagement->setPartnerContactName($inquiry->getProvider()->getContactPerson());
        $engagement->setPartnerContactEmail($inquiry->getProvider()->getContactEmail());

        $this->entityManager->persist($engagement);
        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Create a new engagement directly (without inquiry).
     */
    public function create(
        Tenant $tenant,
        ServiceProvider $provider,
        ServiceOffering $offering,
        ?array $agreedPricing = null,
        ?\DateTimeInterface $scheduledDate = null
    ): PartnerEngagement {
        $engagement = new PartnerEngagement();
        $engagement->setTenant($tenant);
        $engagement->setProvider($provider);
        $engagement->setOffering($offering);
        $engagement->setAgreedPricing($agreedPricing);
        $engagement->setScheduledDate($scheduledDate);

        // Set partner contact from provider
        $engagement->setPartnerContactName($provider->getContactPerson());
        $engagement->setPartnerContactEmail($provider->getContactEmail());
        $engagement->setPartnerContactPhone($provider->getContactPhone());

        $this->entityManager->persist($engagement);
        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Activate an engagement (move from draft to active).
     */
    public function activate(PartnerEngagement $engagement): PartnerEngagement
    {
        if (!$engagement->isDraft()) {
            throw new \InvalidArgumentException('Can only activate draft engagements');
        }

        $engagement->activate();
        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Cancel an engagement.
     */
    public function cancel(PartnerEngagement $engagement): PartnerEngagement
    {
        if ($engagement->isCompleted() || $engagement->isCancelled()) {
            throw new \InvalidArgumentException('Cannot cancel completed or already cancelled engagement');
        }

        $engagement->cancel();
        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Complete an engagement.
     */
    public function complete(PartnerEngagement $engagement): PartnerEngagement
    {
        if (!$engagement->isDelivered()) {
            throw new \InvalidArgumentException('Can only complete delivered engagements');
        }

        $engagement->complete();
        $this->entityManager->flush();

        return $engagement;
    }

    // ========== Data Scope Management ==========

    /**
     * Grant data access for specific scopes.
     * 
     * @param string[] $scopes Scope keys to grant
     * @return array Validation result
     */
    public function grantDataScopes(PartnerEngagement $engagement, array $scopes): array
    {
        $requiredScopes = $engagement->getOffering()->getRequiredDataScopes() ?? [];
        $invalidScopes = DataScopeRegistry::validateScopes($scopes);
        $unrequestedScopes = array_diff($scopes, $requiredScopes);

        if (!empty($invalidScopes)) {
            return [
                'success' => false,
                'error' => 'Invalid scopes: ' . implode(', ', $invalidScopes),
            ];
        }

        if (!empty($unrequestedScopes)) {
            return [
                'success' => false,
                'error' => 'Scopes not requested by offering: ' . implode(', ', $unrequestedScopes),
            ];
        }

        foreach ($scopes as $scope) {
            $engagement->grantDataScope($scope);
        }

        // Check if all required scopes are now granted
        $allGranted = empty(array_diff($requiredScopes, $engagement->getGrantedScopeKeys()));
        if ($allGranted && $engagement->isActive()) {
            $engagement->markDataShared();
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'grantedScopes' => $engagement->getGrantedScopeKeys(),
            'allRequiredGranted' => $allGranted,
        ];
    }

    /**
     * Revoke data access for a scope.
     */
    public function revokeDataScope(PartnerEngagement $engagement, string $scope): array
    {
        if (!$engagement->hasGrantedScope($scope)) {
            return [
                'success' => false,
                'error' => 'Scope not granted: ' . $scope,
            ];
        }

        $engagement->revokeDataScope($scope);
        $this->entityManager->flush();

        return [
            'success' => true,
            'revokedScope' => $scope,
            'remainingScopes' => $engagement->getGrantedScopeKeys(),
        ];
    }

    /**
     * Get data access status for an engagement.
     */
    public function getDataAccessStatus(PartnerEngagement $engagement): array
    {
        $requiredScopes = $engagement->getOffering()->getRequiredDataScopes() ?? [];
        $grantedScopes = $engagement->getGrantedScopeKeys();

        $scopeStatus = [];
        foreach ($requiredScopes as $scope) {
            $scopeInfo = DataScopeRegistry::get($scope);
            $scopeStatus[$scope] = [
                'label' => $scopeInfo['label'] ?? $scope,
                'description' => $scopeInfo['description'] ?? '',
                'sensitivity' => $scopeInfo['sensitivity'] ?? 'unknown',
                'granted' => in_array($scope, $grantedScopes, true),
            ];
        }

        return [
            'requiredScopes' => $scopeStatus,
            'allGranted' => empty(array_diff($requiredScopes, $grantedScopes)),
            'grantedCount' => count($grantedScopes),
            'requiredCount' => count($requiredScopes),
        ];
    }

    // ========== Deliverables Management ==========

    /**
     * Record a delivered output from the partner.
     */
    public function recordDelivery(
        PartnerEngagement $engagement,
        string $outputType,
        array $data
    ): array {
        // Validate output type
        if (!OutputTypeRegistry::exists($outputType)) {
            return [
                'success' => false,
                'error' => 'Unknown output type: ' . $outputType,
            ];
        }

        // Check if this output type is expected from this offering
        $expectedOutputs = $engagement->getOffering()->getOutputDataTypes() ?? [];
        if (!empty($expectedOutputs) && !in_array($outputType, $expectedOutputs, true)) {
            return [
                'success' => false,
                'error' => 'Output type not expected from this offering: ' . $outputType,
            ];
        }

        $engagement->addDeliveredOutput($outputType, $data);

        // Update status if this is the first delivery
        if ($engagement->isProcessing() || $engagement->isDataShared()) {
            $engagement->markDelivered();
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'outputType' => $outputType,
            'integrationPoint' => OutputTypeRegistry::getIntegrationPoint($outputType),
            'deliveredOutputs' => array_keys($engagement->getDeliveredOutputs() ?? []),
        ];
    }

    /**
     * Mark the partner as processing (working on deliverables).
     */
    public function markProcessing(PartnerEngagement $engagement): PartnerEngagement
    {
        if (!$engagement->isDataShared() && !$engagement->isActive()) {
            throw new \InvalidArgumentException('Can only mark as processing after data is shared or engagement is active');
        }

        $engagement->markProcessing();
        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Mark an output as integrated into the BGM process.
     */
    public function markOutputIntegrated(
        PartnerEngagement $engagement,
        string $outputType
    ): array {
        if (!$engagement->hasDeliveredOutput($outputType)) {
            return [
                'success' => false,
                'error' => 'Output not delivered: ' . $outputType,
            ];
        }

        $integrationPoint = OutputTypeRegistry::getIntegrationPoint($outputType);
        if ($integrationPoint === null) {
            return [
                'success' => false,
                'error' => 'No integration point defined for: ' . $outputType,
            ];
        }

        $engagement->markOutputIntegrated($outputType, $integrationPoint);
        $this->entityManager->flush();

        return [
            'success' => true,
            'outputType' => $outputType,
            'integrationPoint' => $integrationPoint,
        ];
    }

    // ========== Queries ==========

    /**
     * Get all active engagements for a tenant.
     * 
     * @return PartnerEngagement[]
     */
    public function getActiveEngagements(Tenant $tenant): array
    {
        return $this->engagementRepository->findActiveByTenant($tenant);
    }

    /**
     * Get all engagements for a tenant.
     * 
     * @return PartnerEngagement[]
     */
    public function getAllEngagements(Tenant $tenant): array
    {
        return $this->engagementRepository->findByTenant($tenant);
    }

    /**
     * Get engagement by ID (with tenant validation).
     */
    public function getEngagement(Tenant $tenant, int $engagementId): ?PartnerEngagement
    {
        $engagement = $this->engagementRepository->find($engagementId);

        if ($engagement === null) {
            return null;
        }

        // Security: Validate tenant ownership
        if ($engagement->getTenant()?->getId() !== $tenant->getId()) {
            return null;
        }

        return $engagement;
    }

    /**
     * Get engagements with pending deliveries.
     * 
     * @return PartnerEngagement[]
     */
    public function getEngagementsWithPendingIntegration(Tenant $tenant): array
    {
        return $this->engagementRepository->findWithPendingIntegration($tenant);
    }

    /**
     * Count active engagements for a tenant.
     */
    public function countActiveEngagements(Tenant $tenant): int
    {
        return $this->engagementRepository->countActiveByTenant($tenant);
    }

    // ========== Partner Contact ==========

    /**
     * Update partner contact information.
     */
    public function updatePartnerContact(
        PartnerEngagement $engagement,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null
    ): PartnerEngagement {
        if ($name !== null) {
            $engagement->setPartnerContactName($name);
        }
        if ($email !== null) {
            $engagement->setPartnerContactEmail($email);
        }
        if ($phone !== null) {
            $engagement->setPartnerContactPhone($phone);
        }

        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Update customer notes (internal, not visible to partner).
     */
    public function updateCustomerNotes(PartnerEngagement $engagement, string $notes): PartnerEngagement
    {
        $engagement->setCustomerNotes($notes);
        $this->entityManager->flush();

        return $engagement;
    }

    /**
     * Update partner notes (visible to customer).
     */
    public function updatePartnerNotes(PartnerEngagement $engagement, string $notes): PartnerEngagement
    {
        $engagement->setPartnerNotes($notes);
        $this->entityManager->flush();

        return $engagement;
    }
}





