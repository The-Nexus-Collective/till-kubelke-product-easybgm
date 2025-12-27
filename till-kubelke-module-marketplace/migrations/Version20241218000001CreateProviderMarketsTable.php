<?php

declare(strict_types=1);

namespace TillKubelke\ModuleMarketplace\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the marketplace_provider_markets join table.
 * 
 * This migration depends on foundation_markets table existing.
 */
final class Version20241218000001CreateProviderMarketsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create marketplace_provider_markets join table for ServiceProvider-Market relationship';
    }

    public function up(Schema $schema): void
    {
        // Create join table
        $this->addSql('
            CREATE TABLE marketplace_provider_markets (
                service_provider_id INTEGER NOT NULL,
                market_code VARCHAR(2) NOT NULL,
                PRIMARY KEY(service_provider_id, market_code)
            )
        ');

        // Add foreign key to service providers
        $this->addSql('
            ALTER TABLE marketplace_provider_markets 
            ADD CONSTRAINT fk_provider_markets_provider 
            FOREIGN KEY (service_provider_id) 
            REFERENCES marketplace_service_providers(id) 
            ON DELETE CASCADE
        ');

        // Add foreign key to markets
        $this->addSql('
            ALTER TABLE marketplace_provider_markets 
            ADD CONSTRAINT fk_provider_markets_market 
            FOREIGN KEY (market_code) 
            REFERENCES foundation_markets(code) 
            ON DELETE CASCADE
        ');

        // Add index for market lookups
        $this->addSql('CREATE INDEX idx_provider_markets_market ON marketplace_provider_markets(market_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE marketplace_provider_markets');
    }
}





