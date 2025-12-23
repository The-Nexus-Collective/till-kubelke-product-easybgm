# 🔒 Security Test Suite

Diese Dokumentation beschreibt die vollständige Security-Test-Suite für Multi-Tenancy und Privilege Escalation Prevention.

## 📊 Test-Übersicht

| Modul | Test-Datei | Kategorie | Tests |
|-------|-----------|-----------|-------|
| Marketplace | `TenantSecuritySubscriberTest.php` | Unit | 6 |
| Marketplace | `TenantIsolationTest.php` | Integration | 12 |
| Marketplace | `ControllerSecurityTest.php` | Integration | 10 |
| Chat | `ChatTenantSecurityTest.php` | Integration | 12 |
| Backend | `TenantSecurityTest.php` | Integration | 15 |
| Backend | `PrivilegeEscalationTest.php` | Integration | 10 |
| **Gesamt** | | | **~65** |

## 🚀 Tests ausführen

### Alle Security-Tests

```bash
./scripts/security/run-security-tests.sh
```

### Schnell-Modus (nur Unit-Tests)

```bash
./scripts/security/run-security-tests.sh --quick
```

### Einzelnes Modul

```bash
./scripts/security/run-security-tests.sh --module marketplace
./scripts/security/run-security-tests.sh --module chat
./scripts/security/run-security-tests.sh --module backend
```

### Mit PHPUnit direkt

```bash
# Marketplace
cd till-kubelke-module-marketplace
vendor/bin/phpunit --group security

# Chat
cd till-kubelke-module-chat
vendor/bin/phpunit --group security

# Backend
cd till-kubelke-product-easybgm-backend
vendor/bin/phpunit --group security
```

## 🛡️ Test-Kategorien

### 1. Tenant Isolation Tests (`@group tenant-isolation`)

Diese Tests verifizieren, dass Nutzer nur auf ihre eigenen Tenants zugreifen können:

- **ID-Spoofing Prevention**: Manipulation des `X-Tenant-ID` Headers wird blockiert
- **Cross-Tenant Access**: Zugriff auf fremde Tenant-Daten wird verhindert
- **Multi-Tenant Users**: Nutzer mit mehreren Tenants können nur auf ihre zugreifen
- **Super-Admin Bypass**: Super-Admins können auf alle Tenants zugreifen

```php
/**
 * SECURITY TEST: User cannot access tenant they don't belong to.
 */
public function testUserCannotAccessUnauthorizedTenant(): void
{
    // User belongs ONLY to their own tenant
    $this->createUserTenantMembership($user, $ownTenant);
    
    // Attempt to access VICTIM tenant (ID spoofing attack)
    $request->headers->set('X-Tenant-ID', (string) $targetTenant->getId());
    
    // MUST be blocked
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
}
```

### 2. Controller Security Tests (`@group controller-security`)

Diese Tests verifizieren, dass alle API-Endpoints korrekt gesichert sind:

- **Authentication Required**: Geschützte Endpoints erfordern Auth
- **Tenant Validation**: Alle tenant-scoped Endpoints validieren Zugriff
- **403 Response**: Unauthorized Access gibt 403 zurück

```php
/**
 * SECURITY TEST: All protected endpoints return 403 for wrong tenant.
 */
public function testAllProtectedEndpointsBlockUnauthorizedTenant(): void
{
    foreach ($protectedEndpoints as [$method, $endpoint]) {
        $this->client->request($method, $endpoint, headers: ['X-Tenant-ID' => '999999']);
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
```

### 3. Privilege Escalation Tests (`@group privilege-escalation`)

Diese Tests verifizieren, dass Nutzer ihre Rechte nicht erweitern können:

- **Role Manipulation**: `setRoles()` kann nicht via API aufgerufen werden
- **Super-Admin Creation**: Normale Nutzer können keine Super-Admins erstellen
- **Admin Access**: Reguläre Nutzer können nicht auf Admin-Endpoints zugreifen
- **Demo Restrictions**: Demo-Nutzer haben eingeschränkte Rechte

```php
/**
 * SECURITY TEST: Cannot set roles via API request.
 */
public function testCannotSetRolesViaApiRequest(): void
{
    $this->client->request('PUT', '/api/users/me', json: ['roles' => ['ROLE_SUPER_ADMIN']]);
    
    // Roles should NOT contain ROLE_SUPER_ADMIN
    $this->assertNotContains('ROLE_SUPER_ADMIN', $content['roles']);
}
```

### 4. Input Validation Tests

Diese Tests verifizieren, dass bösartige Eingaben abgewehrt werden:

- **SQL Injection**: SQL-Injection-Versuche im Tenant-ID werden blockiert
- **Invalid IDs**: Negative, leere oder nicht-numerische IDs werden abgelehnt
- **Forged Tokens**: Manipulierte JWT-Claims werden zurückgewiesen

```php
/**
 * SECURITY TEST: SQL Injection in tenant ID is blocked.
 */
public function testSqlInjectionInTenantIdIsBlocked(): void
{
    $maliciousInputs = [
        "1 OR 1=1",
        "1; DROP TABLE tenants;--",
        "1 UNION SELECT * FROM users",
    ];
    
    foreach ($maliciousInputs as $input) {
        $request->headers->set('X-Tenant-ID', $input);
        $this->assertNotEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
```

## 🔄 CI/CD Integration

Die Security-Tests laufen automatisch bei jedem Push/PR:

```yaml
# .github/workflows/security-check.yml

jobs:
  static-security-check:
    # Statische Analyse auf unsichere Patterns
    
  security-tests:
    # PHPUnit Security Tests
    run: vendor/bin/phpunit --group security
```

### Workflow-Trigger

- Push auf `main`, `develop`, `feature/**` (bei PHP-Änderungen)
- Pull Requests auf `main`, `develop`
- Manueller Trigger via GitHub Actions

## 📋 Pre-Commit Hook

Der Pre-Commit Hook verhindert Commits mit unsicheren Patterns:

```bash
# Installation
./scripts/security/install-hooks.sh

# Was geprüft wird:
# - getTenantFromRequest() ohne getValidatedTenant()
# - Direkter X-Tenant-ID Header-Zugriff in Controllern
```

## ✅ Checkliste für neue Endpoints

Vor dem Merge eines neuen Endpoints:

- [ ] Controller erbt von `AbstractTenantController`
- [ ] `validateTenant()` wird aufgerufen
- [ ] Security-Test für den Endpoint geschrieben
- [ ] Test mit `@group security` annotiert
- [ ] `./scripts/security/run-security-tests.sh` läuft grün

## 🚨 Was tun bei Test-Fehlern?

1. **Tenant Isolation Test schlägt fehl**:
   - Prüfe ob Controller `AbstractTenantController` erbt
   - Prüfe ob `validateTenant()` oder `getValidatedTenant()` aufgerufen wird
   
2. **Privilege Escalation Test schlägt fehl**:
   - Prüfe ob `setRoles()` irgendwo aus Request-Daten aufgerufen wird
   - Prüfe `#[IsGranted]` Annotations

3. **Controller Security Test schlägt fehl**:
   - Prüfe ob `#[IsGranted('ROLE_USER')]` auf dem Controller ist
   - Prüfe ob Tenant-Header validiert wird

---

*Security Tests sind nicht optional – sie sind die letzte Verteidigungslinie gegen Datenlecks!*

