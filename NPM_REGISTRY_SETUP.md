# NPM Registry Setup Guide

This guide explains how to set up a private NPM registry for the Nexus Platform UI packages.

## Overview

All React/TypeScript packages are published to NPM:

| Package | NPM Name |
|---------|----------|
| Platform UI | `@the-nexus-collective/till-kubelke-platform-ui` |

## Option 1: GitHub Packages (Recommended)

GitHub Packages is included with GitHub and integrates seamlessly.

### 1. Configure Package

In `package.json`:

```json
{
  "name": "@the-nexus-collective/till-kubelke-platform-ui",
  "publishConfig": {
    "registry": "https://npm.pkg.github.com"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/The-Nexus-Collective/till-kubelke-platform-ui.git"
  }
}
```

### 2. Create .npmrc

In the package root:

```
@the-nexus-collective:registry=https://npm.pkg.github.com
//npm.pkg.github.com/:_authToken=${GITHUB_TOKEN}
```

### 3. Publish

```bash
# Login (use Personal Access Token with packages:write)
npm login --registry=https://npm.pkg.github.com

# Publish
npm publish
```

### 4. Install in Products

```bash
# Configure registry for scope
npm config set @the-nexus-collective:registry https://npm.pkg.github.com

# Install
npm install @the-nexus-collective/till-kubelke-platform-ui
```

## Option 2: npm (Public or Private)

### Public Package

```bash
npm publish --access public
```

### Private Package (npm Pro required)

```bash
npm publish --access restricted
```

## Option 3: Verdaccio (Self-Hosted)

For complete control, use Verdaccio:

```yaml
# docker-compose.yml
services:
  verdaccio:
    image: verdaccio/verdaccio
    ports:
      - "4873:4873"
    volumes:
      - verdaccio_storage:/verdaccio/storage
```

## CI/CD Publishing

### GitHub Actions

```yaml
name: Publish Package

on:
  release:
    types: [published]

jobs:
  publish:
    runs-on: ubuntu-latest
    permissions:
      packages: write
      contents: read
    steps:
      - uses: actions/checkout@v4
      
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          registry-url: 'https://npm.pkg.github.com'
          scope: '@the-nexus-collective'
      
      - run: npm ci
      - run: npm run build
      - run: npm publish
        env:
          NODE_AUTH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

## Versioning

Use `npm version` for consistent versioning:

```bash
# Patch release (1.0.0 → 1.0.1)
npm version patch

# Minor release (1.0.0 → 1.1.0)
npm version minor

# Major release (1.0.0 → 2.0.0)
npm version major

# Push tags
git push && git push --tags
```

## Local Development

### Using npm link

```bash
# In platform-ui directory
npm link

# In product directory
npm link @the-nexus-collective/till-kubelke-platform-ui
```

### Using Workspace

In product's `package.json`:

```json
{
  "workspaces": [
    "../till-kubelke-platform-ui"
  ]
}
```

## Package Structure

```
till-kubelke-platform-ui/
├── src/
│   ├── index.ts          # Main exports
│   ├── context/          # React contexts
│   ├── hooks/            # Custom hooks
│   ├── components/       # UI components
│   └── types/            # TypeScript types
├── dist/                 # Built output
├── package.json
├── tsconfig.json
└── vite.config.ts        # Build configuration
```

## Build Configuration

Add to `package.json`:

```json
{
  "main": "./dist/index.cjs",
  "module": "./dist/index.js",
  "types": "./dist/index.d.ts",
  "exports": {
    ".": {
      "import": "./dist/index.js",
      "require": "./dist/index.cjs",
      "types": "./dist/index.d.ts"
    }
  },
  "files": ["dist"],
  "scripts": {
    "build": "vite build && tsc --emitDeclarationOnly"
  }
}
```
