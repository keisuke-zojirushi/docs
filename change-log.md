# Change Log


## 2026-06-11 – WordPress Compatibility Update

### Deprecated Function Removal

Replaced deprecated:

```php
get_page_by_title()
# 2026-06-11 – Parts Pricing System Refactoring

## Overview

Refactored the Parts pricing architecture to support role-based pricing for Service Centers, Technical Support staff, and future Parts Order / Warranty Claim workflows.

This update establishes **Distributor Price** as the single source of truth for all Parts pricing calculations.

---

## ACF Changes

### Previous Structure

```text
price
```

### New Structure

```text
distributor_price
```

The legacy `price` field is being phased out.

All future imports and pricing calculations will use:

```text
distributor_price
```

---

## User Roles

The following roles are now supported by the pricing system:

| Role            | Slug               |
| --------------- | ------------------ |
| Admin           | administrator      |
| Editor          | editor             |
| ZAC TS Staff    | zac_ts             |
| US & CANADA SVC | us_canada_svc      |
| MEXICO SVC      | mexico_svc         |
| PARTS Retailor  | canada_parts_sales |

---

## Pricing Rules

### Distributor Price

Base price maintained by Zojirushi America.

### Display Price by Role

| Role            | Calculation                   |
| --------------- | ----------------------------- |
| Admin           | Distributor Price × 100%      |
| Editor          | Distributor Price × 100%      |
| ZAC TS Staff    | Distributor Price × 100%      |
| US & CANADA SVC | Distributor Price × 82%       |
| MEXICO SVC      | Distributor Price × 100%      |
| PARTS Retailor  | Distributor Price × 82% × 85% |

All calculations are rounded using PHP `round()`.

---

## New Functions

Added to:

```text
functions.php
```

### zati_get_svc_price()

Returns standard Service Center pricing.

```text
Distributor Price × 82%
```

### zati_get_part_price_by_role()

Returns pricing automatically based on logged-in user role.

Used throughout the system to ensure pricing consistency.

---

## Search Results Integration

Updated:

```text
page-model-search.php
```

Price column now uses:

```php
zati_get_part_price_by_role()
```

instead of directly displaying stored values.

Verified:

```text
Distributor Price = 100

Admin
→ 100

US & CANADA SVC
→ 82
```

---

## Model Detail Integration

Updated:

```text
single-model_alias.php
```

Parts List pricing now uses the same role-based pricing logic as Search Results.

Verified:

```text
Distributor Price = 100

Admin
→ 100

US & CANADA SVC
→ 82
```

---

## Warranty Claim Planning

Initial Warranty Claim implementation will support:

* US & CANADA SVC
* ZAC TS Staff
* Admin
* Editor

Not included in Version 1:

* MEXICO SVC
* PARTS Retailor

Warranty Claim Parts reimbursement will use:

```text
Distributor Price × 82%
```

through:

```php
zati_get_svc_price()
```

---

## Import Specification

Planned Parts CSV format:

```csv
model,no,partnumber,jpnumber,description,distributor_price,remarks
```

Future WP All Import processes will populate:

```text
distributor_price
```

directly.

---

## Status

Pricing engine implementation completed and verified.

The Parts pricing architecture is now ready for:

* Parts Search
* Model Detail
* Parts Order Form
* Warranty Claim Form
* WP All Import integration


## 2026-06-11 – WordPress Compatibility Update

### Deprecated Function Removal

Replaced deprecated:

```php
get_page_by_title()

with:

WP_Query()

to maintain compatibility with WordPress 6.2+ and future releases.

Result
Deprecated warning removed
Production List page functioning normally
Improved future WordPress compatibility

---

## 現在の残タスク



```text
Models data recovery
Parts data recovery
WP All Import design
Medium Priority
Parts Order Form
Warranty Claim Form
Low Priority
Login redirect enforcement
Role-based menu visibility
Final design tuning

## 2026-05-29

### Added

* TOP Page Layout
* Sidebar Search Component
* News Topics Section
* Quick Links Section
* Category Navigation Section

### Changed

* Replaced static News area with WordPress Custom Post Type (`zac_news`)
* Updated TOP page structure to ZOIS-inspired layout
* Reorganized sidebar search flow

### Fixed

* TwentyTwentyOne CSS conflicts
* Sidebar width and layout issues
* News Topics rendering issues
* Footer removal on TOP page

### Current Status

TOP page mock version is now functional.

Implemented:

* Sidebar Search
* News Topics
* Quick Links
* Category Section

### Next Tasks

* Header redesign
* Navigation cleanup
* Responsive layout
* Dynamic Category links
* News scroll area improvements

---

## 2026-05-28

### Added

* Custom Post Type: zac_news
* News Topics dashboard posting system

### Fixed

* News query integration
* WordPress admin posting workflow


## 2026-05-20 Parts System Migration (Phase 1)

### Added
- Introduced Parts data structure using CSV import
- Added fields:
  - model
  - diagram_no
  - part_number
  - description
  - price
  - alt_part_number
- Enabled per-part row-based data structure (1 row = 1 part)

### Changed
- Migrated from manual parts_list text field to structured Parts data
- Removed dependency on manually formatted text-based parts tables

### In Progress
- Testing Parts import for Rice Cooker models
- Validating model-to-parts relationship using "model" field

### Planned
- Replace PDF Parts Price List with dynamic CSV download
- Implement Parts search by part_number
- Add multiple price types (SVC / Repair / Mexico)
  
## 2026-05-14🔧 Update: Document Classification Simplification

To improve usability for North American service centers (SVC), document categories have been simplified and standardized.

#### ✅ Changes
- Renamed:
  - **Operation Instructions → User Manual**
    - Clarifies that the document is intended for end users
- Consolidated:
  - **After Service Information + Repair Instructions → Technical Info**
    - Reduces confusion and groups supplementary technical materials into a single category

#### ✅ Final Document Structure
- Parts Price List ✅ (Primary)
- Service Manual ✅ (Primary)
- User Manual ✅ (Secondary)
- Technical Info ✅ (Secondary)

#### ✅ Purpose
- Improve clarity for English-speaking users
- Reduce ambiguity between document types
- Focus on essential service-related documentation
- Eliminate unnecessary or redundant categories

This structure aligns the system with real-world SVC usage and improves overall UX.

## 2026-05-04 — Hosting Migration (Kinsta → EasyWP)

### Summary
Temporary migration of the SVC WordPress site from Kinsta to Namecheap EasyWP to avoid billing risk and continue development safely.

### Reason
- Kinsta free trial period approaching expiration
- Hosting account tied to a personal credit card
- Required a fully managed, low-cost, short-term WordPress environment

### Details
- Source: Kinsta (Managed WordPress)
- Destination: Namecheap EasyWP (Starter, temporary)
- Migration tool: WPvivid Backup Plugin  
  (All‑in‑One WP Migration is banned on Kinsta)

### Result
- ✅ Full database and file restore completed successfully
- ✅ No content loss observed
- ✅ Development continues on EasyWP temporary URL
- 🔜 Kinsta scheduled for cancellation after verification window

## 2026-04-30

### Product Category Structure
- Official Product Category structure finalized for ZAC SVC Site
- Consolidated legacy ZAC categories and aligned with Shopify taxonomy
- Defined 8 top-level Product Categories with optional Sub Categories
- Designed for service/repair workflows and phased UI expansion

### Status
- Category structure approved and locked
- Used for sidebar navigation, archive pages, and future filtering


### Forms (Planned)

- Parts Order Form and Warranty Claim Form are defined as independent form pages
- Form implementation is intentionally deferred
  - Core site behavior focuses on search and reference use in current phase
  - Forms will be added incrementally without impacting search or product pages
- Navigation structure already includes hooks for future form pages

## 2026-04-24

### Design & Theme
- All page-level UI mockups have been completed in Figma
  - Landing Page
  - List / Search Result Pages
  - Product Detail Page
  - Common navigation and sidebar layout
- Figma mockups represent the final design target
  - Phase 1 implementation may use simplified UI where appropriate
  - Phase 2 features will align strictly with the completed Figma design

### WordPress Theme
- The site is implemented based on **WordPress Twenty Twenty-Five**
  - Block theme (Full Site Editing) architecture
  - Layout and shared UI components are implemented using Block Templates and Block Patterns
  - No classic PHP-based theme structure is assumed

### Product / Model Data Structure
- Product（CPT）登録を完了（30 models）
  - Capacity variants (e.g. 10 / 18) are consolidated into a single Product
  - Spec variants (e.g. A-suffix models) are handled as separate Products

### Taxonomy
- Product Category / Product Sub Category taxonomy confirmed and applied
  - All Products assigned exactly one Category and one Sub Category
  - Classification follows heating / control method (e.g. IH + Micom)

### Model Alias
- Model Alias (CPT) definition finalized and fully populated
- Introduced `Normalized Key` for series-level identification
  - Example: `nhs`, `ns_tsc`, `ns_tsc_a`
- Registered comprehensive Search Variations for all Products
  - Handles hyphen / no-hyphen, capacity numbers, and common typing variations
  - Base series search (e.g. `NS-TSC`) returns both standard and A-suffix models

### Status
- Product, taxonomy, and alias structures are complete
- UI / search interface implementation not started yet
- Data layer is finalized and ready for Phase 1 UI implementation

## 2026-04-17
- Defined core data model (Product / Part) based on prior requirements documents

- Defined user roles (TS, CS/Director, SVC) based on prior requirements documents

- Clarified external data source policy for parts price and inventory

- Defined core page types for ZAC SVC site

- Defined clear separation policy for ACF and taxonomy usage

- Add Project Status on  project-status.md

## 2026-04-16
- Product Tags を削除
  理由: Category/SubCategory と役割重複のため

- Used in Product taxonomy を廃止
  理由: Part が Product を知る構造を排除するため

## 2026-04-15
- Product Category / Sub Category を taxonomy として導入
