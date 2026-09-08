# ZATI Development Instructions

## Project Overview
ZATI is an internal WordPress portal for Zojirushi America Technical Support and Service Centers.

## Tech Stack
- WordPress
- Twenty Twenty-One Child Theme
- Custom plugin: zati-tools
- ACF
- CPT UI
- Members
- WP All Import
- PHP
- JavaScript
- CSS

## Main Features
- Model Search
- Parts Search
- Parts Order
- Warranty Claim
- Backorder Management
- Parts Price List PDF
- Excel export
- Role-based access

## Important Roles
- administrator
- zac_ts
- us_canada_svc
- canada_parts_sales
- mexico_svc

## Coding Rules
- Do not rewrite large files unless necessary.
- Make the smallest possible change.
- Preserve existing working behavior.
- When proposing code changes, specify exactly:
  1. which file
  2. which existing block to find
  3. what to replace it with
- Do not change unrelated code.
- Check role permissions carefully.
- Preserve existing URL slugs and workflow.

## Important Files
- page-model-search.php
- single-model_alias.php
- page-parts-order.php
- page-parts-order-review.php
- page-parts-order-detail.php
- page-open-backorders.php
- page-backorder-history.php
- app-header.php
- app-footer.php
- zati-tools.php

## Parts Import Rules
- Parts should be uniquely identified by:
  {model}-{no}-{partnumber}
- Avoid using only partnumber as the unique key.
- For WP All Import updates, use:
  "Choose which data to update"
  instead of:
  "Update all data"

## Development Style
- Prefer incremental changes.
- Test one function at a time.
- Do not introduce new frameworks unless necessary.
- Maintain the existing ZOIS / Kintone-style UI familiarity.
