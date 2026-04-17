# Baseline Spec (2026-04)
Last updated: 2026-04-16


## Taxonomy
- Product Categories
- Product Sub Categories
※ Product Tags / Used in Product は使用しない

## ACF
- Product: Attributes + Aliases
- Part: Attributes（製品非依存）
- Product–Part: 図面番号 / Size / Qty

## Development Phases
- Phase 1: DB / フォーム / 基本UI
- Phase 2: Split Pane / Zoom / 図面×リスト連動
  
## Baseline Spec
ZAC SVC Site
Last updated: 2026-04-17

## 1. Scope
This document defines the current agreed baseline for the ZAC SVC site.
This baseline may evolve, but all changes must be recorded in change-log.md.
Fundamental architectural decisions are defined separately in design-principles.md.

## 2. Content Model Overview
2.1 Core Entities

### Product
Part
Product–Part (relationship / usage)

### Responsibilities are clearly separated:

Product = classification, naming, search
Part = pure part master
Product–Part = diagram position, size, usage context


## 3. Product Classification (Taxonomy)
### 3.1 Product Categories

Implemented as taxonomy
Taxonomy name: Product Categories
Assigned to: Products CPT
Purpose:

Primary product classification
Navigation, filtering, listing



### Examples

Rice Cooker
Water Boiler
Thermos


### 3.2 Product Sub Categories

Implemented as taxonomy
Taxonomy name: Product Sub Categories
Assigned to: Products CPT
Logical parent: Product Categories (operational rule)

#### Examples

IH
Micom
Commercial


Note:
Category/Sub Category are the only official classification axes.
Product Tags are not used.


## 4. Product Attributes (ACF)
### 4.1 Core Fields

Product Name
Series / Model Name
Remarks (optional)

### 4.2 Optional Fields

Size (only if meaningful at product level)
Release Date (optional)
Discontinued Date (optional)

Dates are informational only and do not affect structure or logic.

## 5. Product Alias (Search Normalization)
### 5.1 Purpose
Product Aliases are used to normalize search input and resolve naming variations.
### 5.2 Implementation

Implemented as ACF field on Product
Field type:

Repeater or multi-text field


Field name: product_aliases

### 5.3 Usage Rules

All aliases must resolve to one canonical Product
Aliases are not visible as classification
Aliases are for search and lookup purposes only

#### Examples

NW-QAC
NWQAC
NW-QAC10
NW-QAC18


## 6. Part Model (Reference)

Parts do not contain:

Product reference
Category
Diagram number


Part fields include:

ZAC Part Number
JPN Part Number
Description
Price (may vary by SVC context)
Global Notes




## 7. Product–Part Relationship (Preparation for Phase 2)
The Product–Part relationship represents how a part is used in a product.
Fields (not all implemented in Phase 1)

### Product
Part
Diagram No. (mandatory for diagram linkage)
Size (nullable)
Quantity
Product-specific notes


Diagram No. is not a part attribute.
It is strictly defined per Product–Part relationship.


## 8. Development Phases
### Phase 1 (Current Priority)

Database & ACF structure
Product taxonomy (Category / Sub Category)
Alias handling
Product & Part data entry
Basic UI and forms

### Phase 2 (Add-on Features)

Resizable Split Pane
Exploded View Zoom (in / out / reset)
Diagram ↔ Parts List bi-directional highlighting

### Phase 2 features must be implementable without database restructuring.

## 9. Explicit Exclusions
The following are intentionally not used in this baseline:

Product Tags
Used in Product taxonomy
Product Attributes as taxonomy

All such decisions are recorded in change-log.md.
