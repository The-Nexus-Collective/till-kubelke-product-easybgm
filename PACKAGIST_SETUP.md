# Private Packagist Setup Guide

This guide explains how to set up Private Packagist for the Nexus Platform bundles.

## Overview

All PHP bundles are published as Composer packages to Private Packagist:

| Bundle | Package Name |
|--------|--------------|
| Platform Foundation | `the-nexus-collective/till-kubelke-platform-foundation` |
| Survey Module | `the-nexus-collective/till-kubelke-module-survey` |
| Chat Module | `the-nexus-collective/till-kubelke-module-chat` |
| AI Buddy Module | `the-nexus-collective/till-kubelke-module-ai-buddy` |
| HR Integration Module | `the-nexus-collective/till-kubelke-module-hr-integration` |
| EasyBGM App | `the-nexus-collective/till-kubelke-app-easybgm` |

## Setup Steps

### 1. Create Private Packagist Organization

1. Go to [packagist.com](https://packagist.com)
2. Create organization: `the-nexus-collective`
3. Add team members with appropriate roles

### 2. Connect GitHub Repositories

1. Navigate to **Settings → Integrations → GitHub**
2. Install the Packagist GitHub App
3. Select the `The-Nexus-Collective` organization
4. Enable auto-sync for all `till-kubelke-*` repositories

### 3. Configure Authentication Token

Create an authentication token for CI/CD:

```bash
# Add to GitHub Secrets
PACKAGIST_TOKEN=your-token-here
```

### 4. Configure Product Repositories

In each product's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://the-nexus-collective.repo.packagist.com/"
        }
    ]
}
```

### 5. CI/CD Authentication

Add to GitHub Actions workflow:

```yaml
- name: Configure Packagist
  run: |
    composer config --global --auth http-basic.the-nexus-collective.repo.packagist.com token ${{ secrets.PACKAGIST_TOKEN }}
```

## Versioning Strategy

We use **Semantic Versioning** (SemVer):

- `MAJOR.MINOR.PATCH` (e.g., `1.2.3`)
- Breaking changes → increment MAJOR
- New features → increment MINOR
- Bug fixes → increment PATCH

### Tagging Releases

```bash
# In each bundle directory
git tag v1.0.0
git push origin v1.0.0
```

Private Packagist automatically detects new tags and creates package versions.

## Development Workflow

### Local Development (Path Repository)

For local development, use path repositories in your product:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../till-kubelke-platform-foundation",
            "options": { "symlink": true }
        }
    ]
}
```

### Switching to Production

Replace path repositories with Packagist:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://the-nexus-collective.repo.packagist.com/"
        }
    ],
    "require": {
        "the-nexus-collective/till-kubelke-platform-foundation": "^1.0"
    }
}
```

## Pricing

Private Packagist pricing (as of 2024):
- **Team**: $49/month (up to 10 users)
- **Business**: $149/month (unlimited users)
- **Enterprise**: Custom pricing

Alternative: GitHub Packages (included with GitHub)
