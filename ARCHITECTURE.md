# SaaS Platform Architektur - The Nexus Collective

## Vision

Eine modulare SaaS-Plattform unter `github.com/The-Nexus-Collective/` mit dem Prefix `till-kubelke-`. 
EasyBGM ist das erste Produkt, aber die Architektur ermöglicht beliebige weitere Business-Apps.

## 3-Schichten-Architektur

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

## GitHub Repository-Struktur (15 Repos)

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

- Layer 3 (Apps) → darf Layer 2 + Layer 1 nutzen
- Layer 2 (Modules) → darf NUR Layer 1 nutzen
- Layer 1 (Foundation) → keine Abhängigkeiten nach oben
- Module dürfen NICHT voneinander abhängen

## Governance Model

Siehe [GOVERNANCE.md](./GOVERNANCE.md) für das vollständige bidirektionale Governance-Modell:
- **Top-Down (Use):** Products konsumieren Bundles
- **Bottom-Up (Elevate):** Code wird nach oben refactored wenn Patterns entstehen
- **Three-Strike Rule:** 1x Product → 2x App → 3x Module/Foundation

## Quelle

Extrahiert aus: /Users/till.kubelkesportalliance.com/Develop/bgm-portal/

Vollständiger Plan siehe: .cursor/plans/symfony_bundle_modularisierung_*.plan.md
