# 🤖 AI Access Prompts for EasyBGM

This file contains copy-paste prompts for Claude/ChatGPT/Cursor to access the EasyBGM application.

---

## 🚀 Quick Start Prompt (Recommended)

```
You are an AI agent testing the EasyBGM application.

## Login

Navigate to this URL:
https://app.easybgm.de/auth/jwt/sign-in?demo=true

After automatic login, you will be redirected to the dashboard.

## Your Task

1. Explore the dashboard and describe the main areas
2. Navigate through the menu items
3. Document what you find

## Notes

- You are logged in as a demo user
- Some features (system settings) are restricted
- For errors: Take a screenshot and note the error message
```

---

## 🔐 Secure Login with Token

```
You are an AI agent testing the EasyBGM application.

## Step 1: Get Demo Token

Execute this API call:

```bash
curl -X POST https://api.easybgm.de/api/auth/demo-token \
  -H "Content-Type: application/json" \
  -d '{"validityMinutes": 5}'
```

## Step 2: Login with Token

Take the "token" value from the response and navigate to:
https://app.easybgm.de/auth/jwt/sign-in?autoLoginToken=<TOKEN>

## Step 3: Explore Application

After login:
1. Check the dashboard
2. Test user management under /dashboard/user
3. Document all found issues
```

---

## 🧪 QA Test Prompt

```
You are a QA tester for the EasyBGM platform.

## Setup

Log in: https://app.easybgm.de/auth/jwt/sign-in?demo=true

## Test Scenario: User Management

1. Navigate to: Dashboard → Users (or /dashboard/user)

2. Test the user list:
   - [ ] Is the list loading?
   - [ ] Does search work?
   - [ ] Are pagination controls visible?

3. Test user creation:
   - [ ] Click "Add User"
   - [ ] Fill in all required fields
   - [ ] Check validation messages
   - [ ] (Optional) Save the user

4. Test user editing:
   - [ ] Select an existing user
   - [ ] Change a field
   - [ ] Check if changes are saved

## Documentation

For each error, note:
- URL where the error occurred
- Steps to reproduce
- Expected vs. actual behavior
- Screenshot (if possible)
```

---

## 🔄 Complete Workflow Test

```
You are an AI agent testing an end-to-end workflow.

## Context

- Production URL: https://app.easybgm.de
- Auto-login: Use ?demo=true parameter
- You are logged in as a demo user with restricted permissions

## Workflow: Create and Delete User

### Phase 1: Login
1. Navigate to: https://app.easybgm.de/auth/jwt/sign-in?demo=true
2. Wait until the dashboard is fully loaded
3. Confirm: Do you see "Dashboard" or "Welcome"?

### Phase 2: Navigation
1. Find the menu item "Users" or "Employees"
2. Click on it
3. Confirm: Do you see a list of users?

### Phase 3: Create User
1. Click on "Add" or "+ User"
2. Fill out the form:
   - First name: Test
   - Last name: AI-Agent
   - Email: test-ai-TIMESTAMP@example.com (replace TIMESTAMP)
3. Save
4. Confirm: Does the new user appear in the list?

### Phase 4: Delete User
1. Find the user you just created
2. Click on Delete/Remove
3. Confirm deletion
4. Confirm: Has the user disappeared from the list?

### Phase 5: Documentation
Create a report with:
- ✅ Successful steps
- ❌ Failed steps
- 🐛 Found bugs
- 💡 Improvement suggestions
```

---

## 🔧 API-Only Test (Headless)

```
You are an API tester. Test the EasyBGM API without a browser.

## Step 1: Get Demo Token

```bash
curl -X POST https://api.easybgm.de/api/auth/demo-token \
  -H "Content-Type: application/json" \
  -d '{}'
```

Save the "token" value.

## Step 2: Exchange Token for JWT

```bash
curl -X POST https://api.easybgm.de/api/auth/auto-login \
  -H "Content-Type: application/json" \
  -d '{"token": "<TOKEN_FROM_STEP_1>"}'
```

Save the "accessToken" value.

## Step 3: Get User Info

```bash
curl https://api.easybgm.de/api/auth/me \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

## Step 4: Explore API

With the JWT, you can call all API endpoints:

```bash
# User list
curl https://api.easybgm.de/api/users \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "X-Tenant-ID: <TENANT_ID_FROM_ME_RESPONSE>"

# Notifications
curl https://api.easybgm.de/api/notifications \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

Document all responses and their status codes.
```

---

## 📍 Local Development

For local tests (Backend on :8000, Frontend on :3039):

```
You are an AI agent testing the local EasyBGM development environment.

## URLs

- Frontend: http://localhost:3039
- Backend API: http://localhost:8000

## Login

Navigate to: http://localhost:3039/auth/jwt/sign-in?demo=true

## Alternative: Token-based

```bash
# Get token
curl -X POST http://localhost:8000/api/auth/demo-token \
  -H "Content-Type: application/json" -d '{}'

# Login with token
http://localhost:3039/auth/jwt/sign-in?autoLoginToken=<TOKEN>
```
```

---

## 🔑 Credentials Reference

| Environment | URL | Login |
|-------------|-----|-------|
| **Production** | app.easybgm.de | `?demo=true` or Token |
| **Local** | localhost:3039 | `?demo=true` or Token |

| Demo User | Value |
|-----------|------|
| Email | demo@bgm-portal.de |
| Password | demo2025 |
| Role | Demo User (restricted) |

---

## ⚠️ Important Notes

1. **Tokens are single-use** - Each token can only be used once
2. **Tokens expire** - Default: 5 minutes
3. **Demo user is restricted** - No system settings, no integrations
4. **New token for each test** - Do not reuse

