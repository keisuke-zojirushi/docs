# ZATI Incident Log

## 2026-10-01 - Site blank screen / WPvivid AJAX error

### Symptoms
- ZATI pages displayed a blank screen.
- WPvivid showed:
  `SyntaxError: Unexpected token '<', "<style>..." is not valid JSON`
- WPvivid backup deletion appeared to stop.

### Cause
Inline `<style>` and `<script>` code for the Parts Import loading spinner
was output globally from `parts-import.php`.

This caused HTML to be included in responses that were expected to return JSON.

### Fix
Removed the global inline CSS and JavaScript from `parts-import.php`.

### Result
- ZATI site recovered.
- WPvivid backup deletion completed successfully.
- New backup created after recovery.

### Follow-up
Reimplement the Parts Import spinner using properly enqueued CSS/JS files.
