# Module Marketplace

BGM Service Provider Directory and Inquiry System for the Nexus Platform.

## Features

- **Service Provider Catalog**: Browse and filter BGM service providers by category and tags
- **Provider Registration**: Self-service registration with admin approval workflow
- **Inquiry System**: Send inquiries to service providers directly from the platform
- **BGM Integration**: Context-aware provider suggestions in BGM project phases

## Categories

- Bewegung (Movement)
- Ernährung (Nutrition)
- Mentale Gesundheit (Mental Health)
- Suchtprävention (Addiction Prevention)
- Ergonomie (Ergonomics)

## Installation

```bash
composer require till-kubelke/module-marketplace
```

## Architecture

This module is part of Layer 2 (Reusable Modules) and depends on:
- `till-kubelke/platform-foundation` (Layer 1)

## Entities

- `ServiceProvider` - BGM service provider companies
- `ServiceOffering` - Specific services offered by providers
- `ServiceInquiry` - Inquiry requests from tenants to providers
- `Category` - Main service categories
- `Tag` - Fine-grained service tags




