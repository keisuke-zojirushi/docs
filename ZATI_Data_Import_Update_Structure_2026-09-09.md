# ZATI Data Import & Update Operations Manual

**Version:** 1.0  
**Date:** 2026-09-09

## 1. Purpose

This manual standardizes how ZATI parts data is imported and updated using WP All Import so that TS-side administrators can perform the work safely and consistently.

## 2. Data Structure

| Data set | Purpose | Primary key | Frequency |
|---|---|---|---|
| Parts Master | Part identity / descriptive data | `partnumber` | As needed |
| Model Parts | Model-to-part relationship / drawing position | `{model}-{no}-{partnumber}` | When models or mappings change |
| Parts Daily Update | Current prices and inventory | `partnumber` | Daily / as available |

## 3. CSV Standards

### 3.1 Parts Master

Recommended file: `ZATI_PartsMaster.csv`

```text
partnumber
jpnumber
description
```

### 3.2 Model Parts

Recommended file: `ZATI_ModelParts.csv`

```text
model
no
partnumber
size
```

Recommended unique identifier:

```text
{model}-{no}-{partnumber}
```

### 3.3 Parts Daily Update

Recommended file: `ZATI_Parts_Daily_Update.csv`

```text
partnumber
distributor_price
retail_price
stock_04
stock_51
```

- `stock_04` = Warehouse 04 inventory
- `stock_51` = Warehouse 51 inventory
- ZATI already calculates US & Canada SVC, Mexico, and Parts Retailer prices from Dist Price, so those calculated prices do not need to be imported.

## 4. Parts Daily Update - Standard Procedure

**WP All Import job:** Import ID **48**

### Required settings

| Setting | Value |
|---|---|
| Import target | Parts |
| Existing match | Custom field |
| Custom field name | `partnumber` |
| Custom field value | `{partnumber[1]}` |
| Create new parts | OFF |
| Update existing parts | ON |
| Update scope | Choose which data to update |
| Custom fields to update | `distributor_price`, `retail_price`, `stock_04`, `stock_51` |
| Remove records not in CSV | OFF |

### Operator steps

1. Go to **Manage Imports**.
2. Open **Import ID 48**.
3. Open **Settings** and upload the latest `ZATI_Parts_Daily_Update.csv`.
4. Confirm the five CSV columns are correct.
5. Confirm matching is `partnumber = {partnumber[1]}`.
6. Confirm **Create new parts** is OFF.
7. Confirm only the four daily-update custom fields are selected.
8. Confirm removal of records not in the CSV is OFF.
9. Run the import.
10. Review the import summary and spot-check several Parts records.

## 5. Model Addition / Model Parts Import

Use for new models or model-to-part relationship changes.

- Prepare `ZATI_ModelParts.csv`.
- Required columns: `model`, `no`, `partnumber`, `size`.
- Use `{model}-{no}-{partnumber}` as the unique relationship key.
- Confirm referenced `partnumber` values already exist in Parts Master.
- Test with a small subset before a full import when changing structure.
- Verify Model Search, drawing position, and part display after import.

**Do not use the Daily Update job to create model relationships.**

## 6. Parts Master - Update / New Part Creation

Parts Master handles stable part identity information.

| Scenario | Behavior |
|---|---|
| Existing part information change | Match by `partnumber` and update only intended master fields |
| New part | Allow creation only in the Parts Master import job |
| Daily price / stock change | Use Import ID 48 instead |

Before allowing new-part creation:

- Confirm the part number does not already exist.
- Confirm `partnumber` is populated and unique.
- Confirm `description` and `jpnumber` mappings.
- Run a small test before a large import.

## 7. Import Job Register

| Import type | Import ID | Status |
|---|---:|---|
| Parts Daily Update | **48** | Confirmed and tested |
| Parts Master | To verify | Confirm exact existing job/settings |
| Model Parts | To verify | Confirm exact existing job/settings |

## 8. Safety Checklist

- [ ] Back up before a large or structural import.
- [ ] Confirm the correct CSV file and headers.
- [ ] Confirm the correct Import ID.
- [ ] Confirm matching key.
- [ ] Confirm whether new-record creation should be ON or OFF.
- [ ] Confirm exactly which fields may update.
- [ ] Confirm removal of records missing from the CSV is OFF unless explicitly intended.
- [ ] Use a 3-5 record test when changing an import definition.
- [ ] Review the WP All Import summary.
- [ ] Spot-check affected data on the ZATI front end.

## 9. TS Admin Documentation Placement

Recommended location inside ZATI:

```text
TS Admin
└── Documentation / System Manual
    └── Data Import & Update
        ├── Data Import Overview
        ├── Parts Daily Update (Import ID 48)
        ├── Parts Master - update/new parts
        ├── Model Parts - model addition
        ├── CSV Templates
        ├── Import ID Reference
        └── Troubleshooting / Recovery
```

Access should be limited to Technical Support and Administrator roles.

## 10. Recommended Documentation Files

- `ZATI_Data_Import_Operations_Manual.docx`
- `ZATI_Data_Import_Operations_Manual.md`
- `ZATI_Parts_Daily_Update.csv`
- `ZATI_PartsMaster.csv`
- `ZATI_ModelParts.csv`

## 11. Next Verification Tasks

- Confirm the existing Parts Master import ID and exact settings.
- Confirm the existing Model Parts import ID and exact settings.
- Run a controlled Parts Master update/new-part test.
- Run a controlled Model Parts model-addition test.
- Add screenshots of the confirmed WP All Import screens to the final manual.
- Create the TS Admin Documentation page and link the manual / CSV templates.
