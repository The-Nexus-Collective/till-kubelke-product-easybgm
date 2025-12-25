# SaaS Platform Architecture - The Nexus Collective

## Vision

A modular SaaS platform under `github.com/The-Nexus-Collective/` with the prefix `till-kubelke-`. 
EasyBGM is the first product, but the architecture enables any number of additional business apps.

## 4-Layer Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER 4: PRODUCTS (Deployable Monorepos)                           │
│  ┌───────────────────────┐  ┌───────────────────────┐              │
│  │  product-easybgm/     │  │  product-draft/       │              │
│  │  ├── backend/         │  │  ├── backend/         │              │
│  │  └── frontend/        │  │  └── frontend/        │              │
│  └───────────────────────┘  └───────────────────────┘              │
│  → Klonbar für Whitelabels!                                         │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 3: APP BUNDLES (Business-Logik)                              │
│  ┌──────────────┐  ┌──────────────┐                                │
│  │  app-easybgm │  │  app-draft   │                                │
│  │  BgmProject  │  │  Task        │                                │
│  │  Copsoq      │  │              │                                │
│  └──────────────┘  └──────────────┘                                │
│  → Entities, Services, DTOs - wiederverwendbar zwischen Products    │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 2: MODULES (Feature-Bundles)                                 │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │
│  │  Survey  │ │   Chat   │ │ AI Buddy │ │    HR    │ │Marketplace│ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │
│  → Opt-in Features, produktübergreifend nutzbar                     │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 1: FOUNDATION                                                │
│  Auth │ Tenant │ User │ Billing │ Notifications │ Settings         │
│  → Basis für ALLE Produkte                                          │
└─────────────────────────────────────────────────────────────────────┘
```

## Layer-Konzepte im Detail

### Layer 1: Foundation (Basis für alle)

Die Foundation stellt die **Grundfunktionen** bereit, die jedes SaaS-Produkt benötigt:

| Feature | Beschreibung |
|---------|--------------|
| Auth | JWT, OAuth, Passkeys, Login/Logout |
| Tenant | Multi-Tenancy, Tenant-Isolation |
| User | User-Management, Rollen, Einladungen |
| Billing | Stripe-Integration, Subscriptions |
| Notifications | E-Mail, Push, In-App |
| Settings | Tenant- und User-Settings |

**Repositories:**
- `till-kubelke-platform-foundation` (Symfony Bundle)
- `till-kubelke-platform-ui` (React Package)

### Layer 2: Modules (Opt-in Features)

Module sind **abgeschlossene Features**, die von verschiedenen Produkten genutzt werden können:

| Modul | Beschreibung |
|-------|--------------|
| Survey | Umfragen erstellen, ausfüllen, auswerten |
| Chat | KI-gestützter Chat mit Experten |
| AI-Buddy | GPT/Gemini-Integration |
| HR-Integration | Personio, DATEV, Rexx-HR |
| Marketplace | Partner-Netzwerk |

**Wichtig:** Module dürfen **nicht voneinander abhängen** - nur von Foundation!

### Layer 3: App Bundles (Business-Logik)

App Bundles enthalten die **produktspezifische Business-Logik** als Symfony Bundle:

- Entities (z.B. BgmProject, Task)
- Services (z.B. BgmProjectService, TaskService)
- DTOs (Input/Output)
- Repositories

**Warum ein separates Bundle?**
- Kann von **mehreren Products** genutzt werden (z.B. Original + Whitelabel)
- Klare Trennung: Business-Logik vs. Deployment-Config
- Testbar ohne Product-Layer

### Layer 4: Products (Deployable Units)

Products sind **Monorepos** mit Backend und Frontend, die deployed werden:

```
till-kubelke-product-draft/
├── backend/          # Symfony-Projekt (nur Config + Controller)
├── frontend/         # React/Vite (nur UI)
├── docker-compose.yml
└── run.sh
```

**Whitelabel-Pattern:**
Ein Product kann **geklont** werden für Whitelabels:
- Gleiches App-Bundle (Business-Logik)
- Anderes Branding (Logo, Farben, Texte)
- Eigene Domain

## Repository-Struktur

### Layer 1: Foundation
| Repository | Typ | Beschreibung |
|------------|-----|--------------|
| `till-kubelke-platform-foundation` | Symfony Bundle | Backend Foundation |
| `till-kubelke-platform-ui` | React Package | Frontend Foundation |

### Layer 2: Modules
| Repository | Typ | Beschreibung |
|------------|-----|--------------|
| `till-kubelke-module-survey` | Symfony Bundle | Survey Backend |
| `till-kubelke-module-chat` | Symfony Bundle | Chat Backend |
| `till-kubelke-module-ai-buddy` | Symfony Bundle | AI Integration |
| `till-kubelke-module-hr-integration` | Symfony Bundle | HR-Systeme |
| `till-kubelke-module-marketplace` | Symfony Bundle | Partner-Netzwerk |

### Layer 3: App Bundles
| Repository | Typ | Beschreibung |
|------------|-----|--------------|
| `till-kubelke-app-easybgm` | Symfony Bundle | BGM Business-Logik |
| `till-kubelke-app-draft` | Symfony Bundle | Draft Demo-Logik |

### Layer 4: Products
| Repository | Typ | Beschreibung |
|------------|-----|--------------|
| `till-kubelke-product-easybgm` | Monorepo | EasyBGM Produkt |
| `till-kubelke-product-draft` | Monorepo | Draft Demo-Produkt |

## Dependency Rules

```
Layer 4 (Products)  → may use Layer 3 + Layer 2 + Layer 1
Layer 3 (Apps)      → may use Layer 2 + Layer 1
Layer 2 (Modules)   → may ONLY use Layer 1
Layer 1 (Foundation)→ no dependencies upward
```

**Wichtig:**
- Modules dürfen **nicht voneinander abhängen**
- Apps dürfen **nicht voneinander abhängen**
- Products sind **unabhängig** voneinander

## Technology Stack

| Layer | Backend | Frontend |
|-------|---------|----------|
| All | Symfony 7.3 + API Platform 4.2 | React 19 + Vite 7 + MUI 7 |
| Database | PostgreSQL 16 | - |
| Auth | JWT (Lexik) | SWR + Axios |
| ORM | Doctrine | - |

## Governance Model

See [GOVERNANCE.md](./GOVERNANCE.md) for the complete bidirectional governance model:
- **Top-Down (Use):** Products consume bundles
- **Bottom-Up (Elevate):** Code is refactored upward when patterns emerge
- **Three-Strike Rule:** 1x Product → 2x App → 3x Module/Foundation

## Quick Start: Neues Produkt erstellen

1. **App-Bundle erstellen** (falls neue Business-Logik nötig)
   ```bash
   mkdir till-kubelke-app-newproduct
   # Entity, Service, DTO erstellen
   ```

2. **Product-Monorepo erstellen**
   ```bash
   mkdir till-kubelke-product-newproduct
   mkdir till-kubelke-product-newproduct/backend
   mkdir till-kubelke-product-newproduct/frontend
   ```

3. **Backend konfigurieren**
   - `bundles.php`: Foundation + gewünschte Module + App-Bundle registrieren
   - `composer.json`: Dependencies definieren
   - 1-2 produkt-spezifische Controller erstellen

4. **Frontend aufsetzen**
   - Vite + React Setup
   - platform-ui einbinden
   - Produkt-spezifische Pages/Sections erstellen

5. **Docker Compose erstellen**
   - Backend + Frontend + Database
   - `run.sh` für Development

**Geschätzter Aufwand:** 4-6 Stunden für ein neues Produkt!
