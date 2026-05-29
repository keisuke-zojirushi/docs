# ZAC SVC Site – Design Documentation

This folder contains the design foundation for the ZAC SVC site.

## Documents
- design-principles.md  
  Non-negotiable design principles (architecture-level decisions)

- baseline-spec.md  
  Current agreed implementation at this point in time

- change-log.md  
  History of decisions, removals, and redesign reasons

## How to use
- Do not change design-principles lightly
- baseline-spec may evolve
- All changes must be recorded in change-log

# ZAC SVC Site (Prototype)

## Overview
Internal support tool for Technical Support (SVC)
for viewing product diagrams and parts lists.

## Current Status
- WordPress based
- Custom Post Type: Model Alias
- Diagram (PNG) display
- Parts list (manual text → table)
- Fuzzy search implemented
- Status control (active / hidden)
- Image zoom (click to scale)

## Key Structure

### Model Alias
- diagram_image
- diagram_image_2 (optional)
- parts_list
- status

## Current Features
- Model search (including variations)
- Diagram + Parts display
- Zoom (basic)
- Horizontal layout (Flex)

## Known Limitations
- Parts data is manual
- No centralized parts DB
- Zoom is simple (no drag yet)

## Next Steps
- UI improvements (resizable split)
- Scroll separation
- Better zoom (pan / wheel)
- Possible ACF Pro introduction

## Project Structure

This repository contains documentation and partial implementation of the ZAC SVC system.

### Directory Structure
-wp-theme/
  twentytwentyfive-child/
    single-model_alias.php
    style.css

## UI Progress Log

### 2026-05-29

#### TOP Page

Completed:

* Sidebar Search Layout
* News Topics Integration
* Quick Links Section
* Category Navigation Section

Fixed:

* TwentyTwentyOne Theme CSS Conflicts
* Sidebar Layout Issues
* News Custom Post Type Rendering

Current Status:

* TOP Page Mock Version Implemented
* ZOIS-inspired Layout Applied

Next:

* Header Refinement
* Navigation Cleanup
* Responsive Layout
* News Scroll Area
* Category Dynamic Generation

---

## System Architecture

### Platform

WordPress

### Custom Post Types

* Models
* Parts
* zac_news

### Taxonomy

product_category

Example:

Rice Cookers
├─ Pressure IH
├─ IH
└─ Micom

Water Boilers
├─ VE
└─ Micom

---

## Design Goals

Inspired by:

* ZOIS (Zojirushi Online Information System)

Principles:

* Lightweight
* Fast
* Corporate Portal Style
* Minimal UI
* Service Center Focused

Avoid:

* Marketing Site Design
* Excessive Animations
* Heavy UI Frameworks


### Notes

- WordPress is used as the application layer
- Model Alias is the core data structure
- Diagrams are stored as PNG images
- Parts list is currently manually managed
