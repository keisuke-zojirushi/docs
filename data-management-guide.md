# Data Management Guide

## Purpose

This document defines how model and parts data should be created, updated, reviewed, and imported into the Zojirushi America Technical Information system.

The goal is to prevent accidental data loss, duplicate records, inconsistent model names, and incorrect pricing.

---

## Basic Policy

WordPress is not the master data source.

The master data should be maintained outside WordPress.

Recommended structure:

```text
SharePoint / Excel
↓
CSV Export
↓
WP All Import
↓
WordPress
```

WordPress should be treated as the display and search system, not the source of truth.

---

## Storage Location

Master files should be stored in SharePoint or another internal Zojirushi America controlled location.

Do not store master data in a public GitHub repository.

The following data should not be public:

* Model master data
* Parts master data
* Distributor prices
* Parts price files
* Warranty claim pricing data
* Internal service information

---

## Recommended Master Files

### 1. Model Master

Recommended file name:

```text
ZAC_Model_Master.xlsx
```

Purpose:

Maintains the official list of models shown in the system.

Recommended columns:

```csv
post_title,product_category,normalized_key,model_alias,search_keywords,canonical_model
```

Column meaning:

| Column           | Description                                   |
| ---------------- | --------------------------------------------- |
| post_title       | Official model title displayed on the website |
| product_category | Product category path                         |
| normalized_key   | Internal normalized search key                |
| model_alias      | Main model alias                              |
| search_keywords  | Alternate model names and search keywords     |
| canonical_model  | Standard model name used for matching         |

---

### 2. Parts Master

Recommended file name:

```text
ZAC_Parts_Master.xlsx
```

Purpose:

Maintains all service parts data.

Recommended columns:

```csv
model,no,partnumber,jpnumber,description,distributor_price,remarks
```

Column meaning:

| Column            | Description                                           |
| ----------------- | ----------------------------------------------------- |
| model             | Model title. Must match the model post title exactly  |
| no                | Exploded view item number                             |
| partnumber        | ZAC part number                                       |
| jpnumber          | Japan part number                                     |
| description       | Part description                                      |
| distributor_price | Master distributor price                              |
| remarks           | Notes, restrictions, substitutions, or other comments |

---

## Pricing Rule

Only one price should be entered manually:

```text
distributor_price
```

Other prices are calculated automatically by the system.

| Role                          | Calculation                   |
| ----------------------------- | ----------------------------- |
| Admin / Editor / ZAC TS Staff | Distributor Price × 100%      |
| US & CANADA SVC               | Distributor Price × 82%       |
| MEXICO SVC                    | Distributor Price × 100%      |
| PARTS Retailor                | Distributor Price × 82% × 85% |

Do not manually create separate price columns for SVC, Mexico, or Retailor unless the system specification changes.

---

## Data Entry Rules

### Model Names

The `model` value in Parts Master must match the `post_title` value in Model Master exactly.

Example:

```text
NH-VBC18
```

Avoid variations such as:

```text
NH VBC18
NH-VBC 18
NHVBC18
```

Those variations may be used as search keywords, but not as the official model value.

---

### Part Numbers

Part numbers should be entered consistently using the official ZAC part number format.

Avoid adding spaces unless they are part of the official number.

---

### Distributor Price

Use numeric values only.

Good:

```text
100
2.6
15.75
```

Avoid:

```text
$100
USD 100
100 dollars
```

The system will format and calculate prices during display.

---

## Update Workflow

### Step 1: Edit Master Excel

Make changes in the SharePoint master Excel file.

Do not edit WordPress records directly unless it is an emergency correction.

---

### Step 2: Review Changes

Before importing, review:

* New models
* Deleted models
* Changed model names
* New parts
* Deleted parts
* Changed part numbers
* Changed distributor prices

If multiple people are editing the file, confirm who made the changes and why.

---

### Step 3: Export CSV

Export the relevant sheet as CSV.

Recommended CSV files:

```text
model_master.csv
parts_master.csv
```

---

### Step 4: Backup WordPress

Before running WP All Import, create a backup or confirm that a recent backup exists.

Never run a large import without a backup.

---

### Step 5: Test Import Small Batch

Before importing all records, test with:

```text
1 model
5–10 parts
```

Confirm:

* Model Detail opens correctly
* Parts List displays correctly
* Price displays correctly
* Search Results display correctly
* CSV Download works

---

### Step 6: Run Full Import

After small-batch testing, run the full import.

Use WP All Import settings carefully to avoid deleting existing records unintentionally.

---

### Step 7: Verify Website

After import, check:

* Model Search
* Model Detail
* Parts Search
* Role-based pricing
* CSV Download
* Login role visibility

---

## Duplicate Prevention

Use stable unique keys for import.

Recommended unique keys:

### Models

```text
normalized_key
```

or

```text
post_title
```

### Parts

Recommended combined key:

```text
model + no + partnumber
```

This helps prevent duplicate parts when the same part number appears in multiple models.

---

## Deletion Policy

Do not delete WordPress records during import unless intentionally performing a controlled cleanup.

Recommended default:

```text
Do not delete missing records during import
```

If records need to be removed, first mark them in the master file with a status column such as:

```text
active
inactive
discontinued
```

Then hide inactive records from the website instead of deleting them.

---

## Recommended Status Column

For future improvement, add a status column to master files.

Example:

```csv
status
```

Allowed values:

```text
active
inactive
discontinued
draft
```

This is safer than deleting records.

---

## Access Control

Recommended access:

| Role           | Access                    |
| -------------- | ------------------------- |
| Admin          | Full access               |
| ZAC TS Staff   | Edit master data          |
| Editor         | Import and review         |
| SVC users      | No access to master files |
| Parts Retailor | No access to master files |

---

## GitHub Usage

Public GitHub may be used for documentation only.

Allowed in public GitHub:

* System specifications
* Change logs
* Design principles
* Data management procedures

Not allowed in public GitHub:

* Actual model master data
* Actual parts master data
* Distributor prices
* Warranty claim data
* Internal service documents

---

## Recovery Policy

If data is accidentally deleted from WordPress:

1. Do not immediately re-import everything.
2. Check the master Excel file.
3. Compare existing WordPress records with the master file.
4. Identify missing models or parts.
5. Re-import only the missing records first.
6. Verify the result.
7. Then proceed with broader recovery if needed.

---

## Current Recommended Next Step

Create or clean up the official master files:

```text
ZAC_Model_Master.xlsx
ZAC_Parts_Master.xlsx
```

Then compare them against current WordPress data to identify:

* Existing records
* Missing records
* Duplicate records
* Records requiring cleanup
