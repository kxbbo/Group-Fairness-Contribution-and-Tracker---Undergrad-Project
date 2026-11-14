# FairShare views (PHP MVC and HTML)

The Evidence Vault (Feature 11) uses plain PHP views rendered by the controller and public front controller. No Blade engine or build step is required.

- `home.html`: project overview with a link to the Evidence Vault.
- `page-template.html`: static HTML starting point for future pages.
- `evidence/`: PHP views for task selection, evidence listing, and upload.
- `css/fairshare.css`: theme (colors, type, components) loaded after Bootstrap. Colors are variables at the top.
- `js/layout.js`: shared navbar and footer. Edit the `links` object when a page exists.
- `js/main.js`: page scripts. Add one JS file per feature to avoid merge conflicts.

Run `php -S localhost:8000` from the repository root, then open `http://localhost:8000/Resources/Views/home.html`. Bootstrap 5.3.3 and Google Fonts load from a CDN, so those styles require internet access.
