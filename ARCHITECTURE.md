# SaaS Platform Architecture - The Nexus Collective

## Vision

A modular SaaS platform under `github.com/The-Nexus-Collective/` with the prefix `till-kubelke-`. 
EasyBGM is the first product, but the architecture enables any number of additional business apps.

## 3-Layer Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER 3: PRODUCTS (Deployable Apps)                                │
│  ┌──────────────────────┐  ┌──────────────────────┐                │
│  │  EasyBGM             │  │  Future Product X    │                │
│  │  (Backend+Frontend)  │  │                      │                │
│  └──────────────────────┘  └──────────────────────┘                │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 2: REUSABLE MODULES                                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐               │
│  │  Survey  │ │   Chat   │ │ AI Buddy │ │    HR    │               │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘               │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 1: PLATFORM FOUNDATION                                       │
│  Auth │ Tenant │ Billing │ Notifications │ Settings │ Encryption   │
└─────────────────────────────────────────────────────────────────────┘
```

## GitHub Repository Structure (15 Repos)

### Layer 1: Platform Foundation
- till-kubelke-platform-foundation (Symfony Bundle)
- till-kubelke-platform-ui (React Package)

### Layer 2: Reusable Modules
- till-kubelke-module-survey / till-kubelke-module-survey-ui
- till-kubelke-module-chat / till-kubelke-module-chat-ui
- till-kubelke-module-ai-buddy / till-kubelke-module-ai-buddy-ui
- till-kubelke-module-hr-integration / till-kubelke-module-hr-integration-ui

### Layer 3: Business Apps
- till-kubelke-app-easybgm (Symfony Bundle)
- till-kubelke-app-easybgm-ui (React Package)

### Products (Deployables)
- till-kubelke-product-easybgm-backend
- till-kubelke-product-easybgm-frontend
- till-kubelke-product-easybgm-mobile

## Dependency Rules

- Layer 3 (Apps) → may use Layer 2 + Layer 1
- Layer 2 (Modules) → may ONLY use Layer 1
- Layer 1 (Foundation) → no dependencies upward
- Modules must NOT depend on each other

## Technology Rules

- **Database:** PostgreSQL (NOT MySQL) - All database configurations, migrations, and queries must use PostgreSQL

## Governance Model

See [GOVERNANCE.md](./GOVERNANCE.md) for the complete bidirectional governance model:
- **Top-Down (Use):** Products consume bundles
- **Bottom-Up (Elevate):** Code is refactored upward when patterns emerge
- **Three-Strike Rule:** 1x Product → 2x App → 3x Module/Foundation

## Source

Extracted from: /Users/till.kubelkesportalliance.com/Develop/bgm-portal/

Complete plan see: .cursor/plans/symfony_bundle_modularisierung_*.plan.md
