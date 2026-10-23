# Foundry changelog

Bump `foundry/VERSION` and add a line here for every change to `lib.php` or `build-foundry.php`.
Sites should note which foundry version they were last tested against.

## 1.1.1
- Fix: the header and footer logo (UAE Site Logo widget) now set `site_logo_fallback = yes`, so the widget uses its own image. Before, it only worked where WordPress itself had a Custom Logo (theme mod) set, and showed Elementor's grey default logo on a site without one.

## 1.1.0
- New Global Font `herotitle` (Hero title, 44/36/28 px): the Kit now has 4 + 14 fonts. Sites that already imported the 1.0.0 design-system kit must add it (see `foundry/kit/`).
- `$cVideos` takes an optional 4th argument `$videos` = `[[title, YouTube url], ...]`: those cards link to YouTube and use the video thumbnail as the card background. Without it the output is unchanged.

## 1.0.0
- First split into foundry + sites. Shared components extracted from the site-two build: `$cHeader`, `$cFooter`, `$cStatusBand`, `$cServices`, `$cLeadership`, `$cVideos`.
- New: `$cShell` (section skeleton) and `$cTodo` (labelled placeholder).
- New: component library (7 live Custom Blocks + 7 Saved Templates) and the private "Component guide" page.
- Elementor Global Colors / Fonts and the 1280 content width now set by `build-foundry.php` (previously inside the site build).
- Regression check: site two's Home, Header and Footer data are byte-identical before and after the extraction.
