# Parts CSV Template

## Structure

| Column | Description |
|--------|------------|
| model | Model name (must match Product title) |
| diagram_no | Number on diagram |
| part_number | Internal part number |
| description | Part description |
| price | Numeric value only (no $) |
| alt_part_number | Optional external part number |

## Rules

- 1 row = 1 part per model
- Duplicate rows allowed for same part across models
- model must match Product post_title exactly

## Example

NS-WTC10,9,8-NSV-P090,Inner Lid set (C84),11.95,C84-6B
