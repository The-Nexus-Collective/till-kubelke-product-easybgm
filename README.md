# 🚀 Nexus Platform

A modular, multi-repository SaaS platform architecture.

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    LAYER 3: PRODUCTS                            │
│   till-kubelke-product-easybgm-backend                          │
│   till-kubelke-product-easybgm-frontend                         │
└─────────────────────────────────────────────────────────────────┘
                              ▲
┌─────────────────────────────────────────────────────────────────┐
│                    LAYER 2: APPS                                │
│   till-kubelke-app-easybgm (BGM 6-Phasen)                      │
└─────────────────────────────────────────────────────────────────┘
                              ▲
┌─────────────────────────────────────────────────────────────────┐
│                    LAYER 1: MODULES                             │
│   till-kubelke-module-survey                                    │
│   till-kubelke-module-chat                                      │
│   till-kubelke-module-ai-buddy                                  │
│   till-kubelke-module-hr-integration                            │
└─────────────────────────────────────────────────────────────────┘
                              ▲
┌─────────────────────────────────────────────────────────────────┐
│                    LAYER 0: FOUNDATION                          │
│   till-kubelke-platform-foundation (Backend)                    │
│   till-kubelke-platform-ui (Frontend)                           │
└─────────────────────────────────────────────────────────────────┘
```

## 📦 Repositories

| Layer | Repository | Description |
|-------|------------|-------------|
| Foundation | `till-kubelke-platform-foundation` | Auth, Tenant, Billing, Notifications |
| Foundation | `till-kubelke-platform-ui` | React components, hooks, context |
| Module | `till-kubelke-module-survey` | Survey management |
| Module | `till-kubelke-module-chat` | Chat conversations |
| Module | `till-kubelke-module-ai-buddy` | AI providers (GPT, Claude, Gemini, Grok) |
| Module | `till-kubelke-module-hr-integration` | Personio, HR systems |
| App | `till-kubelke-app-easybgm` | BGM 6-Phasen business logic |
| Product | `till-kubelke-product-easybgm-backend` | Deployable Symfony app |
| Product | `till-kubelke-product-easybgm-frontend` | Deployable React app |

## 🛠️ Tech Stack

### Backend
- **PHP 8.3** + **Symfony 7.3**
- **API Platform 4.2**
- **Doctrine ORM**
- **PostgreSQL 16**

### Frontend
- **React 19** + **TypeScript 5.9**
- **Vite 7**
- **MUI v7**
- **SWR**

### Testing
- **PHPUnit** (Backend)
- **Vitest** (Frontend)
- **Playwright** (E2E)

## 🚀 Quick Start

### Development Setup

```bash
# Clone all repositories
git clone https://github.com/The-Nexus-Collective/till-kubelke-product-easybgm-backend.git
git clone https://github.com/The-Nexus-Collective/till-kubelke-product-easybgm-frontend.git

# Start backend
cd till-kubelke-product-easybgm-backend
docker-compose up -d
composer install
symfony server:start

# Start frontend
cd till-kubelke-product-easybgm-frontend
npm install
npm run dev
```

### With Docker

```bash
cd till-kubelke-product-easybgm-backend
docker-compose up -d
```

Services:
- Backend: http://localhost:8000
- Frontend: http://localhost:8080
- Mailpit: http://localhost:8025
- PostgreSQL: localhost:5432

## 📚 Documentation

- [Architecture Overview](./ARCHITECTURE.md)
- [Implementation Plan](./IMPLEMENTATION_PLAN.md)
- [Packagist Setup](./PACKAGIST_SETUP.md)
- [NPM Registry Setup](./NPM_REGISTRY_SETUP.md)

## 🧪 Testing

### Backend
```bash
cd till-kubelke-product-easybgm-backend
./vendor/bin/phpunit
```

### Frontend
```bash
cd till-kubelke-product-easybgm-frontend
npm run test
```

### E2E
```bash
cd till-kubelke-product-easybgm-frontend
npx playwright test
```

## 📋 Dependency Rules

1. **Foundation** depends on nothing (only Symfony/React core)
2. **Modules** depend only on Foundation
3. **Apps** depend on Foundation and selected Modules
4. **Products** wire everything together

## 🔐 Security

- JWT Authentication
- Passkey/WebAuthn support
- Multi-tenancy with X-Tenant-ID header
- AES-256-GCM encryption for API keys
- OWASP Top 10 compliant

## 📄 License

Proprietary - The Nexus Collective
