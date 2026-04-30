# Change Log

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
