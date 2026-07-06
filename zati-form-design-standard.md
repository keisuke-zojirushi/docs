# ZATI Form Design Standard v1.0

## Purpose
ZATI forms should provide a consistent internal business-system UI similar to ZAC/Kintone.

## Target Forms
- Warranty Claim Form
- Parts Order Form
- Repair Log Form
- Technical Inquiry Form

## Common UI
- ZATI header
- Sub navigation / breadcrumb
- Light cream form background
- Section dividers
- Yellow parts table header
- Blue primary buttons
- Small lookup/clear text actions
- Readonly gray fields
- Mobile responsive layout

## Parts Table Types

### Type A: Warranty / Repair
Columns:
- Parts No.
- Description
- Unit Price
- Action

Used for:
- Warranty Claim
- Repair Log

### Type B: Parts Order
Columns:
- Parts No.
- Description
- Quantity
- Unit Price
- Extended Price
- Action

Used for:
- Parts Order

## Money Fields
Use a common money input style:
- `$` prefix
- right-aligned value
- readonly fields are gray

## Warranty Claim Calculation
Total Payment =
Parts Price Total
+ Stocking Fee
+ Labor
+ Shipping Fee In
+ Shipping Fee Out

Stocking Fee = 10% of Parts Price Total

Default Labor Fee = $60.00/hr, editable if needed.

## Mobile Rule
- Main form fields collapse to one column
- Parts tables use horizontal scrolling
- Totals collapse to two columns or one column
