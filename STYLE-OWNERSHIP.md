# Integra Style Ownership

This file defines the style ownership contract for the sibling Integra plugins:

- `PL-Integra-Core`
- `PL-Integra-Navigation`
- `PL-Integra-Blocks`

The goal is simple: every selector family and every layer of styling should have one owner.

## Ownership

### `PL-Integra-Core`

Owns the shared foundation:

- design tokens and runtime config values in `--in-*`
- global reset and base typography
- shared primitives such as `.in-btn*`
- shared layout primitives such as `.in-container*`
- shared utility classes such as color helpers

`PL-Integra-Core` is the only plugin that should ship:

- unscoped element selectors like `html`, `body`, `h1`, `a`, `ul`
- shared `.in-*` primitives intended for reuse across sibling plugins
- global `:root` token definitions for the shared design system

### `PL-Integra-Navigation`

Owns the navigation feature layer:

- `#pl-integra-navigation`
- `#in-*` navigation chrome and structure
- `.pl-integra-navigation-*` admin UI
- navigation-only CSS custom properties

`PL-Integra-Navigation` may consume Core primitives such as:

- `.in-container`
- `.in-btn*`
- shared `--in-*` token values

`PL-Integra-Navigation` should not redefine:

- `.in-btn*`
- `.in-container*`
- shared global typography/reset rules

### `PL-Integra-Blocks`

Owns the block feature layer:

- `.ib-*` frontend block classes
- block editor styles and block-specific layout rules
- block-scoped inline custom properties written per block instance

`PL-Integra-Blocks` may consume Core primitives such as:

- `.in-btn*`
- shared `--in-*` token values

`PL-Integra-Blocks` should not redefine:

- global reset/type selectors
- shared `.in-container*`
- shared `.in-btn*`
- shared token definitions in `:root`

## Rules

1. Generic element selectors belong in Core only.

2. Shared reusable classes belong in Core only.

3. Feature plugins must stay rooted to their namespace:

- Navigation frontend: `#pl-integra-navigation`, `#in-*`
- Navigation admin: `.pl-integra-navigation-*`
- Blocks frontend/editor: `.ib-*`

4. Feature-local CSS variables should be scoped to the feature root, not written globally to `:root`, unless they are true shared design-system tokens.

5. If a selector can be used by more than one plugin, it belongs in Core. If it only makes sense inside one feature, it belongs in that feature plugin.

## Practical Split

Use this split when adding styles:

- shared button, container, typography, color utility, reset: Core
- nav shell, dropdowns, scroll tracker, nav admin: Navigation
- block wrappers, block layouts, block editor panels, block-only typography helpers: Blocks

## Quick Review Checklist

Before adding or changing styles:

1. Does this selector start with `.in-` and look reusable across plugins?
   Put it in Core.

2. Does this selector only make sense inside navigation?
   Keep it under the Navigation root.

3. Does this selector only make sense for blocks or block editor output?
   Keep it in Blocks.

4. Does this change add an unscoped element selector outside Core?
   Stop and move it.
