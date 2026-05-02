<?php

namespace Otto\Admin;

use Otto\Models\Bill;
use Otto\Models\Invoice;
use Otto\Utilities\FileSystemUtil;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Ajax
{
    
    public function __construct()
    {
        add_action("wp_ajax_eac_json_search", [$this, "handle_json_search"]);
        add_action("wp_ajax_eac_add_note", [$this, "handle_add_note"]);
        add_action("wp_ajax_eac_delete_note", [$this, "handle_delete_note"]);
        add_action("wp_ajax_eac_add_invoice_payment", [
            $this,
            "add_invoice_payment",
        ]);
        add_action("wp_ajax_eac_get_bill_address", [$this, "get_bill_address"]);
        add_action("wp_ajax_eac_get_recalculated_bill", [
            $this,
            "get_recalculated_bill",
        ]);
        add_action("wp_ajax_eac_get_invoice_address", [
            $this,
            "get_invoice_address",
        ]);
        add_action("wp_ajax_eac_get_recalculated_invoice", [
            $this,
            "get_recalculated_invoice",
        ]);
        add_action("wp_ajax_eac_ajax_export", [$this, "ajax_export"]);
        add_action("wp_ajax_eac_upload_import_file", [
            $this,
            "ajax_upload_import_file",
        ]);
        add_action("wp_ajax_eac_ajax_import", [$this, "ajax_import"]);
    }

    
    public function handle_json_search()
    {
        check_ajax_referer("eac_search_action");
        $type = isset($_POST["type"])
            ? sanitize_text_field(wp_unslash($_POST["type"]))
            : "";
        $term = isset($_POST["term"])
            ? sanitize_text_field(wp_unslash($_POST["term"]))
            : "";
        $limit = isset($_POST["limit"]) ? absint($_POST["limit"]) : 20;
        $page = isset($_POST["page"]) ? absint($_POST["page"]) : 1;
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
                $accounts = EAC()->accounts->query($args);
                $total = EAC()->accounts->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $accounts);
                break;
            case "item":
                $items = EAC()->items->query($args);
                $total = EAC()->items->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $items);
                break;
            case "category":
                $args["type"] = isset($_POST["subtype"])
                    ? sanitize_text_field(wp_unslash($_POST["subtype"]))
                    : "";
                $categories = EAC()->categories->query($args);
                $total = EAC()->categories->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $categories);
                break;

            case "payment":
                $payments = EAC()->payments->query($args);
                $total = EAC()->payments->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->amount;

                    return $item->to_array();
                }, $payments);
                break;

            case "expense":
                $expenses = EAC()->expenses->query($args);
                $total = EAC()->expenses->query($args, true);
                foreach ($expenses as $expense) {
                    $results[] = [
                        "id" => $expense->id,
                        "text" => $expense->amount,
                    ];
                }
                break;

            case "customer":
                $customers = EAC()->customers->query($args);
                $total = EAC()->customers->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $customers);
                break;

            case "vendor":
                $vendors = EAC()->vendors->query($args);
                $total = EAC()->vendors->query($args, true);
                $results = array_map(function ($item) {
                    $item->text = $item->formatted_name;

                    return $item->to_array();
                }, $vendors);
                break;
            case "invoice":
                $args["status__not"] = "paid";
                $invoices = EAC()->invoices->query($args);
                $total = EAC()->invoices->query($args, true);
                foreach ($invoices as $invoice) {
                    $results[] = [
                        "id" => $invoice->id,
                        "text" => $invoice->number,
                    ];
                }
                break;
            case "bill":
                $args["status__not"] = "paid";
                $bills = EAC()->bills->query($args);
                $total = EAC()->bills->query($args, true);
                foreach ($bills as $bill) {
                    $results[] = [
                        "id" => $bill->id,
                        "text" => $bill->number,
                    ];
                }
                break;
            case "tax":
                $tax_rates = EAC()->taxes->query($args);
                $total = EAC()->taxes->query($args, true);
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
                            ? __("(No title)", "otto-contracts")
                            : wp_strip_all_tags($_page->post_title),
                    ];
                }

                
                $wp_query->reset_postdata();
                break;

            default:
                $filtered = apply_filters(
                    "otto_accounting_json_search",
                    [
                        "results" => $results,
                        "total" => $total,
                    ],
                    $type,
                    $term,
                    $limit,
                    $page,
                );

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
        check_ajax_referer("eac_add_note", "nonce");

        if (!current_user_can("eac_edit_notes")) {
            
            wp_die(-1);
        }

        $parent_id = isset($_POST["parent_id"])
            ? absint(wp_unslash($_POST["parent_id"]))
            : 0;
        $parent_type = isset($_POST["parent_type"])
            ? sanitize_key(wp_unslash($_POST["parent_type"]))
            : "";
        $content = isset($_POST["content"])
            ? sanitize_textarea_field(wp_unslash($_POST["content"]))
            : "";

        
        if (empty($parent_id) || empty($parent_type) || empty($content)) {
            wp_die(-1);
        }

        $note = EAC()->notes->insert([
            "parent_id" => $parent_id,
            "parent_type" => $parent_type,
            "content" => $content,
            "author_id" => get_current_user_id(),
        ]);

        
        if (is_wp_error($note)) {
            wp_die(-1);
        }

        ob_start();
        include __DIR__ . "/views/note-item.php";
        $note_html = ob_get_clean();

        $x = new \WP_Ajax_Response();
        $x->add([
            "what" => "note_html",
            "data" => $note_html,
        ]);

        $x->send();
    }

    
    public function handle_delete_note()
    {
        check_ajax_referer("eac_delete_note", "nonce");

        if (!current_user_can("eac_delete_notes")) {
            
            wp_die(-1);
        }

        $note_id = isset($_POST["note_id"])
            ? absint(wp_unslash($_POST["note_id"]))
            : 0;
        $note = EAC()->notes->get($note_id);

        if (!$note) {
            wp_die(-1);
        }

        $note->delete();

        wp_die(1);
    }

    
    public function add_invoice_payment()
    {
        check_ajax_referer("eac_add_invoice_payment");

        if (!current_user_can("eac_edit_invoices")) {
            
            wp_send_json_error([
                "message" => __(
                    "You do not have permission to add payment for invoice.",
                    "otto-contracts",
                ),
            ]);
        }

        $invoice_id = isset($_POST["invoice_id"])
            ? absint(wp_unslash($_POST["invoice_id"]))
            : 0;
        $account_id = isset($_POST["account_id"])
            ? absint(wp_unslash($_POST["account_id"]))
            : 0;
        $category_id = isset($_POST["category_id"])
            ? absint(wp_unslash($_POST["category_id"]))
            : 0;
        $exchange = isset($_POST["exchange_rate"])
            ? floatval(wp_unslash($_POST["exchange_rate"]))
            : "";
        $date = isset($_POST["payment_date"])
            ? sanitize_text_field(wp_unslash($_POST["payment_date"]))
            : "";
        $reference = isset($_POST["reference"])
            ? sanitize_text_field(wp_unslash($_POST["reference"]))
            : "";
        $note = isset($_POST["note"])
            ? sanitize_text_field(wp_unslash($_POST["note"]))
            : "";

        $invoice = EAC()->invoices->get($invoice_id);
        if (!$invoice) {
            wp_send_json_error([
                "message" => __("Contract not found.", "otto-contracts"),
            ]);
        }

        $account = EAC()->accounts->get($account_id);
        if (!$account) {
            wp_send_json_error([
                "message" => __("Account not found.", "otto-contracts"),
            ]);
        }

        
        $amount = eac_convert_currency(
            $invoice->total,
            $invoice->exchange_rate,
            $exchange,
        );

        $payment = EAC()->payments->insert([
            "account_id" => $account_id,
            "category_id" => $category_id,
            "exchange" => $exchange,
            "amount" => $amount,
            "payment_date" => $date,
            "reference" => $reference,
            "note" => $note,
        ]);

        if (is_wp_error($payment)) {
            wp_send_json_error(["message" => $payment->get_error_message()]);
        }

        $invoice->transaction_id = $payment->id;
        $invoice->status = "paid";
        $invoice->payment_date = $date;
        $ret = $invoice->save();
        if (is_wp_error($ret)) {
            wp_send_json_error(["message" => $ret->get_error_message()]);
        }

        wp_send_json_success([
            "message" => __(
                "Payment added successfully.",
                "otto-contracts",
            ),
        ]);
    }

    
    public function get_bill_address()
    {
        check_ajax_referer("eac_edit_bill");

        if (!current_user_can("read_accounting")) {
            
            wp_die(-1);
        }

        $vendor_id = isset($_POST["contact_id"])
            ? absint(wp_unslash($_POST["contact_id"]))
            : 0;
        $vendor = EAC()->vendors->get($vendor_id);
        if (!$vendor) {
            wp_die(-1);
        }
        $bill = new Bill();
        $bill->contact_id = $vendor_id;
        $bill->contact_company = $vendor->company;
        $bill->contact_name = $vendor->name;
        $bill->contact_email = $vendor->email;
        $bill->contact_phone = $vendor->phone;
        $bill->contact_address = $vendor->address;
        $bill->contact_city = $vendor->city;
        $bill->contact_state = $vendor->state;
        $bill->contact_postcode = $vendor->postcode;
        $bill->contact_country = $vendor->country;
        $bill->contact_tax_number = $vendor->tax_number;

        ob_start();
        include __DIR__ . "/views/bill-address.php";
        $html = ob_get_clean();

        $x = new \WP_Ajax_Response();
        $x->add([
            "what" => "billings_html",
            "data" => $html,
        ]);

        $x->send();

        wp_die(1);
    }

    
    public function get_recalculated_bill()
    {
        check_ajax_referer("eac_edit_bill");

        if (!current_user_can("eac_edit_bills")) {
            
            wp_die(-1);
        }

        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $items = isset($_POST["items"])
            ? map_deep(wp_unslash($_POST["items"]), "sanitize_text_field")
            : [];
        $bill = Bill::make($_POST);
        $bill->items = [];
        $bill->set_items($items);
        $bill->calculate_totals();

        $columns = EAC()->bills->get_columns();
        
        if (!$bill->is_taxed()) {
            unset($columns["tax"]);
        }

        ob_start();
        include __DIR__ . "/views/bill-items.php";
        $items_html = ob_get_clean();

        ob_start();
        include __DIR__ . "/views/bill-totals.php";
        $totals_html = ob_get_clean();

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

    
    public function get_invoice_address()
    {
        check_ajax_referer("eac_edit_invoice");

        if (!current_user_can("read_accounting")) {
            
            wp_die(-1);
        }

        $customer_id = isset($_POST["contact_id"])
            ? absint(wp_unslash($_POST["contact_id"]))
            : 0;
        $customer = EAC()->customers->get($customer_id);
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

        ob_start();
        include __DIR__ . "/views/invoice-address.php";
        $html = ob_get_clean();

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
        check_ajax_referer("eac_edit_invoice");

        if (!current_user_can("eac_edit_invoices")) {
            
            wp_die(-1);
        }

        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $items = isset($_POST["items"])
            ? map_deep(wp_unslash($_POST["items"]), "sanitize_text_field")
            : [];
        $invoice = Invoice::make($_POST);
        $invoice->items = [];
        $invoice->set_items($items);
        $invoice->calculate_totals();
        $columns = EAC()->invoices->get_columns();
        
        if (!$invoice->is_taxed()) {
            unset($columns["tax"]);
        }

        ob_start();
        include __DIR__ . "/views/invoice-items.php";
        $items_html = ob_get_clean();

        ob_start();
        include __DIR__ . "/views/invoice-totals.php";
        $totals_html = ob_get_clean();

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

    
    public function ajax_export()
    {
        check_ajax_referer("eac_ajax_export");
        $type = isset($_POST["type"])
            ? sanitize_text_field(wp_unslash($_POST["type"]))
            : "";
        $step = isset($_POST["step"]) ? absint(wp_unslash($_POST["step"])) : 1;
        $posted_filename = isset($_POST["filename"])
            ? sanitize_file_name(wp_unslash($_POST["filename"]))
            : "";
        $exporter = Exporters::get_exporter($type);

        
        if (
            !$exporter ||
            !is_subclass_of($exporter, Exporters\Exporter::class)
        ) {
            wp_send_json_error([
                "message" => esc_html__(
                    "Invalid export type.",
                    "otto-contracts",
                ),
            ]);
        }

        $exporter = new $exporter();
        if (!$exporter->can_export()) {
            wp_send_json_error([
                "message" => esc_html__(
                    "You do not have enough privileges to export this.",
                    "otto-contracts",
                ),
            ]);
        }

        if (
            !empty($posted_filename) &&
            0 === strpos($posted_filename, $type . "-") &&
            substr($posted_filename, -4) === ".csv"
        ) {
            $exporter->set_filename($posted_filename);
        }

        $exporter->process_step($step);

        if (100 <= $exporter->get_percent_complete()) {
            $total = $exporter->get_total_exported();
            $query_args = apply_filters("eac_export_ajax_query_args", [
                "_wpnonce" => wp_create_nonce("eac_download_file"),
                "action" => "eac_download_export",
                "filename" => $exporter->get_filename(),
                "type" => $type,
            ]);
            wp_send_json_success([
                "step" => "done",
                "percentage" => 100,
                "message" => sprintf(
                    
                    esc_html__("Total %d items exported", "otto-contracts"),
                    $total,
                ),
                "url" => add_query_arg(
                    $query_args,
                    admin_url("admin-post.php"),
                ),
                "filename" => $exporter->get_filename(),
            ]);
        } else {
            wp_send_json_success([
                "step" => ++$step,
                "percentage" => $exporter->get_percent_complete(),
                "filename" => $exporter->get_filename(),
            ]);
        }
    }

    
    public function ajax_upload_import_file()
    {
        check_ajax_referer("eac_ajax_import");

        if (!current_user_can("manage_accounting")) {
            
            wp_send_json_error([
                "message" => esc_html__(
                    "You do not have permission to import.",
                    "otto-contracts",
                ),
            ]);
        }

        if (empty($_FILES["upload"])) {
            wp_send_json_error([
                "message" => esc_html__(
                    "Missing import file. Please provide an import file.",
                    "otto-contracts",
                ),
            ]);
        }

        $tmp_name = isset($_FILES["upload"]["tmp_name"])
            ? sanitize_text_field(wp_unslash($_FILES["upload"]["tmp_name"]))
            : "";
        $file_type = isset($_FILES["upload"]["type"])
            ? sanitize_text_field(wp_unslash($_FILES["upload"]["type"]))
            : "";
        if (empty($tmp_name)) {
            wp_send_json_error([
                "message" => esc_html__(
                    "Something went wrong during the upload process, please try again.",
                    "otto-contracts",
                ),
            ]);
        }

        if (
            empty($file_type) ||
            !in_array(
                strtolower($file_type),
                [
                    "text/csv",
                    "text/comma-separated-values",
                    "text/plain",
                    "text/anytext",
                    "text/*",
                    "text/plain",
                    "text/anytext",
                    "text/*",
                    "application/csv",
                    "application/excel",
                    "application/vnd.ms-excel",
                    "application/vnd.msexcel",
                ],
                true,
            )
        ) {
            wp_send_json_error([
                "message" => __(
                    "The file you uploaded does not appear to be a CSV file.",
                    "otto-contracts",
                ),
            ]);
        }

        $import_file = wp_handle_upload($_FILES["upload"], [
            "test_form" => false,
        ]);
        if (!empty($import_file["error"]) || empty($import_file["file"])) {
            wp_send_json_error([
                "message" => __(
                    "Something went wrong during the upload process, please try again.",
                    "otto-contracts",
                ),
                "error" => $import_file,
            ]);
        }

        wp_send_json_success(["file" => $import_file["file"]]);
    }

    
    public function ajax_import()
    {
        check_ajax_referer("eac_ajax_import");
        $type = isset($_POST["type"])
            ? sanitize_text_field(wp_unslash($_POST["type"]))
            : "";
        $file = isset($_POST["file"])
            ? sanitize_text_field(wp_unslash($_POST["file"]))
            : "";
        $position = isset($_POST["position"])
            ? absint(wp_unslash($_POST["position"]))
            : 0;

        if (empty($file) || !FileSystemUtil::file_exists($file)) {
            wp_send_json_error([
                "message" => __(
                    "Missing import file. Please provide an import file.",
                    "otto-contracts",
                ),
            ]);
        }

        $importer = Importers::get_importer($type);
        if (
            !$importer ||
            !class_exists($importer) ||
            !is_subclass_of($importer, Importers\Importer::class)
        ) {
            wp_send_json_error([
                "message" => __("Invalid import type.", "otto-contracts"),
            ]);
        }

        
        if (!FileSystemUtil::file_exists($file)) {
            wp_send_json_error([
                "message" => __(
                    "The file does not exist.",
                    "otto-contracts",
                ),
            ]);
        }

        $importer = new $importer($file, $position);
        if (!$importer->can_import()) {
            wp_send_json_error([
                "message" => __(
                    "You do not have permission to import.",
                    "otto-contracts",
                ),
            ]);
        }

        $imported = $importer->import();
        $position = $importer->get_position();
        $percent_complete = $importer->get_percent_complete();

        if (100 <= $percent_complete) {
            ReportsUtil::flush_report_caches();
            delete_user_option(
                get_current_user_id(),
                "{$type}_import_log_imported",
            );
            wp_send_json_success([
                "position" => "done",
                
                "message" => sprintf(
                    esc_html__("%d items imported.", "otto-contracts"),
                    absint($imported),
                ),
            ]);

            return;
        }

        wp_send_json_success([
            "position" => $position,
            "percentage" => $percent_complete,
        ]);

        exit();
    }
}
