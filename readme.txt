=== Contract Pilot ===
Contributors: jjpsos
Donate link: https://www.softestate.net/contribution/
Tags: accounting, business, contracts
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 9.47.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Contract Pilot helps you manage contracts and related business records in WordPress. Visit the plugin site for features and contribute to the project.

== Description ==

Contract Pilot helps you manage contracts and related business records in WordPress. Go to https://www.softestate.net/contract-pilot/ for a showcase of advanced features. Thank you for your aid, https://www.softestate.net/contribution/, to this open source project.

See the Credits section for copyright holders, bundled libraries, and GPL attribution requirements.

== Credits ==

This plugin includes work by James Sosontovich (support@softestate.net) (see the root LICENSE file and plugin source headers). Additional contributors appear in bundled third-party libraries under the vendor directory. All named authors and copyright lines are part of this plugin’s GPL-licensed distribution and must be preserved in copies and derivatives, as required by the GNU General Public License.

Bundled third-party code includes Composer class loader components in the vendor directory. Former ByteKit plugin/model code is vendored inside this plugin under includes/Foundation and includes/Database (GPL-3.0+, Sultan Nasir Uddin / Byteever, adapted for Contract Pilot). See each package for its full license and copyright notices.

Bundled JavaScript and CSS in assets/ (human-readable source is included in this plugin; there is no separate minified or compiled build step for these files):

First-party JavaScript (Contract Pilot / James Sosontovich, GPL-2.0+):

* assets/scripts/admin.js — Admin UI: forms, settings, charts, datepickers, SelectWoo integration.
* assets/scripts/form.js — Form helper: event binding, values, blocking overlay.
* assets/scripts/modal.js — Modal dialogs (uses WordPress core wp-backbone; Backbone is not bundled).
* assets/scripts/line-chart.js — Dashboard and overview line charts (Canvas 2D; no external charting library).
* assets/scripts/amount-mask.js — Blur-only currency formatting for admin amount fields.
* assets/packages/money.js — Admin money helpers (format/unformat for USD/CAD).

Third-party JavaScript (full readable source bundled in this plugin):

* SelectWoo 1.0.11 — assets/scripts/select2.js — https://github.com/woocommerce/selectWoo (MIT). Includes readable Almond 0.3.3 (MIT, https://github.com/requirejs/almond) and jQuery Mousewheel 3.1.13 (MIT, https://github.com/jquery/jquery-mousewheel) as part of the same file.
* printThis — assets/scripts/printthis.js — https://github.com/jasonday/printThis (MIT).

First-party CSS (Contract Pilot / James Sosontovich, GPL-2.0+):

* assets/styles/admin.css — Admin styles (LTR); source of truth for admin styling, including datepicker layout rules.
* assets/styles/admin-rtl.css — Admin styles (RTL); generated from admin.css with rtlcss 4.3.0 (readable output, not minified). Regenerate: NPM_CONFIG_CACHE=/tmp/cp-npm-cache npx rtlcss@4.3.0 assets/styles/admin.css assets/styles/admin-rtl.css
* assets/styles/frontend.css — Public invoice and payment page styles.

First-party SVG assets (Contract Pilot / James Sosontovich, GPL-2.0+):

* assets/icon-128x128.svg, assets/icon-256x256.svg — Plugin icons.
* assets/banner-772x250.svg, assets/banner-1544x500.svg — Plugin banners.

WordPress.org plugin directory assets (not runtime code; *.asset.php files list script/style dependencies and versions for WordPress enqueue):

* assets/scripts/*.asset.php, assets/packages/money.asset.php, assets/styles/*.asset.php

WordPress core assets used but not bundled (loaded via wp_enqueue_script / wp_enqueue_style):

* jquery-ui-datepicker (script) — Date fields in admin.
* wp-jquery-ui-dialog (stylesheet) — Base jQuery UI widget styles for admin; Contract Pilot admin.css adds datepicker layout on top.
* wp-backbone (script) — Used by assets/scripts/modal.js only; Backbone and Underscore are not bundled in this plugin.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/contract-pilot/` (or install the ZIP via **Plugins → Add New → Upload Plugin**). The folder name should match the slug you use on your site; `contract-pilot` matches this plugin’s text domain.
2. Activate the plugin through the **Plugins** screen.
3. Use the **Contract Pilot** menu in the admin area to configure and manage data.

== Frequently Asked Questions ==

= Is this a fully functioning business app? =

Yes, all basic features are enabled. Additional custom Add-ons are available.

= How secure are payments in Contract Pilot? =

Contract Pilot follows WordPress security best practices for data handling and access control. Payment security and PCI scope depend on the gateway, processor, and deployment you choose, so review your provider's current compliance and security documentation.

= What about privacy and where is my data stored? =

Business and accounting information you enter (contacts, documents, transactions, settings, and related metadata) is stored in your WordPress site's database and files under your control. By default, Contract Pilot does not send that content to the plugin author's servers or third parties for processing. If you use optional integrations, hosting backups, or external services outside this plugin, those services follow their own privacy and retention policies.

= What would help support this open source project? =

Contributions are welcome.

= Where do I get support? =

Use the plugin support forum on WordPress.org once the plugin is listed, or the contact details provided by the plugin author.

== Features ==

* Admin Dashboard with sales, expenses, and profits reporting overview charts.
* Contracts (sales side): contracts/invoices, payments, and customers.
* Services (items catalog) for billable work used on documents.
* Purchases: expenses and aid.
* Banking safety area (toggle on/off Feature Access).
* Reports for sales, expenses, profits (including filters and breakdowns).
* Settings for general options, currencies, taxes, categories.
* CSV import and export are available as a separate add-on (see https://www.softestate.net/contract-pilot/).
* Admin AJAX for in-app actions (contract payments, bill expenses, service line items); no public REST API.
* WooCommerce compatibility layer (where applicable).
* Background processing via Action Scheduler (bundled) for scheduled tasks.
* Note: use only USD or CAD as default banking account currency.
* Showcase options (see plugin site): multi-currency bank accounts, shared links, clone contracts, email via SMTP, open PDF and Mail app, recurring (otto) contracts, payment-count (otto) clone.

== Changelog ==

= 9.47.4 =
* Removed built-in CSV import and export from the core plugin (available as a separate add-on; see https://www.softestate.net/contract-pilot/).
* Removed Tools admin screen, related capabilities, and filesystem helpers used only for import/export.

= 9.47.3 =
* Version bump release.

= 9.47.2 =
* Admin views: passive edit/view/list templates (Tier 2); handlers load data, views render only.
* Contracts/Bills: layout fixes for edit/view items table; service descriptions on print; discount UI hidden on read-only views.
* Expenses: view screen now uses the correct read-only template.
* Plugin Check: naming-convention fixes for hooks and template loop variables.

= 9.47.1 =
* Version bump release.

= 9.46.4 =
* Charts: replace bundled Chart.js with a custom, human-readable line-chart module (Canvas 2D) for dashboard, account, and customer overview charts.
* Amount fields: replace bundled Inputmask with a custom blur-only amount-mask module for admin currency inputs.
* Datepicker styling: remove minified jquery-ui.css; use WordPress core wp-jquery-ui-dialog plus readable datepicker rules in admin.css.
* Packaging: remove chartjs.js, inputmask.js, timepicker.js, and jquery-ui.css; document all bundled JavaScript, CSS, and SVG assets in readme Credits.

= 9.40.0 =
* Duplicate-prevention hardening: create-request idempotency added to contract/bill/payment/expense admin create flows.
* Numbering reliability: max-number cache invalidation wired to document/transaction save and delete lifecycles.
* Schema safety: guarded `(type, number)` unique index migration added for `pilot_documents` (migration halts with report when duplicates exist).
* Document number collision handling: bounded retries for auto-generated numbers and clear validation message for explicit duplicate numbers.

= 9.39.1 =
* Admin: removed promotional and review-related UI (plugin list review link, admin footer review text, legacy dismissible notice pipeline, database-update admin banner, unused notice view and stub classes).
* Packaging: readme Changelog and Upgrade Notice aligned with Stable tag; duplicate Installer and Bills PHP files removed so Composer classmaps stay unambiguous.
* General maintenance and compatibility updates on the 9.34.x–9.39.x line (intermediate tags are not listed individually in this readme).

= 9.33.9 =
* Contracts/Bills: removed attachment functionality from Contracts and Bills edit/view flows.
* Admin notices: disabled promotional/review notices to keep admin UI clean and stable.

= 9.33.8 =
* Settings: removed secret-code dependency from Feature Access; added direct enable/lock controls for current user.
* Settings UI: hid Access Control and Banking/Tools diagnostics from General view; updated tab ordering.

= 9.33.4 =
* Admin: streamline currency and exchange-rate fields where the site uses a single base currency (banking accounts, customers, contracts, payments, expenses, transfers).
* Readme: keep Stable tag and changelog in sync.

= 9.33.3 =
* WordPress.org readme: description, installation, FAQ, features, credits, Donate link, and contribution URLs.
* Copyright: James Sosontovich; plugin header and LICENSE updated.
* Initial public listing preparation: documentation, text domain, and compatibility updates; LICENSE and uninstall.php for packaging.

== Upgrade Notice ==

= 9.47.4 =
Built-in CSV import and export have been removed from the core plugin and are available as a separate add-on. No database changes.

= 9.47.3 =
Version 9.47.3 release.

= 9.47.2 =
Passive admin views, contract/bill layout and print fixes, expense view correction, and Plugin Check cleanups.

= 9.47.1 =
Version 9.47.1 release.

= 9.46.4 =
Replaces bundled Chart.js and Inputmask with lightweight custom admin modules. No database changes; charts and amount fields behave the same.

= 9.40.0 =
Duplicate-prevention and numbering integrity release. Includes idempotent create protection, cache invalidation for next-number reads, and a guarded unique `(type, number)` migration.

= 9.39.1 =
Maintenance and admin cleanup release.

= 9.33.9 =
Contracts/Bills attachment functionality removed and admin notices streamlined.

= 9.33.8 =
Feature Access now uses direct safety-lock toggles (no secret code), plus admin UI cleanup and tab-order refinements.

= 9.33.4 =
Currency and exchange-rate UI refinements; readme maintenance.

= 9.33.3 =
Maintenance, documentation, and WordPress.org listing preparation.
