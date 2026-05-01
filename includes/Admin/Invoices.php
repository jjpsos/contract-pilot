<?php

namespace Otto\Admin;

use Otto\Models\Invoice;

defined("ABSPATH") || exit();


class Invoices
{
    
    public function __construct()
    {
        add_filter("eac_sales_page_tabs", [__CLASS__, "register_tabs"]);
        add_filter("eac_sales_page_tabs", [__CLASS__, "order_sales_tabs"], 999);
        add_action("admin_post_eac_edit_invoice", [__CLASS__, "handle_edit"]);
        add_action("admin_post_eac_invoice_mark_sent", [
            __CLASS__,
            "handle_mark_sent",
        ]);
        add_action("admin_post_eac_invoice_mark_accept", [
            __CLASS__,
            "handle_mark_accept",
        ]);
        add_action("eac_sales_page_invoices_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("eac_sales_page_invoices_content", [
            __CLASS__,
            "page_content",
        ]);
        add_action("eac_invoice_view_sidebar_content", [
            __CLASS__,
            "invoice_notes",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (current_user_can("eac_read_invoices")) {
            
            $tabs["invoices"] = __("Contracts/Bills", "otto-contracts");
        }

        return $tabs;
    }

    /**
     * Force Contracts page tabs order: Clients, Contracts/Bills, Payments.
     *
     * @param array $tabs Existing tabs.
     * @return array
     */
    public static function order_sales_tabs($tabs)
    {
        if (!is_array($tabs) || empty($tabs)) {
            return $tabs;
        }

        $ordered = [];
        $preferred_order = ["customers", "invoices", "payments"];

        foreach ($preferred_order as $tab_id) {
            if (isset($tabs[$tab_id])) {
                $ordered[$tab_id] = $tabs[$tab_id];
                unset($tabs[$tab_id]);
            }
        }

        foreach ($tabs as $tab_id => $label) {
            $ordered[$tab_id] = $label;
        }

        return $ordered;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_invoice");

        if (!current_user_can("eac_edit_invoices")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit contracts.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $items = isset($_POST["items"])
            ? map_deep(wp_unslash($_POST["items"]), "sanitize_text_field")
            : [];
        $invoice = Invoice::make($id);
        $invoice->issue_date = isset($_POST["issue_date"])
            ? get_gmt_from_date(
                sanitize_text_field(wp_unslash($_POST["issue_date"])),
            )
            : "";
        $invoice->due_date = isset($_POST["due_date"])
            ? get_gmt_from_date(
                sanitize_text_field(wp_unslash($_POST["due_date"])),
            )
            : "";
        $invoice->contact_id = isset($_POST["contact_id"])
            ? absint(wp_unslash($_POST["contact_id"]))
            : 0;
        $invoice->contact_name = isset($_POST["contact_name"])
            ? sanitize_text_field(wp_unslash($_POST["contact_name"]))
            : "";
        $invoice->contact_company = isset($_POST["contact_company"])
            ? sanitize_text_field(wp_unslash($_POST["contact_company"]))
            : "";
        $invoice->contact_email = isset($_POST["contact_email"])
            ? sanitize_text_field(wp_unslash($_POST["contact_email"]))
            : "";
        $invoice->contact_phone = isset($_POST["contact_phone"])
            ? sanitize_text_field(wp_unslash($_POST["contact_phone"]))
            : "";
        $invoice->contact_address = isset($_POST["contact_address"])
            ? sanitize_text_field(wp_unslash($_POST["contact_address"]))
            : "";
        $invoice->contact_city = isset($_POST["contact_city"])
            ? sanitize_text_field(wp_unslash($_POST["contact_city"]))
            : "";
        $invoice->contact_state = isset($_POST["contact_state"])
            ? sanitize_text_field(wp_unslash($_POST["contact_state"]))
            : "";
        $invoice->contact_postcode = isset($_POST["contact_postcode"])
            ? sanitize_text_field(wp_unslash($_POST["contact_postcode"]))
            : "";
        $invoice->contact_country = isset($_POST["contact_country"])
            ? sanitize_text_field(wp_unslash($_POST["contact_country"]))
            : "";
        $invoice->contact_tax_number = isset($_POST["contact_tax_number"])
            ? sanitize_text_field(wp_unslash($_POST["contact_tax_number"]))
            : "";
        $invoice->order_number = isset($_POST["order_number"])
            ? sanitize_text_field(wp_unslash($_POST["order_number"]))
            : "";
        $invoice->attachment_id = isset($_POST["attachment_id"])
            ? absint(wp_unslash($_POST["attachment_id"]))
            : 0;
        $invoice->currency = isset($_POST["currency"])
            ? sanitize_text_field(wp_unslash($_POST["currency"]))
            : eac_base_currency();
        $invoice->exchange_rate = isset($_POST["exchange_rate"])
            ? floatval(wp_unslash($_POST["exchange_rate"]))
            : 1;
        $invoice->discount_type = isset($_POST["discount_type"])
            ? sanitize_text_field(wp_unslash($_POST["discount_type"]))
            : "fixed";
        $invoice->discount_value = isset($_POST["discount_value"])
            ? floatval(wp_unslash($_POST["discount_value"]))
            : 0;
        $invoice->status = isset($_POST["status"])
            ? sanitize_text_field(wp_unslash($_POST["status"]))
            : "draft";
        $invoice->note = isset($_POST["note"])
            ? sanitize_textarea_field(wp_unslash($_POST["note"]))
            : "";
        $invoice->terms = isset($_POST["terms"])
            ? sanitize_textarea_field(wp_unslash($_POST["terms"]))
            : "";
        $invoice->items()->delete();
        $invoice->items = [];
        $invoice->set_items($items);
        $invoice->calculate_totals();
        $retval = $invoice->save();
        if (is_wp_error($retval)) {
            EAC()->flash->error($retval->get_error_message());
        }

        
        foreach ($invoice->items as $item) {
            $item->document_id = $invoice->id;
            $item->save();
            $taxes = $item->taxes;
            foreach ($taxes as $tax) {
                $tax->document_id = $invoice->id;
                $tax->document_item_id = $item->id;
                $tax->save();
            }
        }

        EAC()->flash->success(
            __("Contract saved successfully.", "otto-contracts"),
        );
        $referer = add_query_arg("id", $invoice->id, $referer);
        $referer = add_query_arg("action", "view", $referer);
        $referer = remove_query_arg(["add"], $referer);
        wp_safe_redirect($referer);
        exit();
    }

    
    public static function handle_mark_sent()
    {
        check_admin_referer("eac_invoice_action");
        if (!current_user_can("eac_edit_invoices")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to perform this action.",
                    "otto-contracts",
                ),
            );
        }

        $id = isset($_GET["id"]) ? absint($_GET["id"]) : 0;
        if (!$id) {
            wp_die(esc_html__("Invalid request.", "otto-contracts"));
        }

        $invoice = EAC()->invoices->get($id);
        if (!$invoice) {
            wp_die(
                esc_html__(
                    "You attempted to perform an action on a contract that does not exist.",
                    "otto-contracts",
                ),
            );
        }

        $invoice->status = "sent";
        if ($invoice->save()) {
            EAC()->flash->success(
                __("Contract marked as sent.", "otto-contracts"),
            );
        } else {
            EAC()->flash->error(
                __("Failed to mark contract as sent.", "otto-contracts"),
            );
        }

        $referer = add_query_arg(["action" => "view"], wp_get_referer());
        wp_safe_redirect($referer);
        exit();
    }

    
    public static function handle_mark_accept()
    {
        check_admin_referer("eac_invoice_action");
        if (!current_user_can("eac_edit_invoices")) {
            wp_die(
                esc_html__(
                    "You do not have permission to perform this action.",
                    "otto-contracts",
                ),
            );
        }

        $id = isset($_GET["id"]) ? absint($_GET["id"]) : 0;
        if (!$id) {
            wp_die(esc_html__("Invalid request.", "otto-contracts"));
        }

        $invoice = EAC()->invoices->get($id);
        if (!$invoice) {
            wp_die(
                esc_html__(
                    "You attempted to perform an action on a contract that does not exist.",
                    "otto-contracts",
                ),
            );
        }

        if (!in_array($invoice->status, ["sent", "overdue"], true)) {
            EAC()->flash->error(
                __(
                    "Only contracts that are sent or overdue can be marked as accepted.",
                    "otto-contracts",
                ),
            );
            $referer = add_query_arg(["action" => "view"], wp_get_referer());
            wp_safe_redirect(
                $referer ? $referer : admin_url("admin.php?page=eac-sales&tab=invoices"),
            );
            exit();
        }

        $invoice->status = "accept";
        if ($invoice->save()) {
            EAC()->flash->success(
                __("Contract marked as accepted.", "otto-contracts"),
            );
        } else {
            EAC()->flash->error(
                __("Failed to mark contract as accepted.", "otto-contracts"),
            );
        }

        $referer = add_query_arg(["action" => "view"], wp_get_referer());
        wp_safe_redirect($referer);
        exit();
    }

    
    public static function page_loaded($action)
    {
        global $eac_list_table;
        switch ($action) {
            case "add":
                if (!current_user_can("eac_edit_invoices")) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to add contracts.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            case "view":
            case "edit":
                $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
                if (!EAC()->invoices->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve a contract that does not exist. Perhaps it was deleted?",
                            "otto-contracts",
                        ),
                    );
                }
                if (
                    "edit" === $action &&
                    !EAC()->invoices->get($id)->editable
                ) {
                    wp_die(
                        esc_html__(
                            "You attempted to edit a contract that is not editable.",
                            "otto-contracts",
                        ),
                    );
                }
                if (
                    "edit" === $action &&
                    !current_user_can("eac_edit_invoices")
                ) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to edit contracts.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $eac_list_table = new ListTables\Invoices();
                $eac_list_table->prepare_items();
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of contracts per page:",
                        "otto-contracts",
                    ),
                    "default" => 20,
                    "option" => "eac_invoices_per_page",
                ]);
                break;
        }
    }

    
    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/invoice-edit.php";
                break;
            case "view":
                include __DIR__ . "/views/invoice-view.php";
                break;
            default:
                include __DIR__ . "/views/invoice-list.php";
                break;
        }
    }

    
    public static function invoice_notes($invoice)
    {
        
        if (!$invoice->exists()) {
            return;
        }

        $notes = EAC()->notes->query([
            "parent_id" => $invoice->id,
            "parent_type" => "invoice",
            "orderby" => "date_created",
            "order" => "DESC",
            "limit" => 20,
        ]);
        ?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h3 class="eac-card__title"><?php esc_html_e(
        "Notes",
        "otto-contracts",
    ); ?></h3>
			</div>
			<div class="eac-card__body">

				<?php if (
        current_user_can("eac_edit_notes")
    ):
         ?>
					<div class="eac-form-field">
						<label for="eac-note"><?php esc_html_e(
          "Add Note",
          "otto-contracts",
      ); ?></label>
						<textarea id="eac-note" cols="30" rows="2" placeholder="<?php esc_attr_e(
          "Enter Note",
          "otto-contracts",
      ); ?>"></textarea>
					</div>
					<button id="eac-add-note" type="button" class="button tw-mb-[20px]" data-parent_id="<?php echo esc_attr(
         $invoice->id,
     ); ?>" data-parent_type="invoice" data-nonce="<?php echo esc_attr(
    wp_create_nonce("eac_add_note"),
); ?>">
						<?php esc_html_e("Add Note", "otto-contracts"); ?>
					</button>
				<?php endif; ?>

				<?php include __DIR__ . "/views/note-list.php"; ?>
			</div>
		</div>
		<?php
    }
}
