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
