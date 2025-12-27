# 🔒 Security Audit Review – Open Source Readiness

**Datum:** 24. Dezember 2025  
**Reviewer:** Externer Symfony Core Team Security Experte + Leanna & Ryan  
**Ziel:** Prüfung auf Open-Source-Reife und vollständige Absicherung

---

## 📊 Executive Summary

| Kategorie | Status | Kritikalität | Action Required |
|-----------|--------|--------------|-----------------|
| **Tenant Isolation** | ✅ FIXED | CRITICAL | Bereits behoben |
| **Rate Limiting** | ⚠️ OPTIONAL | HIGH | Konfiguration erforderlich |
| **Input Validation** | ⚠️ MIXED | MEDIUM | DTOs für alle Endpoints |
| **Security Headers** | ✅ GOOD | LOW | Bereits implementiert |
| **Encryption** | ✅ GOOD | CRITICAL | AES-256-GCM vorhanden |
| **JWT Security** | ✅ GOOD | HIGH | 1h TTL, asymmetrisch |
| **CORS** | ✅ GOOD | MEDIUM | Korrekt konfiguriert |
| **Logging** | ⚠️ INSUFFICIENT | MEDIUM | Security Event Logging fehlt |

---

## 🔴 KRITISCHE FINDINGS

### 1. Rate Limiting ist OPTIONAL

**Problem:**
```php
// AuthController.php
public function __construct(
    // ...
    private ?RateLimiterFactory $loginLimiter = null,  // ⚠️ OPTIONAL!
    private ?RateLimiterFactory $signupLimiter = null, // ⚠️ OPTIONAL!
) {}
```

**Risiko:** Brute-Force-Angriffe auf Login/Signup möglich, wenn Rate Limiter nicht konfiguriert.

**Empfehlung:**
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        login:
            policy: 'sliding_window'
            limit: 5
            interval: '1 minute'
        signup:
            policy: 'fixed_window'
            limit: 3
            interval: '1 hour'
        password_reset:
            policy: 'fixed_window'
            limit: 3
            interval: '1 hour'
```

**Fix erforderlich:** ✅

---

### 2. Keine Rate Limiter Konfiguration gefunden

**Problem:** Keine `rate_limiter.yaml` oder entsprechende Konfiguration in `framework.yaml`.

**Fix:**
```bash
# Erstelle config/packages/rate_limiter.yaml
```

---

### 3. Input Validation inkonsistent

**Problem:** 22 Controller verwenden `json_decode($request->getContent())` statt DTOs mit `#[MapRequestPayload]`.

**Aktueller Stand:**
- ✅ Einige Endpoints nutzen DTOs
- ❌ Viele nutzen noch manuelles `json_decode()`

**Risiko:** Fehlende automatische Validierung, Type Coercion Issues.

**Empfehlung:** Alle Endpoints auf `#[MapRequestPayload]` migrieren:
```php
// ❌ VORHER
$data = json_decode($request->getContent(), true);
if (!isset($data['email'])) { ... }

// ✅ NACHHER
#[Route('/sign-in', methods: ['POST'])]
public function signIn(#[MapRequestPayload] SignInInput $input): JsonResponse
{
    // Automatische Validierung durch Symfony
}
```

---

## 🟡 MEDIUM FINDINGS

### 4. Security Event Logging fehlt

**Problem:** Keine strukturierte Erfassung von Sicherheitsevents.

**Empfehlung:** Security-spezifisches Logging implementieren:
```php
// Zu loggende Events:
- Failed login attempts
- Successful logins
- Password resets
- Tenant switching
- Permission denied
- Cross-tenant access attempts (bereits im TenantSecuritySubscriber)
```

**Beispiel:**
```php
$this->securityLogger->warning('security.login_failed', [
    'email' => $email,
    'ip' => $request->getClientIp(),
    'user_agent' => $request->headers->get('User-Agent'),
    'timestamp' => new \DateTimeImmutable(),
]);
```

---

### 5. Demo-Token API öffentlich zugänglich

**Problem:** `/api/auth/demo-token` ist öffentlich und erzeugt Login-Tokens.

**Aktueller Schutz:**
- ✅ Token sind zeitlich begrenzt (5 Min default)
- ✅ Einmalige Nutzung
- ✅ Demo-User hat eingeschränkte Rechte

**Empfehlung für Production:**
- Rate Limiting auf `/api/auth/demo-token` (z.B. 10/Stunde pro IP)
- Optional: Environment-basiertes Deaktivieren in Production

---

### 6. Auto-Login Token im Query String

**Problem:** Login-Token kann im URL sein (`?autoLoginToken=...`).

**Risiko:** Token könnte in Server-Logs, Browser-History, Referrer-Headers landen.

**Aktueller Schutz:**
- ✅ Einmalige Nutzung
- ✅ Kurze Gültigkeit (5 Min)

**Empfehlung:** 
- Token nach erfolgreicher Verwendung aus URL History entfernen (Frontend)
- Dokumentation für Betreiber bzgl. Log-Hygiene

---

### 7. Kein Refresh Token Rotation

**Problem:** Kein Refresh Token System, nur JWT mit 1h TTL.

**Aktuelles Verhalten:**
- JWT Token: 1 Stunde Gültigkeit
- Kein Refresh Token

**Risiko:** Bei Token-Kompromittierung 1 Stunde Fenster.

**Empfehlung:** Refresh Token mit Rotation implementieren:
```yaml
# lexik_jwt_authentication.yaml
token_ttl: 900  # 15 Minuten Access Token
# + Refresh Token mit 7 Tagen Gültigkeit, Rotation bei Nutzung
```

---

## 🟢 POSITIVE FINDINGS

### 8. Tenant Isolation ✅

- `AbstractTenantController` mit `validateTenant()`
- `TenantSecuritySubscriber` als Safety Net
- Security Tests vorhanden
- Pre-Commit Hook und CI-Check

### 9. Security Headers ✅

```php
// SecurityHeadersSubscriber.php
'X-Frame-Options' => 'DENY'
'X-Content-Type-Options' => 'nosniff'
'X-XSS-Protection' => '1; mode=block'
'Referrer-Policy' => 'strict-origin-when-cross-origin'
'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
'Strict-Transport-Security' => '...' // nur in prod
```

### 10. API Key Encryption ✅

- AES-256-GCM Verschlüsselung
- Separate Keys pro Environment
- Key Rotation Mechanismus vorhanden

### 11. CORS Konfiguration ✅

- `origin_regex: true` (keine Wildcards)
- Erlaubte Headers explizit definiert
- `allow_credentials: true` nur mit spezifischen Origins

### 12. Password Hashing ✅

```yaml
password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```

### 13. JWT Konfiguration ✅

- Asymmetrische Keys (Public/Private)
- 1 Stunde TTL
- Stateless Authentication

### 14. No Sensitive Data Exposure ✅

- User Entity hat kein `toArray()` (Passwort nicht exponiert)
- ServiceProvider `toArray()` enthält keine sensiblen Daten
- API Keys werden nur maskiert zurückgegeben

---

## 📋 Action Items für Open Source Release

### MUST HAVE (vor Release)

| # | Task | Priorität | Aufwand |
|---|------|-----------|---------|
| 1 | Rate Limiter Konfiguration erstellen | 🔴 KRITISCH | 1h |
| 2 | Rate Limiter als REQUIRED machen | 🔴 KRITISCH | 30min |
| 3 | Security Event Logger implementieren | 🟡 HOCH | 4h |
| 4 | `.env.example` ohne echte Secrets | 🔴 KRITISCH | 30min |
| 5 | Secrets aus Git-History entfernen | 🔴 KRITISCH | 2h |
| 6 | SECURITY.md für Vulnerability Reporting | 🟡 HOCH | 1h |

### SHOULD HAVE (nach Release)

| # | Task | Priorität | Aufwand |
|---|------|-----------|---------|
| 7 | Alle Endpoints auf MapRequestPayload migrieren | 🟡 MEDIUM | 8h |
| 8 | Refresh Token Rotation implementieren | 🟡 MEDIUM | 4h |
| 9 | IP-based Anomaly Detection | 🟢 LOW | 8h |
| 10 | 2FA/MFA Support | 🟢 LOW | 16h |

---

## 🔐 Empfohlene Security-Dokumentation für Open Source

### 1. SECURITY.md (Root-Verzeichnis)

```markdown
# Security Policy

## Reporting a Vulnerability

Please DO NOT open a public issue for security vulnerabilities.

Email: security@yourdomain.com

We will respond within 48 hours.

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅        |
| < 1.0   | ❌        |
```

### 2. .env.example (ohne echte Werte)

```bash
# .env.example
DATABASE_URL="postgresql://user:password@localhost:5432/app"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase-here
ENCRYPTION_KEY=generate-with-openssl-rand-base64-32
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

---

## ✅ Fazit

Das Projekt ist **grundsätzlich sicher** und die kritische Tenant-Isolation ist gut implementiert. 

Für **Open Source Readiness** müssen die folgenden KRITISCHEN Punkte behoben werden:

1. ⚠️ Rate Limiting konfigurieren und erzwingen
2. ⚠️ Security Event Logging implementieren  
3. ⚠️ `.env.example` ohne Secrets
4. ⚠️ SECURITY.md erstellen

Nach Behebung dieser Punkte ist das Projekt **Open Source Ready**.

---

*Security Review durchgeführt von: Leanna, Ryan & External Symfony Core Team Expert*



