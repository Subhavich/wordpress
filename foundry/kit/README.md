# Design system kit

`design-system.kit.zip` is an Elementor kit that contains ONLY the design system of the division2 sites:
4 + 10 Global Colors, 4 + 13 Global Fonts (with tablet and mobile sizes) and a 1280 px content width. Nothing else.

Import it into a WordPress site: **Elementor > Tools > Import / Export Kit** (or via Elementor's REST import routes).
Elementor creates a new active Kit and *adds* these colours and fonts to the existing Kit, so import it into a site whose Kit has no custom colours yet
(a fresh Elementor install). Re-importing into a site that already has them duplicates every colour and font.

It is generated from the local foundry by `sites/two/export-live.php`. SHA-256: adabfbb51fac77661d05f3f6ae421c48204fa8c20942e474402b9931176804f3

## Files
- `design-system.kit.zip`: the full design system (foundry 1.0). Import once per site, into a Kit that has no custom colours yet.
- `design-system-1.1-additions.kit.zip`: ONLY the `Hero title` Global Font added in foundry 1.1.0. Import it into a site that already imported the 1.0 kit (it adds one font and changes nothing else; tested).
  A fresh site can skip it by importing a newly exported `design-system.kit.zip` (made by `sites/export-live.php`... or Elementor's own Export Kit) instead.
- `portrait-placeholder.png`: the grey placeholder photo used by the commander cards until real photos are uploaded.
