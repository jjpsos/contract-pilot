<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Models\Invoice;
use Jjpsos\ContractPilot\Utilities\Idempotency;
use Jjpsos\ContractPilot\Utilities\ReportsUtil;

defined("ABSPATH") || exit();

/**
 * Admin AJAX handlers for Contract Pilot.
 *
 * Security model (every wp_ajax_* callback):
 * 1. Nonce — check_ajax_referer() proves request came from our admin UI (CSRF protection).
 * 2. Capability — current_user_can() decides who may perform the action (authorization).
 * Nonces do not replace capability checks; both are required before reading $_POST/$_FILES.
 * Nonces localized in Scripts.php as contract_pilot_admin_vars and sent by admin.js.
 */
class Ajax
{
    public function __construct()
    {
        add_action("wp_ajax_contract_pilot_json_search", [$this, "handle_json_search"]);
        add_action("wp_ajax_contract_pilot_add_note", [$this, "handle_add_note"]);
        add_action("wp_ajax_contract_pilot_delete_note", [$this, "handle_delete_note"]);
        add_action("wp_ajax_contract_pilot_get_item", [$this, "get_item"]);
        add_action("wp_ajax_contract_pilot_get_invoice_for_payment", [
            $this,
            "get_invoice_for_payment",
        ]);
        add_action("wp_ajax_contract_pilot_create_invoice_payment", [
            $this,
            "create_invoice_payment",
        ]);
        add_action("wp_ajax_contract_pilot_get_invoice_address", [
            $this,
            "get_invoice_address",
        ]);
        add_action("wp_ajax_contract_pilot_get_recalculated_invoice", [
            $this,
            "get_recalculated_invoice",
        ]);
    }


    public function handle_json_search()
    {
        // Select2 sends search_nonce as _wpnonce (see admin.js).
        check_ajax_referer("contract_pilot_search_action", "_wpnonce");
        if (!current_user_can("contract_pilot_access")) {
            wp_send_json_error(
                [
                    "message" => esc_html__(
                        "You do not have permission to perform this search.",
                        "contract-pilot",
                    ),
                ],
                403,
            );
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $type = isset($_POST["type"])
            ? sanitize_text_field(wp_unslash($_POST["type"]))
            : "";
        $term = isset($_POST["term"])
            ? sanitize_text_field(wp_unslash($_POST["term"]))
            : "";
        $limit = isset($_POST["limit"]) ? absint($_POST["limit"]) : 20;
        $page = isset($_POST["page"]) ? absint($_POST["page"]) : 1;
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $results = [];
        $total = 0;

        $args = [
            "limit" => $limit,
            "page" => $page,
            "search" => $term,
            "no_count" => true,
        ];
        switch ($type) {
            case "account":
                $accounts = contract_pilot()->accounts->query($args);
                $total = contract_pilot()->accounts->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $accounts);
                break;
            case "item":
                $items = contract_pilot()->items->query($args);
                $total = contract_pilot()->items->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $items);
                break;
            case "category":
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified at start of handle_json_search().
                $args["type"] = isset($_POST["subtype"])
                    ? sanitize_text_field(wp_unslash($_POST["subtype"]))
                    : "";
                $categories = contract_pilot()->categories->query($args);
                $total = contract_pilot()->categories->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $categories);
                break;

            case "payment":
                $payments = contract_pilot()->payments->query($args);
                $total = contract_pilot()->payments->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->amount;

                    return $item->to_array();
                }, $payments);
                break;

            case "expense":
                $expenses = contract_pilot()->expenses->query($args);
                $total = contract_pilot()->expenses->query($args, true);
                foreach ($expenses as $expense) {
                    $results[] = [
                        "id" => $expense->id,
                        "text" => $expense->amount,
                    ];
                }
                break;

            case "customer":
                $customers = contract_pilot()->customers->query($args);
                $total = contract_pilot()->customers->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $customers);
                break;

            case "invoice":
                $args["status__not"] = "paid";
                $invoices = contract_pilot()->invoices->query($args);
                $total = contract_pilot()->invoices->query($args, true);
                foreach ($invoices as $invoice) {
                    $results[] = [
                        "id" => $invoice->id,
                        "text" => $invoice->number,
                    ];
                }
                break;
            case "tax":
                $tax_rates = contract_pilot()->taxes->query($args);
                $total = contract_pilot()->taxes->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $tax_rates);
                break;

            case "page":
                $wp_query = new \WP_Query([
                    "post_type" => "page",
                    "posts_per_page" => $limit,
                    "paged" => $page,
                    "s" => $term,
                ]);

                $pages = $wp_query->get_posts();
                $total = $wp_query->found_posts;
                foreach ($pages as $_page) {
                    $results[] = [
                        "id" => $_page->ID,
                        "text" => empty($_page->post_title)
                            ? __("(No title)", "contract-pilot")
                            : wp_strip_all_tags($_page->post_title),
                    ];
                }


                $wp_query->reset_postdata();
                break;

            default:
                $filtered = apply_filters(
                    "contract_pilot_json_search",
                    [
                        "results" => $results,
                        "total" => $total,
                    ],
                    $type,
                    $term,
                    $limit,
                    $page,
                );
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated pre-2.0 filter; contract_pilot_json_search is canonical.
                if (has_filter("pilot_accounting_json_search")) {
                    $filtered = apply_filters_deprecated(
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated pre-2.0 filter; contract_pilot_json_search is canonical.
                        "pilot_accounting_json_search",
                        [
                            $filtered,
                            $type,
                            $term,
                            $limit,
                            $page,
                        ],
                        "2.0.0",
                        "contract_pilot_json_search",
                    );
                }

                $results = $filtered["results"];
                $total = $filtered["total"];
                break;
        }

        wp_send_json([
            "results" => $results,
            "total" => $total,
            "page" => $page,
            "pagination" => [
                "more" => $page * $limit < $total,
            ],
        ]);
    }


    public function handle_add_note()
    {
        check_ajax_referer("contract_pilot_add_note", "nonce");

        if (!current_user_can("contract_pilot_edit_notes")) {
            wp_die(-1);
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $parent_id = isset($_POST["parent_id"])
            ? absint(wp_unslash($_POST["parent_id"]))
            : 0;
        $parent_type = isset($_POST["parent_type"])
            ? sanitize_key(wp_unslash($_POST["parent_type"]))
            : "";
        $content = isset($_POST["content"])
            ? sanitize_textarea_field(wp_unslash($_POST["content"]))
            : "";
        // phpcs:enable WordPress.Security.NonceVerification.Missing


        if (empty($parent_id) || empty($parent_type) || empty($content)) {
            wp_die(-1);
        }

        $note = contract_pilot()->notes->insert([
            "parent_id" => $parent_id,
            "parent_type" => $parent_type,
            "content" => $content,
            "author_id" => get_current_user_id(),
        ]);


        if (is_wp_error($note)) {
            wp_die(-1);
        }

        $note_html = contract_pilot_render_admin_view_html('partials/note-item', [
            'note' => $note,
        ]);

        $x = new \WP_Ajax_Response();
        $x->add([
            "what" => "note_html",
            "data" => $note_html,
        ]);

        $x->send();
    }


    public function handle_delete_note()
    {
        check_ajax_referer("contract_pilot_delete_note", "nonce");

        if (!current_user_can("contract_pilot_delete_notes")) {
            wp_die(-1);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $note_id = isset($_POST["note_id"])
            ? absint(wp_unslash($_POST["note_id"]))
            : 0;
        $note = contract_pilot()->notes->get($note_id);

        if (!$note) {
            wp_die(-1);
        }

        $note->delete();

        wp_die(1);
    }


    public function get_item()
    {
        // CSRF: admin.js sends admin_nonce as "nonce".
        check_ajax_referer("contract_pilot_admin_action", "nonce");

        if (!current_user_can("contract_pilot_read_items")) {
            wp_send_json_error([
                "message" => __(
                    "You do not have permission to perform this action.",
                    "contract-pilot",
                ),
            ]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $item = contract_pilot()->items->get($id);
        if (!$item) {
            wp_send_json_error([
                "message" => __("Service not found.", "contract-pilot"),
            ]);
        }

        $taxes = [];
        foreach ($item->taxes as $tax) {
            $taxes[] = [
                "id" => (int) $tax->id,
                "name" => $tax->name,
                "rate" => (float) $tax->rate,
                "compound" => (bool) $tax->compound,
            ];
        }

        wp_send_json_success([
            "id" => (int) $item->id,
            "name" => $item->name,
            "description" => $item->description,
            "price" => (float) $item->price,
            "cost" => (float) $item->cost,
            "type" => $item->type,
            "unit" => $item->unit,
            "taxes" => $taxes,
        ]);
    }


    public function get_invoice_for_payment()
    {
        // CSRF: admin.js sends admin_nonce as "nonce".
        check_ajax_referer("contract_pilot_admin_action", "nonce");

        if (!current_user_can("contract_pilot_edit_invoices")) {
            wp_send_json_error([
                "message" => __(
                    "You do not have permission to perform this action.",
                    "contract-pilot",
                ),
            ]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $invoice = contract_pilot()->invoices->get($id);
        if (!$invoice) {
            wp_send_json_error([
                "message" => __("Contract not found.", "contract-pilot"),
            ]);
        }

        wp_send_json_success([
            "due_amount" => $invoice->get_due_amount(),
            "currency" => $invoice->currency,
        ]);
    }


    public function create_invoice_payment()
    {
        // CSRF: admin.js sends admin_nonce as "nonce".
        check_ajax_referer("contract_pilot_admin_action", "nonce");

        if (!current_user_can("contract_pilot_edit_invoices")) {
            wp_send_json_error([
                "message" => __(
                    "You do not have permission to perform this action.",
                    "contract-pilot",
                ),
            ]);
        }

        $request_lock_hash = "";
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $idempotency_key = isset($_POST["_cp_idempotency_key"])
            ? sanitize_text_field(wp_unslash($_POST["_cp_idempotency_key"]))
            : "";
        if ("" !== $idempotency_key) {
            $idempotency = Idempotency::acquire_request_lock(
                "contract_pilot_ajax_create_payment",
                $idempotency_key,
                "ajax_payment",
            );
            if (empty($idempotency["ok"])) {
                wp_send_json_error([
                    "message" => Idempotency::get_error_message(
                        (string) ($idempotency["status"] ?? ""),
                    ),
                ]);
            }
            $request_lock_hash = (string) ($idempotency["request_hash"] ?? "");
        }

        $invoice_id = isset($_POST["invoice_id"])
            ? absint(wp_unslash($_POST["invoice_id"]))
            : 0;
        $invoice = contract_pilot()->invoices->get($invoice_id);
        if (!$invoice) {
            if ("" !== $request_lock_hash) {
                Idempotency::release_request_lock($request_lock_hash);
            }
            wp_send_json_error([
                "message" => __("Contract not found.", "contract-pilot"),
            ]);
        }

        $account_id = isset($_POST["account_id"])
            ? absint(wp_unslash($_POST["account_id"]))
            : 0;
        if (!contract_pilot()->accounts->get($account_id)) {
            if ("" !== $request_lock_hash) {
                Idempotency::release_request_lock($request_lock_hash);
            }
            wp_send_json_error([
                "message" => __("Account not found.", "contract-pilot"),
            ]);
        }

        $payment_date = isset($_POST["payment_date"])
            ? sanitize_text_field(wp_unslash($_POST["payment_date"]))
            : "";
        $amount = isset($_POST["amount"]) ? floatval(wp_unslash($_POST["amount"])) : 0;
        $exchange_rate = isset($_POST["exchange_rate"])
            ? floatval(wp_unslash($_POST["exchange_rate"]))
            : 1;
        if ($exchange_rate <= 0) {
            $exchange_rate = 1;
        }

        $payment = contract_pilot()->payments->insert([
            "payment_date" => $payment_date
                ? get_gmt_from_date($payment_date)
                : "",
            "account_id" => $account_id,
            "amount" => $amount,
            "exchange_rate" => $exchange_rate,
            "invoice_id" => $invoice_id,
            "contact_id" => isset($_POST["customer_id"])
                ? absint(wp_unslash($_POST["customer_id"]))
                : (int) $invoice->contact_id,
            "category_id" => isset($_POST["category_id"])
                ? absint(wp_unslash($_POST["category_id"]))
                : 0,
            "payment_method" => isset($_POST["payment_method"])
                ? sanitize_text_field(wp_unslash($_POST["payment_method"]))
                : "",
            "reference" => isset($_POST["reference"])
                ? sanitize_text_field(wp_unslash($_POST["reference"]))
                : "",
            "note" => isset($_POST["note"])
                ? sanitize_textarea_field(wp_unslash($_POST["note"]))
                : "",
            "editable" => false,
        ]);
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if (is_wp_error($payment)) {
            if ("" !== $request_lock_hash) {
                Idempotency::release_request_lock($request_lock_hash);
            }
            wp_send_json_error(["message" => $payment->get_error_message()]);
        }

        if ("" !== $request_lock_hash) {
            Idempotency::consume_request_lock($request_lock_hash);
        }

        wp_send_json_success([
            "id" => (int) $payment->id,
            "message" => __("Payment added successfully.", "contract-pilot"),
        ]);
    }


    public function get_invoice_address()
    {
        // Nonce comes from wp_nonce_field( 'contract_pilot_edit_invoice' ) on the invoice form.
        check_ajax_referer("contract_pilot_edit_invoice");

        if (!current_user_can("contract_pilot_edit_invoices")) {
            wp_die(-1);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $customer_id = isset($_POST["contact_id"])
            ? absint(wp_unslash($_POST["contact_id"]))
            : 0;
        $customer = contract_pilot()->customers->get($customer_id);
        if (!$customer) {
            wp_die(-1);
        }
        $invoice = new Invoice();
        $invoice->contact_id = $customer_id;
        $invoice->contact_company = $customer->company;
        $invoice->contact_name = $customer->name;
        $invoice->contact_email = $customer->email;
        $invoice->contact_phone = $customer->phone;
        $invoice->contact_address = $customer->address;
        $invoice->contact_city = $customer->city;
        $invoice->contact_state = $customer->state;
        $invoice->contact_postcode = $customer->postcode;
        $invoice->contact_country = $customer->country;
        $invoice->contact_tax_number = $customer->tax_number;

        $html = contract_pilot_render_admin_view_html('partials/invoice-address', [
            'invoice' => $invoice,
        ]);

        $x = new \WP_Ajax_Response();
        $x->add([
            "what" => "billings_html",
            "data" => $html,
        ]);

        $x->send();

        wp_die(1);
    }


    public function get_recalculated_invoice()
    {
        // Nonce comes from wp_nonce_field( 'contract_pilot_edit_invoice' ) on the invoice form.
        check_ajax_referer("contract_pilot_edit_invoice");

        if (!current_user_can("contract_pilot_edit_invoices")) {
            wp_die(-1);
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
        $invoice_attributes = Request::sanitize_invoice_post();
        $items = $invoice_attributes['items'];
        $invoice = Invoice::make($invoice_attributes);
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $invoice->items = [];
        $invoice->set_items($items);
        $invoice->calculate_totals();
        $columns = contract_pilot()->invoices->get_columns();

        if (!$invoice->is_taxed()) {
            unset($columns["tax"]);
        }

        $items_html = contract_pilot_render_admin_view_html('partials/invoice-items', [
            'invoice' => $invoice,
            'columns' => $columns,
        ]);

        $totals_html = contract_pilot_render_admin_view_html('partials/invoice-totals', [
            'invoice' => $invoice,
            'columns' => $columns,
        ]);

        $x = new \WP_Ajax_Response();

        $x->add([
            "what" => "items_html",
            "id" => "items_html",
            "data" => $items_html,
        ]);
        $x->add([
            "what" => "totals_html",
            "id" => "totals_html",
            "data" => $totals_html,
        ]);

        $x->send();

        wp_die(1);
    }
}
