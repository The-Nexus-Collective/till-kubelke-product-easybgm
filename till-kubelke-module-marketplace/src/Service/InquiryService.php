<?php

namespace TillKubelke\ModuleMarketplace\Service;

use Doctrine\ORM\EntityManagerInterface;
use TillKubelke\ModuleMarketplace\Entity\ServiceInquiry;
use TillKubelke\ModuleMarketplace\Entity\ServiceOffering;
use TillKubelke\ModuleMarketplace\Entity\ServiceProvider;
use TillKubelke\ModuleMarketplace\Repository\ServiceInquiryRepository;
use TillKubelke\PlatformFoundation\Notification\Service\NotificationService;
use TillKubelke\PlatformFoundation\Tenant\Entity\Tenant;

class InquiryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceInquiryRepository $inquiryRepository,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /**
     * Create a new inquiry from a tenant to a provider.
     */
    public function createInquiry(
        Tenant $tenant,
        ServiceProvider $provider,
        array $data,
        ?ServiceOffering $offering = null,
        ?int $bgmProjectId = null,
    ): ServiceInquiry {
        $inquiry = new ServiceInquiry();
        $inquiry->setTenant($tenant);
        $inquiry->setProvider($provider);
        $inquiry->setOffering($offering);
        $inquiry->setBgmProjectId($bgmProjectId);
        $inquiry->setContactName($data['contactName']);
        $inquiry->setContactEmail($data['contactEmail']);
        $inquiry->setContactPhone($data['contactPhone'] ?? null);
        $inquiry->setMessage($data['message']);
        $inquiry->setMetadata($data['metadata'] ?? null);

        $this->entityManager->persist($inquiry);
        $this->entityManager->flush();

        // Send notification to provider (if notification service is available)
        $this->notifyProviderOfNewInquiry($inquiry);

        return $inquiry;
    }

    /**
     * Update inquiry status.
     */
    public function updateStatus(ServiceInquiry $inquiry, string $status, ?string $notes = null): ServiceInquiry
    {
        $inquiry->setStatus($status);
        
        if ($notes !== null) {
            $inquiry->setProviderNotes($notes);
        }

        $this->entityManager->flush();

        // Notify tenant of status change
        $this->notifyTenantOfStatusChange($inquiry);

        return $inquiry;
    }

    /**
     * Mark inquiry as contacted.
     */
    public function markAsContacted(ServiceInquiry $inquiry, ?string $notes = null): ServiceInquiry
    {
        return $this->updateStatus($inquiry, ServiceInquiry::STATUS_CONTACTED, $notes);
    }

    /**
     * Mark inquiry as in progress.
     */
    public function markAsInProgress(ServiceInquiry $inquiry, ?string $notes = null): ServiceInquiry
    {
        return $this->updateStatus($inquiry, ServiceInquiry::STATUS_IN_PROGRESS, $notes);
    }

    /**
     * Mark inquiry as completed.
     */
    public function markAsCompleted(ServiceInquiry $inquiry, ?string $notes = null): ServiceInquiry
    {
        return $this->updateStatus($inquiry, ServiceInquiry::STATUS_COMPLETED, $notes);
    }

    /**
     * Mark inquiry as declined.
     */
    public function markAsDeclined(ServiceInquiry $inquiry, ?string $notes = null): ServiceInquiry
    {
        return $this->updateStatus($inquiry, ServiceInquiry::STATUS_DECLINED, $notes);
    }

    /**
     * Get inquiries for a tenant.
     *
     * @return ServiceInquiry[]
     */
    public function getInquiriesForTenant(Tenant $tenant, int $limit = 50, int $offset = 0): array
    {
        return $this->inquiryRepository->findByTenant($tenant, $limit, $offset);
    }

    /**
     * Get inquiries for a provider.
     *
     * @return ServiceInquiry[]
     */
    public function getInquiriesForProvider(
        ServiceProvider $provider,
        ?string $status = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->inquiryRepository->findByProvider($provider, $status, $limit, $offset);
    }

    /**
     * Count new inquiries for a provider.
     */
    public function countNewInquiriesForProvider(ServiceProvider $provider): int
    {
        return $this->inquiryRepository->countNewByProvider($provider);
    }

    /**
     * Get inquiries linked to a BGM project.
     *
     * @return ServiceInquiry[]
     */
    public function getInquiriesForBgmProject(int $bgmProjectId): array
    {
        return $this->inquiryRepository->findByBgmProject($bgmProjectId);
    }

    private function notifyProviderOfNewInquiry(ServiceInquiry $inquiry): void
    {
        if ($this->notificationService === null) {
            return;
        }

        // Note: In a real implementation, we would need a way to associate
        // a provider with users who should receive notifications.
        // For now, this is a placeholder for future implementation.
    }

    private function notifyTenantOfStatusChange(ServiceInquiry $inquiry): void
    {
        if ($this->notificationService === null) {
            return;
        }

        // Note: Notification would be sent to relevant tenant users
        // based on the inquiry status change.
    }
}





