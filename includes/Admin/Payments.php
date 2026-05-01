<?php

namespace Otto\Admin;

use Otto\Models\Payment;

defined("ABSPATH") || exit();


class Payments
{
    
    public function __construct()
    {
        add_filter("eac_sales_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("admin_post_eac_edit_payment", [__CLASS__, "handle_edit"]);
        add_action("admin_post_eac_update_payment", [
            __CLASS__,
            "handle_update",
        ]);
        add_action("eac_sales_page_payments_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("eac_sales_page_payments_content", [
            __CLASS__,
            "page_content",
        ]);
        add_action("eac_payment_view_sidebar_content", [
            __CLASS__,
            "payment_attachment",
        ]);
        add_action("eac_payment_view_sidebar_content", [
            __CLASS__,
            "payment_notes",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (current_user_can("eac_read_payments")) {
            
            $tabs["payments"] = __("Payments", "otto-contracts");
        }

        return $tabs;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_payment");

        if (!current_user_can("eac_edit_payments")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit payments.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $data = [
            "id" => isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0,
            "payment_date" => isset($_POST["payment_date"])
                ? get_gmt_from_date(
                    sanitize_text_field(wp_unslash($_POST["payment_date"])),
                )
                : "",
            "account_id" => isset($_POST["account_id"])
                ? absint(wp_unslash($_POST["account_id"]))
                : 0,
            "amount" => isset($_POST["amount"])
                ? floatval(wp_unslash($_POST["amount"]))
                : 0,
            "exchange_rate" => isset($_POST["exchange_rate"])
                ? floatval(wp_unslash($_POST["exchange_rate"]))
                : 1,
            "category_id" => isset($_POST["category_id"])
                ? absint(wp_unslash($_POST["category_id"]))
                : 0,
            "contact_id" => isset($_POST["contact_id"])
                ? absint(wp_unslash($_POST["contact_id"]))
                : 0,
            "attachment_id" => isset($_POST["attachment_id"])
                ? absint(wp_unslash($_POST["attachment_id"]))
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
            "status" => isset($_POST["status"])
                ? sanitize_text_field(wp_unslash($_POST["status"]))
                : "active",
        ];
        $payment = EAC()->payments->insert($data);
        if (is_wp_error($payment)) {
            EAC()->flash->error($payment->get_error_message());
        } else {
            EAC()->flash->success(
                __("Payment saved successfully.", "otto-contracts"),
            );
            $referer = add_query_arg("id", $payment->id, $referer);
            $referer = add_query_arg("action", "view", $referer);
            $referer = remove_query_arg(["add"], $referer);
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function handle_update()
    {
        check_admin_referer("eac_update_payment");

        if (!current_user_can("eac_edit_payments")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to update payments.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $status = isset($_POST["status"])
            ? sanitize_text_field(wp_unslash($_POST["status"]))
            : "";
        $attachment_id = isset($_POST["attachment_id"])
            ? absint(wp_unslash($_POST["attachment_id"]))
            : 0;
        $payment_action = isset($_POST["payment_action"])
            ? sanitize_text_field(wp_unslash($_POST["payment_action"]))
            : "";
        $payment = EAC()->payments->get($id);

        
        if (!$payment) {
            EAC()->flash->error(__("Payment not found.", "otto-contracts"));

            return;
        }

        
        if (!empty($status) && $status !== $payment->status) {
            $payment->status = $status;
        }

        
        if ($attachment_id !== $payment->attachment_id) {
            $payment->attachment_id = $attachment_id;
        }

        if ($payment->is_dirty() && $payment->save()) {
            $ret = $payment->save();
            if (is_wp_error($ret)) {
                EAC()->flash->error($ret->get_error_message());
            } else {
                EAC()->flash->success(
                    __("Payment updated successfully.", "otto-contracts"),
                );
            }
        }

        
        if (!empty($payment_action)) {
            switch ($payment_action) {
                case "send_receipt":
                    
                    break;
                default:
                    
                    do_action(
                        "eac_payment_action_" . $payment_action,
                        $payment,
                    );
                    break;
            }
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function page_loaded($action)
    {
        global $eac_list_table;
        switch ($action) {
            case "add":
                if (!current_user_can("eac_edit_payments")) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to add payments.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            case "view":
            case "edit":
                $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
                if (!EAC()->payments->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve a payment that does not exist. Perhaps it was deleted?",
                            "otto-contracts",
                        ),
                    );
                }
                if (
                    "edit" === $action &&
                    !EAC()->payments->get($id)->editable
                ) {
                    wp_die(
                        esc_html__(
                            "You attempted to edit a payment that is not editable.",
                            "otto-contracts",
                        ),
                    );
                }
                if (
                    "edit" === $action &&
                    !current_user_can("eac_edit_payments")
                ) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to edit payments.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $eac_list_table = new ListTables\Payments();
                $eac_list_table->prepare_items();
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of items per page:",
                        "otto-contracts",
                    ),
                    "default" => 20,
                    "option" => "eac_payments_per_page",
                ]);
                break;
        }
    }

    
    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/payment-edit.php";
                break;
            case "view":
                include __DIR__ . "/views/payment-view.php";
                break;
            default:
                include __DIR__ . "/views/payment-list.php";
                break;
        }
    }

    
    public static function payment_attachment($payment)
    {
        ?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h3 class="eac-card__title"><?php esc_html_e(
        "Attachment",
        "otto-contracts",
    ); ?></h3>
			</div>
			<div class="eac-card__body">
				<?php eac_file_uploader([
        "value" => $payment->attachment_id,
        "readonly" => true,
    ]); ?>
			</div>
		</div>
		<?php
    }

    
    public static function payment_notes($payment)
    {
        
        if (!$payment->exists()) {
            return;
        }
        $notes = EAC()->notes->query([
            "parent_id" => $payment->id,
            "parent_type" => "payment",
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
         $payment->id,
     ); ?>" data-parent_type="payment" data-nonce="<?php echo esc_attr(
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
