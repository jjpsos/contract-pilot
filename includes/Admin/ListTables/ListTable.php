<?php

namespace Otto\Admin\ListTables;

defined("ABSPATH") || exit();

if (!class_exists("WP_List_Table")) {
    require_once ABSPATH . "wp-admin/includes/class-wp-list-table.php";
}


abstract class ListTable extends \WP_List_Table
{
    
    public $base_url;

    
    public function __construct($args = [])
    {
        parent::__construct($args);
        remove_filter(
            "manage_{$this->screen->id}_columns",
            [$this, "get_columns"],
            0,
        );
    }

    
    protected function get_request_orderby()
    {
        $orderby = isset($_GET["orderby"])
            ? sanitize_text_field(wp_unslash($_GET["orderby"]))
            : ""; 

        return $orderby;
    }

    
    protected function get_request_order()
    {
        if (
            !empty($_GET["order"]) &&
            "desc" ===
                strtolower(sanitize_text_field(wp_unslash($_GET["order"])))
        ) {
            
            $order = "DESC";
        } else {
            $order = "ASC";
        }

        return $order;
    }

    
    protected function get_request_status($fallback = null)
    {
        $status = !empty($_GET["status"])
            ? sanitize_text_field(wp_unslash($_GET["status"]))
            : ""; 

        return empty($status) ? $fallback : $status;
    }

    
    protected function get_request_type($fallback = null)
    {
        $type = !empty($_GET["type"])
            ? sanitize_text_field(wp_unslash($_GET["type"]))
            : ""; 

        return empty($type) ? $fallback : $type;
    }

    
    public function get_request_search()
    {
        return !empty($_GET["s"])
            ? sanitize_text_field(wp_unslash($_GET["s"]))
            : ""; 
    }

    
    protected function process_actions()
    {
        $this->_column_headers = [
            $this->get_columns(),
            get_hidden_columns($this->screen),
            $this->get_sortable_columns(),
        ];

        
        $action = $this->current_action();
        if (
            !empty($action) &&
            array_key_exists($action, $this->get_bulk_actions())
        ) {
            check_admin_referer("bulk-" . $this->_args["plural"]);

            $ids = isset($_GET["id"])
                ? map_deep(wp_unslash($_GET["id"]), "intval")
                : [];
            $ids = wp_parse_id_list($ids);
            $method = "bulk_" . $action;
            if (
                array_key_exists($action, $this->get_bulk_actions()) &&
                method_exists($this, $method) &&
                !empty($ids)
            ) {
                $this->$method($ids);
            }
        }

        if (isset($_GET["_wpnonce"]) && isset($_SERVER["REQUEST_URI"])) {
            wp_safe_redirect(
                remove_query_arg(
                    ["_wp_http_referer", "_wpnonce", "id", "action", "action2"],
                    esc_url_raw(wp_unslash($_SERVER["REQUEST_URI"])),
                ),
            );
            exit();
        }
    }

    
    public function column_metadata($items)
    {
        if (!empty($items)) {
            $items = is_array($items) ? $items : [$items];
            $items = array_filter($items);
            $metadata = sprintf(
                '<div class="column-metadata"><span>%s</span></div>',
                implode("</span><span>", $items),
            );

            return wp_kses_post($metadata);
        }

        return "";
    }

    
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case "status":
                $statuses = [
                    "active" => __("Active", "otto-contracts"),
                    "inactive" => __("Inactive", "otto-contracts"),
                ];
                $status = isset($item->$column_name) ? $item->$column_name : "";
                $label = isset($statuses[$status]) ? $statuses[$status] : "";

                return sprintf(
                    '<span class="eac-status is--%1$s">%2$s</span>',
                    esc_attr($status),
                    esc_html($label),
                );

            default:
                if (is_object($item) && isset($item->$column_name)) {
                    return empty($item->$column_name)
                        ? "&mdash;"
                        : wp_kses_post($item->$column_name);
                }
        }

        return "&mdash;";
    }

    
    protected function category_filter($type)
    {
        $category_id = filter_input(
            INPUT_GET,
            "category_id",
            FILTER_SANITIZE_NUMBER_INT,
        );
        $category = empty($category_id)
            ? null
            : EAC()->categories->get($category_id);
        ?>
		<select class="eac_select2" name="category_id" id="filter-by-category" data-action="eac_json_search" data-type="category" data-subtype="<?php echo esc_attr(
      $type,
  ); ?>" data-placeholder="<?php esc_attr_e("Filter by category", "otto-contracts"); ?>">
			<?php if (!empty($category)): ?>
				<option value="<?php echo esc_attr($category->id); ?>" <?php selected(
    $category_id,
    $category->id,
); ?>>
					<?php echo esc_html($category->name); ?>
				</option>
			<?php endif; ?>
		</select>
		<?php
    }

    
    protected function account_filter()
    {
        $account_id = filter_input(
            INPUT_GET,
            "account_id",
            FILTER_SANITIZE_NUMBER_INT,
        );
        $account = empty($account_id)
            ? null
            : EAC()->accounts->get($account_id);
        ?>
		<select class="eac_select2" name="account_id" id="filter-by-account" data-action="eac_json_search" data-type="account" data-placeholder="<?php esc_attr_e(
      "Filter by account",
      "otto-contracts",
  ); ?>">
			<?php if (!empty($account)): ?>
				<option value="<?php echo esc_attr($account->id); ?>" <?php selected(
    $account_id,
    $account->id,
); ?>>
					<?php echo esc_html($account->name); ?>
				</option>
			<?php endif; ?>
		</select>
		<?php
    }

    
    protected function contact_filter($type)
    {
        if ("customer" === $type) {

            $customer_id = filter_input(
                INPUT_GET,
                "customer_id",
                FILTER_SANITIZE_NUMBER_INT,
            );
            $customer = empty($customer_id)
                ? null
                : EAC()->customers->get($customer_id);
            ?>
			<select class="eac_select2" name="customer_id" id="filter-by-customer" data-action="eac_json_search" data-type="customer" data-placeholder="<?php esc_attr_e(
       "Filter by customer",
       "otto-contracts",
   ); ?>">
				<?php if (!empty($customer)): ?>
					<option value="<?php echo esc_attr($customer->id); ?>" <?php selected(
    $customer_id,
    $customer->id,
); ?>>
						<?php echo esc_html($customer->name); ?>
					</option>
				<?php endif; ?>
			</select>
			<?php
        } else {

            $vendor_id = filter_input(
                INPUT_GET,
                "vendor_id",
                FILTER_SANITIZE_NUMBER_INT,
            );
            $vendor = empty($vendor_id)
                ? null
                : EAC()->vendors->get($vendor_id);
            ?>
			<select class="eac_select2" name="vendor_id" id="filter-by-vendor" data-action="eac_json_search" data-type="vendor" data-placeholder="<?php esc_attr_e(
       "Filter by vendor",
       "otto-contracts",
   ); ?>">
				<?php if (!empty($vendor)): ?>
					<option value="<?php echo esc_attr($vendor->id); ?>" <?php selected(
    $vendor_id,
    $vendor->id,
); ?>>
						<?php echo esc_html($vendor->name); ?>
					</option>
				<?php endif; ?>
			</select>
			<?php
        }
    }

    
    protected function date_filter($months)
    {
        $m = filter_input(INPUT_GET, "m", FILTER_SANITIZE_NUMBER_INT);
        $month_count = count($months);
        if (
            !$month_count ||
            (1 === $month_count && 0 === (int) $months[0]->month)
        ) {
            return;
        }
        ?>
		<select name="m" id="filter-by-date" class="eac_select2" data-placeholder="<?php esc_attr_e(
      "Filter by date",
      "otto-contracts",
  ); ?>">
			<option<?php selected(
       $m,
       0,
   ); ?> style='display: none'><?php esc_attr_e("Filter by date", "otto-contracts"); ?></option>
			<?php foreach ($months as $arc_row) {
       if (0 === (int) $arc_row->year || 0 === (int) $arc_row->month) {
           continue;
       }

       $month = zeroise($arc_row->month, 2);
       $year = $arc_row->year;

       printf(
           "<option %s value='%s'>%s</option>\n",
           selected($m, $year . $month, false),
           esc_attr($arc_row->year . $month),
           esc_html(
               \DateTime::createFromFormat("Y-m", $year . "-" . $month)->format(
                   "M Y",
               ),
           ),
       );
   } ?>
		</select>
		<?php
    }
}
