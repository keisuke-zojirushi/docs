# ZATI WP All Import Notes

## Purpose

This document records important WP All Import settings and lessons learned for Zojirushi America Technical Information (ZATI).

ZATI uses WordPress custom post types, ACF, CPT UI, Members, and WP All Import Pro.

---

## Important Rule

Do not use **Update all data** unless intentionally rebuilding all fields.

For Models and Parts, always prefer:

* Choose which data to update
* Limit updated fields
* Backup with WPvivid before import

---

## Models Import

### CPT

Models are stored as:

```text
post_type: model_alias
```

### Recommended Unique Identifier

```text
{post_title[1]}
```

### Product Category Setting

For hierarchical categories such as:

```text
Rice Cookers > Micom
Water Boilers > Electric Dispensing Pot
```

Use:

```text
Models have hierarchical parent/child Product Categories
An element in my file contains the entire hierarchy
Separated by: >
```

Import field:

```text
{product_category[1]}
```

### Safe Update Setting

When updating existing Models:

* Match existing models by Title
* Do not remove models not present in import file
* Do not use Update all data
* Update only necessary fields or taxonomies

### Incident

During a previous Models import, **Update all data** was selected.

This cleared existing ACF fields such as:

* diagram_image
* diagram_image2
* diagram_hotspots
* service_manual_pdf
* user_manual_pdf
* technical_info

The media files remained in the Media Library, but their associations with Models were lost.

---

## Parts Import

### CPT

Parts are stored as:

```text
post_type: part
```

### Correct Data Policy

One row should represent:

```text
1 model x 1 diagram number x 1 part
```

Even if the same Part No is shared by multiple models, each model should have its own part row.

Reason:

The same Part No may have different diagram numbers depending on the model.

---

## Parts Import Unique Identifier

### Incorrect Previous Setting

The previous setting was:

```text
{partnumber[1]}
```

This caused shared parts to overwrite each other.

Example:

```text
NS-WTC10 - No.4 - 8-NST-P030
NS-WPC10 - No.4 - 8-NST-P030
```

Because the Unique Identifier was only `partnumber`, WP All Import treated both as the same record.

Result:

The part was associated with only one model, and disappeared from other model detail pages.

### Correct Setting

Use:

```text
{model[1]}-{no[1]}
```

Example:

```text
NS-WTC10-4
NS-WPC10-4
NS-WRC10-4
```

This keeps shared Part Nos separate by model and diagram number.

---

## Parts Rebuild Procedure

When changing the Parts Unique Identifier from `partnumber` to `model-no`, do not simply rerun the import over existing data.

Recommended procedure:

1. Run WPvivid Backup
2. Delete all existing Parts CPT records
3. Empty Trash
4. Confirm Parts count is 0
5. Set WP All Import Unique Identifier to:

```text
{model[1]}-{no[1]}
```

6. Re-import `parts_master.csv`
7. Confirm model detail pages show complete parts lists

---

## Parts Search Display Rule

After rebuilding Parts with `{model[1]}-{no[1]}`, the same Part No exists multiple times across models.

This is correct for the database.

However, in keyword Parts Search, the top **Parts Results** should show each Part No only once.

The lower **Production List** should show all models using that part.

Example:

Search:

```text
8-NST-P030
```

Parts Results:

```text
8-NST-P030 / Lid Packing
```

Production List:

```text
NS-WPC10
NS-WRC10
NS-WTC10
NS-WSC10
NS-WAC10/18
NL-AAC10/18
NS-TSC10A/18A
NS-TSC10/18
```

---

## Search Result Page Notes

`page-model-search.php` supports:

* Model Search
* Category Search
* Parts Search fallback when no model is found
* Parts Results
* Production List
* Pagination

### Pagination

Pagination should work with:

```text
/model-search/?cat=165&subcat=0&per_page=25&paged=2
```

Avoid relying on:

```text
/model-search/page/2/
```

because custom page templates may not read `$_GET['paged']` from that URL format.

### Per Page Options

Allowed values:

```text
25
50
100
```

Default:

```text
50
```

---

## Model Visibility

The previous ACF `status` field had values such as:

* Active
* Hidden
* On Hold

This caused ambiguity.

Decision:

Use WordPress native post status instead.

Recommended rule:

* Public model: Published
* Hidden model: Private or Draft

The ACF `status` field is no longer necessary and should be removed or ignored.

---

## Parts Price Logic

ACF stores only:

```text
distributor_price
```

Displayed price is calculated by role.

Rules:

```text
Admin / Editor / ZAC TS Staff: distributor_price x 100%
MEXICO SVC: distributor_price x 100%
US&CANADA SVC: distributor_price x 82%
PARTS Retailor: distributor_price x 82% x 85%
```

Prices are rounded.

Function:

```text
zati_get_part_price_by_role()
```

Used in:

* Model Detail
* Parts Search
* Parts CSV download

---

## Current Important Lessons

* Do not use `Update all data` casually
* Always backup before import
* Models and Parts must have different import strategies
* Parts must be model-specific, not part-number-specific
* Category spelling must be controlled carefully
* Duplicate parent categories can occur if spelling differs, for example:

  * Rice Cooker
  * Rice Cookers
* Product Categories should be checked after each import
* Parts Search should deduplicate display by Part No, but database should keep model-specific rows
