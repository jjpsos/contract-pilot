<?php

namespace Otto\Admin;

use Otto\Models\Item;

defined("ABSPATH") || exit();


class Items
{
    
    public function __construct()
    {
        add_filter("eac_items_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("admin_post_eac_edit_item", [__CLASS__, "handle_edit"]);
        add_action("eac_items_page_items_loaded", [__CLASS__, "page_loaded"]);
        add_action("eac_items_page_items_content", [__CLASS__, "page_content"]);
        add_action("eac_item_edit_sidebar_content", [__CLASS__, "item_notes"]);
    }

    
    public static function register_tabs($tabs)
    {
        if (current_user_can("eac_read_items")) {
            
            $tabs["items"] = __("Services", "otto-contracts");
        }

        return $tabs;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_item");
        if (!current_user_can("eac_edit_items")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit services.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $data = [
            "id" => isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0,
            "type" => isset($_POST["type"])
                ? sanitize_text_field(wp_unslash($_POST["type"]))
                : "",
            "name" => isset($_POST["name"])
                ? sanitize_text_field(wp_unslash($_POST["name"]))
                : "",
            "description" => isset($_POST["description"])
                ? sanitize_text_field(wp_unslash($_POST["description"]))
                : "",
            "unit" => isset($_POST["unit"])
                ? sanitize_text_field(wp_unslash($_POST["unit"]))
                : "",
            "price" => isset($_POST["price"])
                ? floatval(wp_unslash($_POST["price"]))
                : 0,
            "cost" => isset($_POST["cost"])
                ? floatval(wp_unslash($_POST["cost"]))
                : 0,
            "tax_ids" => isset($_POST["tax_ids"])
                ? array_map("absint", wp_unslash($_POST["tax_ids"]))
                : [],
            "category_id" => isset($_POST["category_id"])
                ? absint(wp_unslash($_POST["category_id"]))
                : 0,
        ];

        $item = EAC()->items->insert($data);
        if (is_wp_error($item)) {
            EAC()->flash->error($item->get_error_message());
        } else {
            EAC()->flash->success(
                __("Service saved successfully.", "otto-contracts"),
            );
            $referer = add_query_arg("id", $item->id, $referer);
            $referer = remove_query_arg(["add"], $referer);
        }
        wp_safe_redirect($referer);
        exit();
    }

    
    public static function page_loaded($action)
    {
        global $eac_list_table;
        switch ($action) {
            case "add":
                if (!current_user_can("eac_edit_items")) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to add services.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            case "edit":
                $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);
                if (!EAC()->items->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve a service that does not exist. Perhaps it was deleted?",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $eac_list_table = new ListTables\Items();
                $eac_list_table->prepare_items();
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of services per page:",
                        "otto-contracts",
                    ),
                    "default" => 20,
                    "option" => "eac_items_per_page",
                ]);
                break;
        }
    }

    
    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/item-edit.php";
                break;
            default:
                include __DIR__ . "/views/item-list.php";
                break;
        }
    }

    
    public static function item_notes($item)
    {
        
        if (!$item->exists()) {
            return;
        }

        $notes = EAC()->notes->query([
            "parent_id" => $item->id,
            "parent_type" => "item",
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
        current_user_can("eac_edit_items")
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
         $item->id,
     ); ?>" data-parent_type="item" data-nonce="<?php echo esc_attr(
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
