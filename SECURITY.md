# Security Policy

## Reporting a Vulnerability

**Please DO NOT open a public GitHub issue for security vulnerabilities.**

If you discover a security vulnerability within this project, please send an email to the security team. All security vulnerabilities will be promptly addressed.

### Contact

- **Email:** security@easybgm.de
- **PGP Key:** [Available upon request]

### What to Include

When reporting a vulnerability, please include:

1. **Description** of the vulnerability
2. **Steps to reproduce** the issue
3. **Potential impact** assessment
4. **Suggested fix** (if you have one)

### Response Timeline

| Action | Timeline |
|--------|----------|
| Acknowledgment | Within 48 hours |
| Initial assessment | Within 5 business days |
| Fix development | Depends on severity |
| Public disclosure | After fix is released |

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅ Active support |
| < 1.0   | ❌ No support |

## Security Best Practices

### For Operators

1. **Environment Variables:**
   - Never commit `.env` files with real secrets
   - Use `.env.local` for local development
   - Use secure secret management in production (AWS Secrets Manager, HashiCorp Vault, etc.)

2. **JWT Keys:**
   - Generate unique keys for each environment
   - Rotate keys periodically
   - Store private keys securely

3. **Encryption Key:**
   - Generate with: `openssl rand -base64 32`
   - Never reuse across environments
   - Have a key rotation plan

4. **Rate Limiting:**
   - Ensure rate limiters are configured in `config/packages/rate_limiter.yaml`
   - Monitor for rate limit violations

5. **CORS:**
   - Configure `CORS_ALLOW_ORIGIN` strictly for production
   - Never use wildcard (`*`) with credentials

### For Contributors

1. **Never commit:**
   - API keys or secrets
   - Private keys
   - Passwords
   - `.env` files with real values

2. **Security-sensitive code:**
   - Use `AbstractTenantController` for tenant-scoped endpoints
   - Always validate tenant access with `validateTenant()` or `getValidatedTenant()`
   - Use DTOs with validation for all input
   - Never trust user input

3. **Before submitting PRs:**
   - Run security tests: `./scripts/security/run-security-tests.sh`
   - Ensure no secrets in commits
   - Review for OWASP Top 10 vulnerabilities

## Known Security Measures

### Tenant Isolation
- All tenant-scoped endpoints validate `X-Tenant-ID` header
- `TenantSecuritySubscriber` provides defense-in-depth
- Automated tests verify isolation

### Authentication
- JWT tokens with 1-hour expiration
- Asymmetric key signing (RS256)
- Rate limiting on login endpoints
- Password hashing with bcrypt/argon2

### Authorization
- Role-based access control
- Tenant-level permissions
- **Admin role via Impersonate only**: Admin access (formerly Super-Admin) is ONLY granted via
  Impersonate from the Admin Portal. Direct Super-Admin login to EasyBGM is blocked.
- Admin role grants elevated feature access but NOT security bypass

### Data Protection
- API keys encrypted with AES-256-GCM
- Sensitive data not exposed in API responses
- Passwords never returned in responses

### Security Headers
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- Strict-Transport-Security (production)
- Referrer-Policy: strict-origin-when-cross-origin

## Security Audits

This project has undergone security reviews:

- **December 2025:** Multi-tenancy hardening, ID-spoofing prevention
- **December 2025:** Open Source readiness review
- **December 2025:** Super-Admin removal from EasyBGM - Admin access now only via Impersonate

### Super-Admin to Admin Refactoring (December 2025)

A major security improvement was implemented to restrict Super-Admin access:

1. **Super-Admins cannot directly login to EasyBGM** - They must use Impersonate from Admin Portal
2. **Admin role is virtual** - Never stored in database, only granted via JWT claims during Impersonate
3. **Admin has feature rights but no security bypass** - Cannot assign Admin role, must follow tenant isolation
4. **AdminDetectionService** - New service to detect Impersonate sessions via JWT claims

Audit script: `./scripts/security/audit-super-admin-removal.sh`

## Acknowledgments

We thank all security researchers who responsibly disclose vulnerabilities.

---

*Last updated: December 2025*


