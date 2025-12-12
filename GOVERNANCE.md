# Nexus Platform Governance Model

> **Inspired by Design System Governance:** This architecture is both **top-down** (products use bundles) and **bottom-up** (refactor upward when patterns emerge).

---

## The Bidirectional Architecture 🔄

```
┌─────────────────────────────────────────────────────────────────┐
│  OUTER RING: PROJECT TEAM GOVERNANCE                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  PRODUCTS (Deployables)                  ↑ Elevate        │  │
│  │  ┌────────┐  ┌────────┐  ┌────────┐     │ when          │  │
│  │  │Backend │  │Frontend│  │ Mobile │     │ pattern       │  │
│  │  └────────┘  └────────┘  └────────┘     │ emerges       │  │
│  │       ↓ Uses components from below       │               │  │
│  └──────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────┤
│  MIDDLE RING: GUILD GOVERNANCE                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  BUSINESS APPS (Layer 3)                 ↑ Elevate        │  │
│  │  ┌─────────┐  ┌─────────┐               │ when          │  │
│  │  │ EasyBGM │  │ Future  │               │ generic       │  │
│  │  │  Bundle │  │ Product │               │ enough        │  │
│  │  └─────────┘  └─────────┘               │               │  │
│  │       ↓ Uses modules below               │               │  │
│  └──────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────┤
│  INNER RING: GUILD GOVERNANCE                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  REUSABLE MODULES (Layer 2)             ↑ Elevate        │  │
│  │  ┌────────┐ ┌────────┐ ┌────────┐      │ when          │  │
│  │  │ Survey │ │  Chat  │ │AI Buddy│      │ cross-        │  │
│  │  └────────┘ └────────┘ └────────┘      │ cutting       │  │
│  │       ↓ Uses foundation below           │               │  │
│  └──────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────┤
│  CORE: ARCHITECTURE TEAM GOVERNANCE                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  PLATFORM FOUNDATION (Layer 1)                            │  │
│  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐           │  │
│  │  │  Auth  │ │ Tenant │ │Billing │ │Encrypt │           │  │
│  │  └────────┘ └────────┘ └────────┘ └────────┘           │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘

KEY:
↓ = USE (Top-down: Products use Apps use Modules use Foundation)
↑ = ELEVATE (Bottom-up: Refactor upward when patterns emerge)
```

---

## Part 1: Top-Down (Use) ⬇️

### How Products Compose Bundles

Products are **configuration + composition** of bundles:

```php
// product-easybgm-backend/config/bundles.php
return [
    // Core Symfony
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    
    // Layer 1: Foundation
    TillKubelke\PlatformFoundation\PlatformFoundationBundle::class => ['all' => true],
    
    // Layer 2: Modules (pick what you need)
    TillKubelke\ModuleSurvey\SurveyModuleBundle::class => ['all' => true],
    TillKubelke\ModuleChat\ChatModuleBundle::class => ['all' => true],
    TillKubelke\ModuleAiBuddy\AiBuddyModuleBundle::class => ['all' => true],
    
    // Layer 3: Business App
    TillKubelke\AppEasyBgm\EasyBgmBundle::class => ['all' => true],
];
```

**Dependency Rules:**
- ✅ Products → can use Apps + Modules + Foundation
- ✅ Apps → can use Modules + Foundation
- ✅ Modules → can use Foundation ONLY
- ❌ Modules → CANNOT use other Modules
- ❌ Foundation → NO upward dependencies

---

## Part 2: Bottom-Up (Elevate) ⬆️

### When to Refactor Upward

As you build, patterns emerge. Use this decision tree:

```
Found code in Product
        ↓
    Ask: "Will OTHER products need this?"
        ↓
    NO → Keep in Product
    YES → Continue...
        ↓
    Ask: "Is it business-specific (e.g., BGM-related)?"
        ↓
    YES → Elevate to App (Layer 3)
    NO → Continue...
        ↓
    Ask: "Is it generic/reusable across business domains?"
        ↓
    YES → Continue...
        ↓
    Ask: "Is it a bounded feature (Survey, Chat, AI)?"
        ↓
    YES → Elevate to Module (Layer 2)
    NO → Continue...
        ↓
    Ask: "Is it cross-cutting (Auth, Storage, Security)?"
        ↓
    YES → Elevate to Foundation (Layer 1)
```

---

## Governance Levels 🏛️

### Level 1: Project Team (Products)

**Who Decides:** Individual product teams  
**Authority:** Full control over their product  
**Scope:** Configuration, deployment, feature toggles

**Examples:**
- Which bundles to include
- Environment variables
- Deployment strategy
- Product-specific UI customization

**Process:** Team decision, no approval needed

---

### Level 2: Guild (Apps + Modules)

**Who Decides:** Cross-functional feature teams  
**Authority:** Module/app features and contracts  
**Scope:** Business logic, module APIs, reusability

**Examples:**
- Adding features to module-survey
- Defining app-easybgm business rules
- Module-to-module interfaces (via events)
- API contracts

**Process:** 
1. Developer proposes in guild meeting
2. Team reviews impact
3. Vote/consensus
4. Implement

---

### Level 3: Architecture Team (Foundation)

**Who Decides:** Core architecture team  
**Authority:** Platform-wide decisions  
**Scope:** Security, auth, tenancy, core infrastructure

**Examples:**
- Authentication strategy
- Multi-tenancy approach
- Database patterns
- Breaking changes to foundation

**Process:**
1. RFC document
2. Architecture review
3. Breaking change analysis
4. Migration guide
5. Major version bump

---

## The Elevation Decision Matrix 🎯

| Question | Product | App | Module | Foundation |
|----------|---------|-----|--------|------------|
| **Used by multiple products?** | No | Maybe | Yes | Yes |
| **Business-specific?** | Maybe | Yes | No | No |
| **Generic/reusable?** | No | No | Yes | Yes |
| **Cross-cutting concern?** | No | No | No | Yes |
| **Used by ALL bundles?** | No | No | No | Yes |
| **Governance level** | Team | Guild | Guild | Architects |

---

## Extended Components & Variants 🧩

Inspired by Design Systems, modules can be **extended** without modification:

### Component Extension Pattern

```php
// ✅ Module provides base functionality
// module-survey/src/Service/SurveyManager.php
class SurveyManager
{
    public function create(array $config): Survey { /* ... */ }
    public function evaluate(Survey $survey): SurveyResult { /* ... */ }
}

// ✅ App extends with business-specific variant
// app-easybgm/src/Service/BgmSurveyManager.php
class BgmSurveyManager extends SurveyManager
{
    public function createCopsoq(): Survey
    {
        return $this->create(['type' => 'copsoq', 'version' => '3.0']);
    }
    
    public function createGpaq(): Survey
    {
        return $this->create(['type' => 'gpaq']);
    }
}
```

### When to Use Extension vs. Elevation

| Scenario | Action |
|----------|--------|
| BGM needs special survey config | **Extend** in App |
| 3 products need same survey config | **Elevate** config to Module |
| Survey config has security implications | **Elevate** to Foundation |

---

## Event-Based Module Communication 📡

Modules CANNOT depend on each other. Use Foundation events instead:

### Publishing Events (Module → Foundation)

```php
// module-survey/src/EventSubscriber/SurveyEventSubscriber.php
use TillKubelke\PlatformFoundation\Event\DomainEventDispatcher;

class SurveyEventSubscriber
{
    public function __construct(private DomainEventDispatcher $dispatcher) {}
    
    public function onSurveyCompleted(Survey $survey): void
    {
        // Dispatch via Foundation (no module dependency!)
        $this->dispatcher->dispatch(new SurveyCompletedEvent(
            surveyId: $survey->getId(),
            tenantId: $survey->getTenant()->getId(),
            completedAt: new \DateTimeImmutable()
        ));
    }
}
```

### Subscribing to Events (Module ← Foundation)

```php
// module-ai-buddy/src/EventSubscriber/SurveyAnalysisSubscriber.php
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use TillKubelke\PlatformFoundation\Event\SurveyCompletedEvent;

#[AsEventListener]
class SurveyAnalysisSubscriber
{
    public function __invoke(SurveyCompletedEvent $event): void
    {
        // Start AI analysis - no direct dependency on module-survey!
        $this->aiService->analyzeResults($event->surveyId);
    }
}
```

### Event Flow Diagram

```
┌──────────────┐    Event    ┌─────────────────┐    Event    ┌──────────────┐
│ module-survey│ ──────────> │    Foundation   │ ──────────> │module-ai-buddy│
│              │             │ (Event Bus)     │             │              │
└──────────────┘             └─────────────────┘             └──────────────┘
                                    │
                                    │ Event
                                    ▼
                            ┌──────────────┐
                            │  app-easybgm │
                            │ (orchestrate)│
                            └──────────────┘
```

---

## The Three-Strike Rule ⚾

```
1st occurrence: Build in Product (fast iteration)
    ↓
2nd occurrence: Consider App (pattern emerging)
    ↓
3rd occurrence: Evaluate Module or Foundation (clear reuse)
```

**Example:**

```php
// 1st time: Built in product-easybgm-backend
class NotificationSender { }

// 2nd time: Needed in product-easybgm-mobile
// Copy-paste with slight modifications

// 3rd time: Needed in future product
// STOP! Time to elevate to foundation
```

---

## Signals to Elevate ⬆️

### 🚨 Signal: Duplication

```bash
# Same code in multiple places
grep -r "class EmailSender" */src/
product-easybgm-backend/src/Service/EmailSender.php
module-survey/src/Service/EmailSender.php
module-chat/src/Service/EmailSender.php
```

**Action:** Elevate to `platform-foundation`

---

### 🚨 Signal: Generic Naming

```php
// In app-easybgm
class EmailService  // No "BGM" in name
class UserService   // No business reference
class DateHelper    // Pure utility
```

**Action:** Too generic for app → Elevate to foundation

---

### 🚨 Signal: Multiple Products Need It

```
Developer A: "Mobile app needs authentication"
Developer B: "New product needs auth too"
```

**Action:** Already in foundation ✅

---

### 🚨 Signal: Business Logic Leaking

```php
// ❌ Found in module-survey
class SurveyManager
{
    public function createBgmSurvey(): Survey
    {
        // NO! "BGM" is business logic
    }
}
```

**Action:** Move DOWN to app-easybgm (not elevation, refactoring)

---

## Anti-Patterns to Avoid ⚠️

### ❌ Anti-Pattern 1: Premature Elevation

```php
// ❌ WRONG: Elevating after first use
// Built feature once in product
// Immediately move to module "in case we need it later"
```

**Solution:** Wait for 2-3 occurrences. Refactoring is cheap, wrong abstractions are expensive.

---

### ❌ Anti-Pattern 2: Business Logic in Modules

```php
// ❌ WRONG: module-survey/src/Service/BgmSurveyCreator.php
class BgmSurveyCreator
{
    public function createCopsoqForBgm(): Survey
    {
        // "BGM" and "Copsoq" = business logic!
    }
}
```

**Solution:** Keep business logic in apps

```php
// ✅ CORRECT: app-easybgm/src/Service/BgmSurveyCreator.php
class BgmSurveyCreator
{
    public function __construct(private SurveyManager $surveyManager) {}
    
    public function createCopsoq(): Survey
    {
        return $this->surveyManager->create(['type' => 'copsoq']);
    }
}
```

---

### ❌ Anti-Pattern 3: Module-to-Module Dependencies

```php
// ❌ WRONG: module-survey depends on module-chat
use TillKubelke\ModuleChat\Entity\Conversation;

class SurveyManager
{
    public function createWithChat(Conversation $chat): Survey
    {
        // FORBIDDEN!
    }
}
```

**Solution:** Use events or let App orchestrate

```php
// ✅ CORRECT: app-easybgm orchestrates
class BgmProjectService
{
    public function __construct(
        private SurveyManager $surveyManager,
        private ChatService $chatService
    ) {}
    
    public function create(): BgmProject
    {
        $survey = $this->surveyManager->create();
        $chat = $this->chatService->create();
        return new BgmProject($survey, $chat);
    }
}
```

---

### ❌ Anti-Pattern 4: Foundation Depending on Modules

```php
// ❌ WRONG: platform-foundation depends on module-survey
use TillKubelke\ModuleSurvey\Entity\Survey;

class User
{
    private Collection $surveys; // NO!
}
```

**Solution:** Foundation has NO upward dependencies

---

## The Elevation Process 🔄

### Step 1: Identify Candidate

```markdown
## Elevation Proposal

**Component:** NotificationSender  
**Current Location:** product-easybgm-backend/src/Service/  
**Proposed Location:** platform-foundation/src/Notification/  

**Reason:**
- Used in 3 places (products + modules)
- Generic notification sending
- No business logic
- Cross-cutting concern

**Governance Level:** Foundation (Architecture Team)
```

---

### Step 2: Guild/Team Review

**Meeting Agenda:**
- Developer presents case
- Team asks:
  - "Is it truly generic?"
  - "Are there hidden dependencies?"
  - "Will ALL bundles need this?"
- Check for business logic leakage
- Vote/consensus

---

### Step 3: Create RFC (for Foundation)

```markdown
# RFC: Elevate NotificationSender to Foundation

## Summary
Move NotificationSender from duplicated locations to platform-foundation.

## Motivation
Currently duplicated in 3 modules. All bundles need notifications.

## Design
interface NotificationSenderInterface
{
    public function send(Notification $notification): void;
}

## Breaking Changes
- Modules must update imports
- Version bump: 1.x → 2.0

## Migration Guide
[See below]

## Alternatives Considered
1. Keep in each module → rejected (duplication)
2. Create new module-notification → rejected (too small, cross-cutting)
```

---

### Step 4: Implementation

```bash
# 1. Create in foundation
cd till-kubelke-platform-foundation
mkdir -p src/Notification
# Add NotificationSenderInterface, EmailSender, SmsSender

# 2. Version bump
# composer.json: "version": "2.0.0"

# 3. Remove from modules
cd till-kubelke-module-survey
# Delete old NotificationSender

# 4. Update dependencies
# composer.json: "till-kubelke/platform-foundation": "^2.0"

# 5. Update imports
find src/ -type f -exec sed -i 's/ModuleSurvey\\Service\\NotificationSender/PlatformFoundation\\Notification\\NotificationSenderInterface/' {} \;
```

---

## Versioning Strategy 📦

### Semantic Versioning

```
MAJOR.MINOR.PATCH

1.2.3
│ │ └─ Bug fixes (non-breaking)
│ └─── New features (non-breaking)
└───── Breaking changes
```

### When to Bump

| Change Type | Version Bump | Example |
|-------------|--------------|---------|
| Bug fix | PATCH | 1.2.3 → 1.2.4 |
| New feature (backward compatible) | MINOR | 1.2.4 → 1.3.0 |
| Breaking change | MAJOR | 1.3.0 → 2.0.0 |
| Elevation to foundation | Usually MAJOR | Imports change |

---

## Monthly Refactoring Review 📅

**Schedule:** First Friday of each month

**Agenda:**
1. **Review duplication reports**
   ```bash
   # Run before meeting
   ./scripts/find-duplication.sh
   ```

2. **Evaluate elevation candidates**
   - Present proposals
   - Discuss governance level
   - Vote on elevations

3. **Plan migrations**
   - Breaking changes
   - Migration guides
   - Timelines

4. **Retrospective**
   - What worked?
   - What didn't?
   - Adjust process

---

## Tools & Scripts 🛠️

### Find Duplication

```bash
#!/bin/bash
# scripts/find-duplication.sh

echo "=== Checking for duplicated classes ==="
find . -name "*.php" -type f | \
  xargs grep -h "^class " | \
  sort | \
  uniq -c | \
  awk '$1 > 1 { print $1, $2, $3 }'

echo ""
echo "=== Checking for duplicated interfaces ==="
find . -name "*.php" -type f | \
  xargs grep -h "^interface " | \
  sort | \
  uniq -c | \
  awk '$1 > 1 { print $1, $2, $3 }'
```

### Dependency Check

```bash
#!/bin/bash
# scripts/check-dependencies.sh

echo "=== Checking for module-to-module dependencies ==="
for module in till-kubelke-module-*/; do
  echo "Checking $module..."
  grep -r "use TillKubelke\\\\Module" "$module/src/" 2>/dev/null | \
    grep -v "$(basename $module | sed 's/till-kubelke-module-//')"
done
```

---

## Decision Framework Template 📝

Use this when unsure where code belongs:

```markdown
## Elevation Decision: [ComponentName]

### Context
- Current location: [path]
- What it does: [description]
- First created: [date]
- Usage count: [X products/modules]

### Questions

1. **Is it used by multiple products?**
   - [ ] No → Keep in Product
   - [ ] Yes → Continue

2. **Is it business-specific?**
   - [ ] Yes → Consider App
   - [ ] No → Continue

3. **Is it generic/reusable?**
   - [ ] No → Keep in Product
   - [ ] Yes → Continue

4. **Does it fit existing module scope?**
   - [ ] Yes → Consider Module
   - [ ] No → Continue

5. **Is it cross-cutting (auth, storage, etc)?**
   - [ ] Yes → Consider Foundation
   - [ ] No → Maybe new module?

### Recommendation
- [ ] Keep in Product
- [ ] Elevate to App
- [ ] Elevate to Module
- [ ] Elevate to Foundation

### Governance Required
- [ ] Team decision (Product/App)
- [ ] Guild approval (Module)
- [ ] Architecture RFC (Foundation)
```

---

## Summary 🎯

### The Two Flows

```
TOP-DOWN (Use): Products → Apps → Modules → Foundation
BOTTOM-UP (Elevate): Foundation ← Modules ← Apps ← Products
```

### The Three Rules

1. **Start Low:** Build in product first, elevate when patterns emerge
2. **Three Strikes:** Duplicate 3 times? Time to elevate
3. **Business Logic Down:** Generic code up, business logic down

### The Three Governance Levels

1. **Project Team:** Products (no approval needed)
2. **Guild:** Apps + Modules (team consensus)
3. **Architecture Team:** Foundation (RFC required)

---

## Related Documentation

- [ARCHITECTURE.md](./ARCHITECTURE.md) - Overall 3-layer architecture
- [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md) - Migration status
- [README.md](./README.md) - Quick start guide

---

*"Good architecture is about making the right things easy and the wrong things hard."*

— The Nexus Collective


