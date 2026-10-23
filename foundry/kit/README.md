# Design system kit

`design-system.kit.zip` is an Elementor kit that contains ONLY the design system of the division2 sites:
4 + 10 Global Colors, 4 + 13 Global Fonts (with tablet and mobile sizes) and a 1280 px content width. Nothing else.

Import it into a WordPress site: **Elementor > Tools > Import / Export Kit** (or via Elementor's REST import routes).
Elementor creates a new active Kit and *adds* these colours and fonts to the existing Kit, so import it into a site whose Kit has no custom colours yet
(a fresh Elementor install). Re-importing into a site that already has them duplicates every colour and font.

It is generated from the local foundry by `sites/two/export-live.php`. SHA-256: adabfbb51fac77661d05f3f6ae421c48204fa8c20942e474402b9931176804f3
