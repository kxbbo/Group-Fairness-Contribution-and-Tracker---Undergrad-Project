# FairShare base template (Bootstrap 5, HTML, CSS, JS)

Open `index.html` in a browser, or use VS Code Live Server. No build step.

- `index.html`: home page (no functions).
- `page-template.html`: copy this for every new page (navbar and footer included).
- `css/fairshare.css`: theme (colors, type, components) loaded after Bootstrap. Colors are variables at the top.
- `js/layout.js`: shared navbar and footer. Edit the `links` object when a page exists.
- `js/main.js`: page scripts. Add one JS file per feature to avoid merge conflicts.

Bootstrap 5.3.3 and Google Fonts load from a CDN, so you need internet access.
