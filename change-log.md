# Change Log
## 2026-04-24

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
