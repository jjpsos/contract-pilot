=== Contract Pilot ===
Contributors: jjpsos
Donate link: https://www.softestate.net/contribution/
Tags: accounting, business, contracts
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 9.33.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Contract Pilot helps you manage contracts and related business records in WordPress. Visit the plugin site for features, contribute to the project, and watch the video demo.

== Description ==

Contract Pilot helps you manage contracts and related business records in WordPress. Go to https://www.softestate.net/contract-pilot/ for a showcase of advanced features. Thank you for your aid, https://www.softestate.net/contribution/, to this open source project.

See the Credits section for copyright holders, bundled libraries, and GPL attribution requirements.

== Credits ==

This plugin includes work by James Sosontovich (jjpsos) (see the root LICENSE file and plugin source headers). Additional contributors appear in bundled third-party libraries under the vendor directory. All named authors and copyright lines are part of this plugin’s GPL-licensed distribution and must be preserved in copies and derivatives, as required by the GNU General Public License.

Bundled third-party code includes WooCommerce Action Scheduler (GPLv3, Automattic and contributors), ByteKit-related packages (GPL-3.0+, Sultan Nasir Uddin / Byteever), Composer class loader components, and other dependencies shipped in the vendor directory. See each package for its full license and copyright notices.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/otto-contracts/` (or install the ZIP via **Plugins → Add New → Upload Plugin**). The folder name should match the slug you use on your site; `otto-contracts` is recommended.
2. Activate the plugin through the **Plugins** screen.
3. Use the **Otto** menu in the admin area to configure and manage data.

== Frequently Asked Questions ==

= Is this a fully functioning business app? =

Yes, all basic features are enabled. Additional custom Add-ons are available.

= How secure are payments in Otto Contracts? =

Otto Contracts follows WordPress security best practices for data handling and access control. Payment security and PCI scope depend on the gateway, processor, and deployment you choose, so review your provider's current compliance and security documentation before production use.

= What would help support this open source project? =

1-2.3% of a business's profits per month as a donation.

= Where do I get support? =

Use the plugin support forum on WordPress.org once the plugin is listed, or the contact details provided by the plugin author.

== Screenshots ==

1. screenshot-1.png - Dashboard: overview charts, reports, and activity.
2. screenshot-2.png - Contracts: list or document view.
3. screenshot-3.png - Settings: Otto settings.

== Features ==

* Admin Dashboard with sales, expenses, and profits reporting tabs; overview chart.
* Services (items catalog) for billable work used on documents.
* Contracts (sales side): contracts/invoices, payments, and customers.
* Purchases: expenses and aid.
* Banking and Tools areas (additional capabilities may be gated behind Feature Access or custom Add-ons).
* Reports for sales, expenses, and profits (including year filters and month breakdowns).
* Settings for general options, currencies, taxes, categories, and Feature Access (access code for custom Add-ons).
* Importers and exporters for data interchange (Add-on).
* REST API routes for items, taxes, categories, customers, accounts, notes, expenses, payments, utilities, invoices, and bills.
* WooCommerce compatibility layer (where applicable).
* Background processing via Action Scheduler (bundled) for scheduled tasks.
* Notes: For default banking, use only USD or CAD as your base bank account currency. Multi-currency bank account calculations are a custom Add-on, not part of the base plugin.

== Changelog ==

= 9.33.3 =
* WordPress.org readme: description, installation, FAQ, features, credits, Donate link, and contribution URLs.
* Copyright: James Sosontovich; plugin header and LICENSE updated.
* Initial public listing preparation: documentation, text domain, and compatibility updates; LICENSE and uninstall.php for packaging.

== Upgrade Notice ==

= 9.33.3 =
Maintenance, documentation, and WordPress.org listing preparation.
