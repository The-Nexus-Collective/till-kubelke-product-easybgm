# 🧪 E2E Test Setup – Nexus Platform

## Übersicht

Die Nexus Platform hat eine isolierte Test-Umgebung für E2E-Tests:

```
┌─────────────────────────────────────────────────────────────────────┐
│  TEST-UMGEBUNG (Ports)                                              │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  Backend:           Port 8001 (APP_ENV=test)                │   │
│  │  EasyBGM Frontend:  Port 8081                               │   │
│  │  Admin Frontend:    Port 9001                               │   │
│  │  Database:          app_test (Port 5432)                    │   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

## Quick Start

```bash
# 1. Test-Umgebung starten (alle Services)
./run-test-env.sh

# 2. In separatem Terminal: Tests ausführen
cd till-kubelke-product-easybgm-frontend
npx playwright test

# Oder Admin-Tests:
cd till-kubelke-product-admin-frontend
npx playwright test
```

## Test-Suites

### EasyBGM Frontend

| Suite | Beschreibung | Command |
|-------|--------------|---------|
| `foundation` | Auth, Account, Dashboard, Navigation | `npx playwright test --project=foundation` |
| `easybgm` | BGM Phasen, Checklisten, Surveys | `npx playwright test --project=easybgm` |
| `i18n` | Internationalization Coverage | `npx playwright test --project=i18n` |
| `smoke` | Kritische User-Flows | `npx playwright test smoke.spec.ts` |

### Admin Frontend

| Suite | Beschreibung | Command |
|-------|--------------|---------|
| `admin` | System Config, Skills, Communities | `npx playwright test` |

## Test-Status-Kategorien

### ✅ Aktive Tests
Tests die immer laufen und funktionieren sollten.

### 🔧 `test.fixme()` – Unfertige Tests
Tests die bekannte Issues haben und noch implementiert werden müssen:

```typescript
// FIXME: Braucht test fixtures mit Daten
test.fixme("should display member list", async ({ page }) => {
  // ...
});
```

**Wann `test.fixme()` nutzen:**
- Test braucht Fixtures die noch nicht existieren
- UI-Komponente noch nicht implementiert
- Bekannter Bug verhindert Test-Durchführung

### ❌ `test.skip()` – Absichtlich übersprungen
Nur für Tests die unter bestimmten Bedingungen nicht laufen können:

```typescript
// Skip: Nur in CI-Umgebung relevant
test.skip(({ browserName }) => browserName !== 'chromium');
```

## Verzeichnisstruktur

```
till-kubelke-product-easybgm-frontend/e2e/
├── fixtures/                    # Test-Utilities & Fixtures
│   ├── index.ts                # Haupt-Export
│   ├── api-helpers.ts          # API-basierte Test-Daten
│   └── test-fixtures.ts        # Playwright Fixtures
│
├── page-objects/               # Page Object Classes
│   ├── login-page.ts
│   ├── dashboard-page.ts
│   └── bgm/                    # BGM-spezifische Page Objects
│
├── foundation/                 # Foundation-Tests (Layer 1)
│   ├── auth.spec.ts
│   ├── account.spec.ts
│   └── ...
│
├── easybgm/                    # EasyBGM-Tests (Layer 4)
│   ├── bgm-phases.spec.ts
│   ├── bgm-checklist.spec.ts
│   └── ...
│
├── auth.setup.ts               # Auth-State Setup
└── global-setup.ts             # DB Reset & Environment Check
```

## Troubleshooting

### "Test environment is not running"

```bash
# Starte die Test-Umgebung
./run-test-env.sh
```

### "Admin frontend is not running on port 9001"

Die Test-Umgebung startet jetzt beide Frontends. Stelle sicher, dass du `./run-test-env.sh` verwendest.

### Viele Tests "skipped"

Tests mit `test.fixme()` werden als "skipped" angezeigt. Das ist beabsichtigt – sie markieren unfertige Features.

Prüfe die Test-Kategorien:
- `test.fixme()` = Bekanntes Issue, wird noch implementiert
- Dynamische Skips = Daten-abhängig (z.B. leere Listen)

### Debugging

```bash
# UI Mode (interaktiv)
npx playwright test --ui

# Trace bei Fehlern
npx playwright test --trace on

# Einzelner Test
npx playwright test bgm-overview.spec.ts
```

## Best Practices

### 1. Nutze `test.fixme()` statt leerer Tests

```typescript
// ❌ Schlecht
test.skip("should do something", async () => {});

// ✅ Gut
test.fixme("should do something - needs fixture data", async ({ page }) => {
  // Implementiere den Test vollständig
  await page.goto('/dashboard');
  await expect(page.locator('[data-testid="something"]')).toBeVisible();
});
```

### 2. Stable Selectors mit `data-testid`

```typescript
// ❌ Fragil
await page.click('.MuiButton-primary');

// ✅ Stabil
await page.click('[data-testid="save-button"]');
```

### 3. Resiliente Assertions

```typescript
// ❌ Fragil - bricht bei Timing-Issues
const count = await items.count();
expect(count).toBe(5);

// ✅ Resilient - wartet auf Zustand
await expect(items).toHaveCount(5);
```

## CI/CD Integration

Die Tests laufen auch im Deployment Dashboard:

```javascript
// config.js
tests: {
  frontend: {
    dir: 'till-kubelke-product-easybgm-frontend',
    commands: {
      smoke: 'npx playwright test smoke.spec.ts',
      foundation: 'npx playwright test --project=foundation',
      easybgm: 'npx playwright test --project=easybgm',
    }
  },
  admin: {
    dir: 'till-kubelke-product-admin-frontend',
    commands: {
      'admin-e2e': 'npx playwright test'
    }
  }
}
```

