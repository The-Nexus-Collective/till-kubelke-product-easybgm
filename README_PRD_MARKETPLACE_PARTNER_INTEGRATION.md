# Marketplace Partner Integration - PRD

*"Hilfe zur Selbsthilfe" - Bring external BGM expertise into your process, without losing control.*

---

## The Problem

**Ryan:** "Right now, our users are on their own. They go through the 6-phase BGM process, hit a checklist item like 'Conduct employee survey', and... now what? They need to find a COPSOQ provider, negotiate separately, manually track results, and somehow connect it all back to their BGM project."

**Leanna:** "And the data flow is a nightmare! The survey provider needs employee emails, but how do you share that securely? Then results come back as a PDF attachment, and the user has to manually enter insights. There's no connection between the external service and our platform."

**The Gap:**
- Users can't easily find and engage BGM service providers
- No structured way to share data with partners (DSGVO-compliant)
- Partner results don't flow back into the BGM process
- No documentation of who participated in what intervention
- Health days are planned in Excel with zero tool support

---

## Who We're Building For

### Primary: BGM Manager ("Petra")
- Manages workplace health for 50-500 employees
- Has budget but limited time
- Wants to stay in control but needs expert help
- Needs documentation for health insurance reports

### Secondary: Service Provider ("Marcus")
- Offers BGM services (surveys, coaching, workshops)
- Wants qualified leads from companies already doing BGM
- Needs clear briefs and structured data handoff

### Tertiary: Health Insurance Partner ("AOK Anna")
- Offers full-service health day planning
- Coordinates multiple sub-providers
- Wants to integrate with company's BGM system

---

## Core Features

### Feature 1: Smart Partner Discovery
> *"The right partner at the right moment"*

When Petra is in Phase 2 (Analysis) working on "Employee Survey", the system proactively suggests relevant partners who can help with exactly that task.

```
┌─────────────────────────────────────────────────────┐
│ 💡 Partners who can help with "Employee Survey"     │
├─────────────────────────────────────────────────────┤
│ 🏥 COPSOQ-Analyse by SurveyPro GmbH                │
│    §20 certified | Remote | from 1.200€            │
│    [ Learn more ]  [ Request quote ]               │
├─────────────────────────────────────────────────────┤
│ 📊 Quick Pulse Survey by FeedbackNow               │
│    2-week turnaround | from 800€                   │
│    [ Learn more ]  [ Request quote ]               │
└─────────────────────────────────────────────────────┘
```

**Key Capabilities:**
- Phase-aware suggestions (different partners for different phases)
- Filter by: category, certification, delivery mode, price range
- AI-powered matching based on company goals and survey results

---

### Feature 2: Structured Data Exchange
> *"Partner gets what they need, nothing more"*

Each service offering defines exactly what data it needs (input) and what it delivers (output). The user explicitly grants access - DSGVO compliant.

**Data Scopes (what partners can request):**
| Scope | Sensitivity | Example |
|-------|-------------|---------|
| `employee_count` | Low | "120 employees" |
| `goals` | Low | BGM goals from Phase 1 |
| `survey_results` | Medium | Anonymized survey data |
| `employee_list` | High | Names + emails for invitations |

**User grants access explicitly:**
```
┌─────────────────────────────────────────────────────┐
│ 🔐 Data Access Request                              │
│                                                     │
│ SurveyPro GmbH requests access to:                  │
│                                                     │
│ [✓] Employee emails (for survey invitations)        │
│     → 120 employees will receive survey link        │
│                                                     │
│ [✓] BGM goals (for customized questions)            │
│     → Your 3 defined health goals will be shared    │
│                                                     │
│ [ Grant Access ]  [ Decline ]                       │
└─────────────────────────────────────────────────────┘
```

---

### Feature 3: Result Integration ("Einklinken")
> *"Partner results flow back automatically"*

When the partner delivers results, they automatically appear in the right place in the BGM process.

**Integration Points:**
| Partner Output | Lands In | Example |
|----------------|----------|---------|
| `copsoq_analysis` | Phase 2 Analysis Tab | Survey results with charts |
| `intervention_plan` | Phase 3 Planning | Suggested interventions |
| `participation_stats` | KPI Dashboard | Custom KPI card |
| `ergonomic_assessment` | Legal Requirements | Gefährdungsbeurteilung ✓ |

**The magic:** Partner uploads structured JSON/PDF → System knows where to put it → User sees it in context.

---

### Feature 4: Participation Tracking
> *"Document everything, share only aggregates"*

Internal documentation of who participated in what - the partner never sees the names.

**What Petra sees:**
```
┌─────────────────────────────────────────────────────┐
│ 📋 Participation: Lunch & Learn (March 15)          │
├─────────────────────────────────────────────────────┤
│ [✓] Max Mustermann    ● attended                    │
│ [✓] Lisa Schmidt      ● attended                    │
│ [✓] Tom Weber         ○ no-show                     │
│ ...                                                 │
│                                                     │
│ Summary: 28/32 attended (87.5%)                     │
│ [ Export for Insurance Report ]                     │
└─────────────────────────────────────────────────────┘
```

**What the partner sees:**
```
{ "attendedCount": 28, "registeredCount": 32, "attendanceRate": 0.875 }
```

**Long-term value:**
- Insurance reports: "127 employees participated in nutrition programs"
- Engagement analysis: "Sales dept. has lowest participation"
- AI recommendations: "Consider mobile offerings for field workers"

---

### Feature 5: Health Day Planner
> *"From chaos to coordinated wellness event"*

Two modes for different needs:

**DIY Mode:** User plans themselves with tool support
- Module catalog (linked to Marketplace)
- Timeline builder
- Budget tracker
- Registration management

**Full-Service Mode:** External planner (health insurance, consultant) coordinates everything
- Single point of contact
- Planner books all sub-providers
- Consolidated reporting

```
┌─────────────────────────────────────────────────────┐
│ 🎯 Health Day: "Fit for Work"                       │
│    March 15, 2025 | Budget: 5.000€                  │
├─────────────────────────────────────────────────────┤
│ Modules:                                            │
│ ├─ 09:00 Rückenscreening (Rückenpause GmbH) ✓      │
│ ├─ 10:00 Stress-Messung (cardioscan) ✓             │
│ ├─ 12:00 Lunch & Learn (Upfit) ✓                   │
│ └─ 14:00 Ergonomie-Check (ErgoProfi) ⏳            │
│                                                     │
│ Registrations: 87/120 (72%)                         │
│ Status: Preparation in progress                     │
└─────────────────────────────────────────────────────┘
```

---

## Deliverables & Progress Tracking

### MVP (Phase 1) - Core Partner Flow
> *Goal: End-to-end partner engagement works for simple cases*

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 1.1 | **ServiceOffering Entity Extension** | ✅ DONE | Add `requiredDataScopes`, `outputDataTypes`, `integrationPoints`, `relevantPhases` |
| 1.2 | **PartnerEngagement Entity** | ✅ DONE | Track active collaborations with status flow |
| 1.3 | **DataScopeRegistry** | ✅ DONE | Central definition of all shareable data types |
| 1.4 | **Engagement API** | ✅ DONE | Endpoints: create, update-status, grant-data |
| 1.5 | **Partner Suggestion Widget** | ✅ DONE | Show relevant partners in phase views |
| 1.6 | **Data Grant Dialog** | ✅ DONE | UI for explicit data access approval |
| 1.7 | **Engagement Dashboard** | ✅ DONE | List active partner engagements with status |
| 1.8 | **Basic Result Upload** | ✅ DONE | Partner can upload result files/JSON |

**MVP Definition of Done:**
- [x] User can discover partners relevant to their current phase
- [x] User can request quote from a partner
- [x] User can grant specific data access to partner
- [x] Partner can upload results
- [x] Results appear in user's engagement dashboard

---

### MVP (Phase 2) - Participation Tracking
> *Goal: Document who participated in what*

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 2.1 | **InterventionParticipation Entity** | ✅ DONE | Central participation records |
| 2.2 | **Registration Flow** | ✅ DONE | Employees can sign up for interventions |
| 2.3 | **Attendance Tracking UI** | ✅ DONE | BGM manager marks who attended |
| 2.4 | **Aggregation Service** | ✅ DONE | Generate anonymous stats for partners |
| 2.5 | **Participation Reports** | ✅ DONE | Export for insurance documentation |

**Phase 2 Definition of Done:**
- [x] Employees can register for interventions
- [x] BGM manager can track attendance
- [x] System generates participation reports
- [x] Partner only sees aggregated numbers

---

### MVP (Phase 3) - Result Integration
> *Goal: Partner results flow into BGM process*

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 3.1 | **OutputTypeRegistry** | ✅ DONE | Define all result types and where they land |
| 3.2 | **Integration Point Handlers** | ✅ DONE | Logic to "plug in" results to correct location |
| 3.3 | **Phase 2 Analysis Integration** | ✅ DONE | Survey results appear in Analysis tab |
| 3.4 | **KPI Custom Integration** | ✅ DONE | External stats as KPI cards |
| 3.5 | **Legal Requirements Integration** | ✅ DONE | Gefährdungsbeurteilung auto-completion |

**Phase 3 Definition of Done:**
- [x] COPSOQ results appear in Phase 2 Analysis
- [x] Participation stats show as custom KPI
- [x] Ergonomic assessment marks legal requirement as done

---

### Post-MVP: Health Day Module
> *Goal: Dedicated module for planning wellness events*

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 4.1 | **HealthDay Entity** | ⬜ TODO | Event with date, location, budget, status |
| 4.2 | **HealthDayModule Entity** | ⬜ TODO | Individual stations/activities |
| 4.3 | **HealthDayRegistration Entity** | ⬜ TODO | Employee sign-ups per module |
| 4.4 | **DIY Planning UI** | ⬜ TODO | Timeline builder, module catalog |
| 4.5 | **Full-Service Integration** | ⬜ TODO | Orchestrator pattern for external planners |
| 4.6 | **Live Event Tracking** | ⬜ TODO | QR check-in, real-time stats |
| 4.7 | **Post-Event Report** | ⬜ TODO | Aggregated feedback, recommendations |

---

### Post-MVP: AI-Powered Matching
> *Goal: Smart recommendations based on context*

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 5.1 | **PartnerMatchingService** | ⬜ TODO | Context-aware partner suggestions |
| 5.2 | **AI Buddy Integration** | ⬜ TODO | Chat can suggest partners |
| 5.3 | **Goal-based Matching** | ⬜ TODO | "You want to reduce sick days → try X" |
| 5.4 | **Survey-based Matching** | ⬜ TODO | "38% back pain → ergonomics partner" |
| 5.5 | **Proactive Suggestions** | ⬜ TODO | Push notifications for relevant partners |

---

### Post-MVP: Partner Portal
> *Goal: Partners manage their side efficiently*

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 6.1 | **Partner Dashboard** | ⬜ TODO | See all inquiries and engagements |
| 6.2 | **Structured Result Upload** | ⬜ TODO | Forms based on output type schema |
| 6.3 | **Engagement Status Updates** | ⬜ TODO | Partner updates progress |
| 6.4 | **Messaging System** | ⬜ TODO | In-platform communication |

---

## Technical Architecture Overview

### Module Placement

Following the platform's 3-layer architecture and governance rules:

```
┌─────────────────────────────────────────────────────────────────┐
│ LAYER 3: PRODUCTS                                               │
│                                                                 │
│  product-easybgm-backend/                                       │
│  └── Orchestrates modules, handles app-specific logic           │
│                                                                 │
│  product-easybgm-frontend/                                      │
│  └── Partner widgets, engagement UI, health day planner UI      │
├─────────────────────────────────────────────────────────────────┤
│ LAYER 2: MODULES                                                │
│                                                                 │
│  module-marketplace/ (EXTENDED)                                 │
│  module-health-day/ (NEW)                                       │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│ LAYER 1: FOUNDATION                                             │
│                                                                 │
│  platform-foundation/                                           │
│  └── Tenant, User, Employee (via HR module)                     │
└─────────────────────────────────────────────────────────────────┘
```

### Module 1: `till-kubelke-module-marketplace` (Extended)

**Location:** `till-kubelke-module-marketplace/src/`

```
src/
├── Entity/
│   ├── Category.php                    # existing
│   ├── Tag.php                         # existing
│   ├── ServiceProvider.php             # existing
│   ├── ServiceOffering.php             # MODIFY: add data scope fields
│   ├── ServiceInquiry.php              # existing
│   ├── PartnerEngagement.php           # NEW: active collaboration tracking
│   └── InterventionParticipation.php   # NEW: who attended what
│
├── Repository/
│   ├── ServiceProviderRepository.php   # existing
│   ├── PartnerEngagementRepository.php # NEW
│   └── InterventionParticipationRepository.php  # NEW
│
├── Registry/                           # NEW folder
│   ├── DataScopeRegistry.php           # NEW: defines shareable data types
│   └── OutputTypeRegistry.php          # NEW: defines result types & integration points
│
├── Service/
│   ├── ProviderService.php             # existing
│   ├── InquiryService.php              # existing
│   ├── EngagementService.php           # NEW: engagement workflow logic
│   ├── PartnerMatchingService.php      # NEW: AI-powered partner suggestions
│   └── ParticipationAggregationService.php  # NEW: anonymous stats for partners
│
├── Controller/
│   ├── CatalogController.php           # existing
│   ├── ProviderController.php          # existing
│   ├── InquiryController.php           # existing
│   ├── AdminController.php             # existing
│   └── EngagementController.php        # NEW: engagement workflow endpoints
│
└── MarketplaceModuleBundle.php         # existing
```

**Why here?** Partner Engagement is the natural next step after discovery in the catalog. It's the "transaction" that happens after browsing.

---

### Module 2: `till-kubelke-module-health-day` (New)

**Location:** `till-kubelke-module-health-day/` (create new)

```
till-kubelke-module-health-day/
├── composer.json
├── phpunit.xml.dist
├── README.md
├── Resources/
│   └── config/
│       └── services.yaml
├── src/
│   ├── HealthDayModuleBundle.php
│   │
│   ├── Entity/
│   │   ├── HealthDay.php               # The event itself
│   │   ├── HealthDayModule.php         # Individual stations/activities
│   │   └── HealthDayRegistration.php   # Employee sign-ups
│   │
│   ├── Repository/
│   │   ├── HealthDayRepository.php
│   │   ├── HealthDayModuleRepository.php
│   │   └── HealthDayRegistrationRepository.php
│   │
│   ├── Service/
│   │   ├── HealthDayPlannerService.php # Planning logic
│   │   └── HealthDayReportService.php  # Post-event reporting
│   │
│   └── Controller/
│       └── HealthDayController.php
│
└── Tests/
    └── Unit/
        └── Entity/
            └── HealthDayTest.php
```

**Why separate module?** Health Day planning is a distinct bounded context. It can work standalone (DIY mode without marketplace) or integrated (modules from marketplace).

---

### Dependency Rules (Governance Compliant)

```
✅ ALLOWED                           ❌ NOT ALLOWED
─────────────────────────────────    ─────────────────────────────────
module-marketplace → foundation     module-marketplace → module-health-day
module-health-day → foundation      module-health-day → module-marketplace
app-easybgm → both modules          modules → app-easybgm
product → app → modules             foundation → modules
```

**How do they communicate?**
- Via **Foundation entities** (Tenant, User)
- Via **Events** dispatched through Foundation
- The **app-easybgm** layer orchestrates when both modules need to interact

**Example:** When a HealthDayModule links to a ServiceOffering:
```php
// In app-easybgm (Layer 3), NOT in the modules
class BgmHealthDayService
{
    public function linkModuleToOffering(
        HealthDayModule $module,
        ServiceOffering $offering
    ): PartnerEngagement {
        // App layer handles the cross-module orchestration
    }
}
```

---

### Frontend Structure

**Location:** `till-kubelke-product-easybgm-frontend/src/`

```
src/
├── sections/
│   └── marketplace/                    # NEW section
│       ├── partner-suggestion-widget.tsx
│       ├── data-grant-dialog.tsx
│       ├── engagement-dashboard.tsx
│       └── engagement-card.tsx
│
├── sections/
│   └── health-day/                     # NEW section
│       ├── health-day-planner.tsx
│       ├── health-day-timeline.tsx
│       ├── module-selector.tsx
│       └── registration-manager.tsx
│
├── hooks/
│   ├── use-partner-suggestions.ts      # NEW
│   ├── use-engagements.ts              # NEW
│   ├── use-health-day.ts               # NEW
│   └── use-participation.ts            # NEW
│
└── pages/
    └── dashboard/
        └── bgm/
            ├── partners.tsx            # NEW: engagement dashboard page
            └── health-day/
                ├── index.tsx           # NEW: health day list
                └── [id].tsx            # NEW: health day detail/planner
```

---

## Status Legend

| Symbol | Meaning |
|--------|---------|
| ⬜ TODO | Not started |
| 🟡 IN PROGRESS | Currently working on |
| 🟢 DONE | Completed and tested |
| 🔴 BLOCKED | Waiting on something |
| ⏸️ PAUSED | Deprioritized |

---

## Changelog

| Date | Change | By |
|------|--------|-----|
| 2024-12-16 | Initial PRD created | Ryan & Leanna |
| 2024-12-16 | Added detailed module structure and file placement | Ryan & Leanna |

---

*"The best BGM software doesn't replace expertise - it connects you with the right experts at the right time, and makes sure nothing falls through the cracks."*

— Ryan & Leanna 🚀
