<?php

    namespace Jjpsos\ContractPilot\Admin\Settings;

    use Jjpsos\ContractPilot\Admin\Request;
    use Jjpsos\ContractPilot\Admin\Settings;

    defined("ABSPATH") || exit();

abstract class Page
{
    public $id      = "";
    public $label   = "";
    public $section = "";
    public function __construct($id, $label)
    {
        $this->id      = $id;
        $this->label   = $label;
        $this->section = Request::get_key('section');
        if (! array_key_exists($this->section, $this->get_sections())) {
            $sections      = $this->get_sections();
            $this->section = key($sections);
        }

        add_action("contract_pilot_settings_save_" . $this->id, [$this, "save_settings"]);
        add_action("contract_pilot_settings_page_" . $this->id . "_content", [
            $this,
            "render_sections",
        ]);
        add_action("contract_pilot_settings_page_" . $this->id . "_content", [
            $this,
            "render_content",
        ]);
    }

    public function save_settings()
    {
        $settings = $this->get_section_settings($this->section);
        if (Settings::save_fields($settings)) {
            contract_pilot()->flash->success(__("Settings saved.", "contract-pilot"));
        }
    }

    public function render_sections()
    {
        $sections = $this->get_sections();

        if (empty($sections) || 1 === count($sections)) {
            return;
        }

        $array_keys = array_keys($sections);
        echo '<ul class="subsubsub settings-sections-nav">';
        foreach ($sections as $id => $label) {
            $url = admin_url(
                "admin.php?page=contract-pilot-settings&tab=" .
                $this->id .
                "&section=" .
                sanitize_title($id),
            );
            $class     = $this->section === $id ? "current" : "";
            $separator = end($array_keys) === $id ? "" : "|";
            $text      = esc_html($label);
            printf(
                '<li><a href="%s" class="%s">%s</a> %s</li>',
                esc_url($url),
                esc_attr($class),
                esc_html($text),
                esc_html($separator),
            );
        }
        echo '</ul><br class="clear" />';
    }

    public function render_content()
    {
        $settings = $this->get_section_settings($this->section);
        $action   = "contract_pilot_settings_" .
            $this->id .
            "_tab" .
            ($this->section ? "_" . $this->section : "") .
            "_content";
        if (has_action($action)) : ?>
            <?php do_action(
                "contract_pilot_settings_" .
                    $this->id .
                    "_tab" .
                    ($this->section ? "_" . $this->section : "") .
                    "_content",
                $this->section,
            ); ?>
            <?php if (has_action("contract_pilot_settings_" . $this->id . "_" . $action)) {
                do_action_deprecated(
                    "contract_pilot_settings_" . $this->id . "_" . $action,
                    [$this->section],
                    "2.0.0",
                    $action,
                );
            } ?>
        <?php elseif (! empty($settings)) : ?>
<form method="post" id="mainform" action="" enctype="multipart/form-data">
                <?php Settings::output_fields($settings); ?>
                <?php wp_nonce_field('contract_pilot_save_settings'); ?>

                <?php if (
                apply_filters(
                    'contract_pilot_settings_save_button_' . $this->id,
                    true,
                    $this->section
                )
) : ?>
        <p class="submit">
            <button
                name="save_settings"
                class="button-primary"
                type="submit"
                value="<?php esc_attr_e('Save changes', 'contract-pilot'); ?>"
            >
                <?php esc_html_e('Save changes', 'contract-pilot'); ?>
            </button>
        </p>
                <?php endif; ?>
</form>
        <?php endif; ?>
    <?php }

    protected function get_own_sections()
    {
        return ["" => __("Options", "contract-pilot")];
    }
    public function get_sections()
    {
        $sections = $this->get_own_sections();
        return (array) apply_filters(
            "contract_pilot_get_settings_sections_" . $this->id,
            $sections,
        );
    }

    public function get_section_settings($section)
    {
        $settings = [];
        if (empty($section)) {
            $method = "get_default_section_settings";
        } else {
            $method = "get_" . $section . "_section_settings";
        }

        if (method_exists($this, $method)) {
            $settings = $this->$method($section);
        }
        return apply_filters(
            "contract_pilot_get_" . $this->id . "_settings",
            $settings,
            $section,
        );
    }
    public function get_default_section_settings()
    {
        return [];
    }
}
