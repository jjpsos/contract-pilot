<?php

defined('ABSPATH') || exit;

/**
 * Category add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Category $category
 * @var string                                $type_default
 * @var array<string, string>                 $category_types
 */

?><h1 class="wp-heading-inline">
    <?php if ($category->exists()) : ?>
        <?php esc_html_e('Edit Category', 'contract-pilot'); ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-settings&tab=categories&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php else : ?>
        <?php esc_html_e('Add Category', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-edit-category" name="category" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">
        <div class="column-1">

            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Category Attributes', 'contract-pilot'); ?></h2>
                </div>

                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'id'          => 'name',
                            'label'       => __('Name', 'contract-pilot'),
                            'placeholder' => __('Enter category name', 'contract-pilot'),
                            'value'       => $category->name,
                            'required'    => true,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'type',
                            'type'        => 'select',
                            'label'       => __('Type', 'contract-pilot'),
                            'placeholder' => __('Select category type', 'contract-pilot'),
                            'value'       => $category->type,
                            'default'     => $type_default,
                            'options'     => $category_types,
                            'required'    => true,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'            => 'description',
                            'label'         => __('Description', 'contract-pilot'),
                            'placeholder'   => __('Enter category description', 'contract-pilot'),
                            'value'         => $category->description,
                            'type'          => 'textarea',
                            'wrapper_class' => 'is--full',
                        )
                    );
                    ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_category_edit_core_content', $category);
            ?>
        </div><!-- .column-1 -->

        <div class="column-2">
            <div id="contract-pilot-category-actions" class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__footer">
                    <?php if ($category->exists()) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $category->get_edit_url()), 'bulk-categories')); ?>"><?php esc_html_e('Delete', 'contract-pilot'); ?></a>
                        <button class="button button-primary"><?php esc_html_e('Update Category', 'contract-pilot'); ?></button>
                    <?php else : ?>
                        <button class="button button-primary button-block"><?php esc_html_e('Add Category', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_category_edit_sidebar_content', $category);
            ?>

        </div><!-- .column-2 -->

    </div><!-- .contract-pilot-poststuff -->
    <?php wp_nonce_field('contract_pilot_edit_category'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_category"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($category->id); ?>"/>
</form>
