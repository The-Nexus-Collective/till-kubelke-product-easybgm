# 🚀 Nexus Platform - Modulare SaaS Architektur

Eine modulare SaaS-Plattform unter `github.com/The-Nexus-Collective/` mit dem Prefix `till-kubelke-`.

## 📐 3-Schichten-Architektur

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

## 📦 Repositories

### Layer 1: Platform Foundation
| Repository | Type | Files | Description |
|------------|------|-------|-------------|
| [platform-foundation](https://github.com/The-Nexus-Collective/till-kubelke-platform-foundation) | Symfony Bundle | 33 | Auth, Tenant, Billing, Security |
| [platform-ui](https://github.com/The-Nexus-Collective/till-kubelke-platform-ui) | React Package | 41 | Shared Hooks, Components, Types |

### Layer 2: Reusable Modules
| Repository | Type | Files | Description |
|------------|------|-------|-------------|
| [module-survey](https://github.com/The-Nexus-Collective/till-kubelke-module-survey) | Symfony Bundle | 16 | Survey, COPSOQ, GPAQ |
| [module-chat](https://github.com/The-Nexus-Collective/till-kubelke-module-chat) | Symfony Bundle | 10 | Chat Conversations |
| [module-ai-buddy](https://github.com/The-Nexus-Collective/till-kubelke-module-ai-buddy) | Symfony Bundle | 15 | ChatGPT, Claude, Gemini, Grok |
| [module-hr-integration](https://github.com/The-Nexus-Collective/till-kubelke-module-hr-integration) | Symfony Bundle | 9 | Personio, DATEV, RexxHR |

### Layer 3: Business Apps
| Repository | Type | Files | Description |
|------------|------|-------|-------------|
| [app-easybgm](https://github.com/The-Nexus-Collective/till-kubelke-app-easybgm) | Symfony Bundle | 36 | BGM 6-Phasen Business Logic |

### Products (Deployable)
| Repository | Type | Description |
|------------|------|-------------|
| [product-easybgm-backend](https://github.com/The-Nexus-Collective/till-kubelke-product-easybgm-backend) | Symfony App | Production Backend |
| [product-easybgm-frontend](https://github.com/The-Nexus-Collective/till-kubelke-product-easybgm-frontend) | React App | Production Frontend |

## 🚀 Quick Start

```bash
# Clone
git clone https://github.com/The-Nexus-Collective/till-kubelke-product-easybgm-backend.git
git clone https://github.com/The-Nexus-Collective/till-kubelke-product-easybgm-frontend.git

# Start
./run.sh start

# URLs
# Frontend: http://localhost:8080
# Backend:  http://localhost:8000
# Mailpit:  http://localhost:8025
```

## 🔗 Dependency Rules

```
✅ ERLAUBT                    ❌ VERBOTEN
Product → App → Module → Found   Module → Module
App → Module                     Foundation → Module
App → Foundation                 Module → App
Module → Foundation              Foundation → App
```

## 🛠️ Tech Stack

**Backend:** Symfony 7.3, API Platform, Doctrine ORM, PostgreSQL
**Frontend:** React 19, Vite, MUI v7, SWR, TypeScript

## 📊 Statistics

- **Backend Bundles:** 119 PHP files
- **Frontend Package:** 41 TypeScript files
- **Tests:** 548 (90% passing)
- **Migration:** 88% complete

---

*"Modular code is maintainable code."* - Ryan & Leanna
