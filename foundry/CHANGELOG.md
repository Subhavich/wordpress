# Foundry changelog

Bump `foundry/VERSION` and add a line here for every change to `lib.php` or `build-foundry.php`.
Sites should note which foundry version they were last tested against.

## 1.0.0
- First split into foundry + sites. Shared components extracted from the site-two build: `$cHeader`, `$cFooter`, `$cStatusBand`, `$cServices`, `$cLeadership`, `$cVideos`.
- New: `$cShell` (section skeleton) and `$cTodo` (labelled placeholder).
- New: component library (7 live Custom Blocks + 7 Saved Templates) and the private "Component guide" page.
- Elementor Global Colors / Fonts and the 1280 content width now set by `build-foundry.php` (previously inside the site build).
- Regression check: site two's Home, Header and Footer data are byte-identical before and after the extraction.
