# ZATI Data Import / Update Structure

**Date:** September 9, 2026

## 1. Purpose

Organize Parts-related data into clear import/update groups so that ZATI can be maintained safely and daily price/stock data can be refreshed without overwriting unrelated master data.

## 2. Data Structure Overview

| No. | Data Type | Primary Purpose |
|---|---|---|
| 1 | Parts Master | Core part information |
| 2 | Model Parts | Relationship between models and parts |
| 3 | Parts Daily Update | Daily-updated price and inventory data |

## 3. Parts Master

**Purpose:** Stores the basic identity and description of each part. One part equals one master record.

| Field | Description |
|---|---|
| Part Number | Primary US part number / common key |
| JP Part Number | Japanese part number |
| Description | Part name / description |

- **Primary key:** `Part Number`
- **Suggested file name:** `ZATI_PartsMaster.csv`

## 4. Model Parts

**Purpose:** Defines which parts are used by each model and where they appear in the model diagram.

| Field | Description |
|---|---|
| Model | Model number |
| Drawing No. | Drawing / position number |
| Part Number | Part used by the model |
| Size | Size information when applicable |

- **Relationship key:** `Part Number`
- **Unique ID convention:** `{model}-{no}-{partnumber}`
- **Suggested file name:** `ZATI_ModelParts.csv`

## 5. Parts Daily Update

**Purpose:** Updates fields that change frequently in company source data. This combines price and inventory in one daily update file.

| Field | Description |
|---|---|
| Part Number | Key used to match an existing part |
| Retail Price | Latest company Retail Price |
| Dist Price | Latest company Distributor Price |
| Stock 04 | Inventory at company warehouse 04 |
| Stock 51 | Inventory at Mitsubishi warehouse 51 |

- **Suggested file name:** `ZATI_Parts_Daily_Update.csv`

## 6. Price Logic

Retail Price and Dist Price are imported directly from the company data source.

ZATI already contains the calculation logic for service-channel prices derived from Dist Price:

- US & Canada SVC Price
- Mexico SVC Price
- Parts Retailer Price

Therefore, the daily CSV does **not** need separately calculated SVC prices. Updating `Dist Price` is sufficient for ZATI to calculate the applicable channel prices using the existing site logic.

## 7. Inventory Logic

The two inventory sources planned for ZATI are:

- `04` = Company warehouse
- `51` = Mitsubishi warehouse

Because these stock counts are updated daily, they belong in the same Parts Daily Update file as Retail Price and Dist Price.

## 8. WP All Import Update Policy

For the daily update import, `Part Number` should be used as the matching key.

Only the following fields should be updated:

- Retail Price
- Dist Price
- Stock 04
- Stock 51

The daily update should **not** overwrite:

- JP Part Number
- Description
- Model Parts
- Drawing No.
- Size
- Other master/model data

## 9. Final Data Model

```text
ZATI
├─ 1. Parts Master
│  ├─ Part Number
│  ├─ JP Part Number
│  └─ Description
├─ 2. Model Parts
│  ├─ Model
│  ├─ Drawing No.
│  ├─ Part Number
│  └─ Size
└─ 3. Parts Daily Update
   ├─ Part Number
   ├─ Retail Price
   ├─ Dist Price
   ├─ Stock 04
   └─ Stock 51
```

## 10. Operational Use

| Timing | Import File | Purpose |
|---|---|---|
| Initial setup / master changes | `ZATI_PartsMaster.csv` | Create or update core part information |
| Initial setup / model changes | `ZATI_ModelParts.csv` | Create or update model-part relationships |
| Daily / frequent update | `ZATI_Parts_Daily_Update.csv` | Update Retail Price, Dist Price, Stock 04 and Stock 51 only |


## 11.　WP All Import
Import ID: 48
- Purpose: Parts Daily Update
- Match Key: partnumber
- Updates: distributor_price, retail_price, stock_04, stock_51
