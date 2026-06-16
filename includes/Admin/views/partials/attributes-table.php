<?php

defined('ABSPATH') || exit;

/**
 * Shared label/value attributes table.
 *
 * @var array<int, array{label: string, value: mixed}> $contract_pilot_attributes
 */

if (empty($contract_pilot_attributes) || ! is_array($contract_pilot_attributes)) {
    return;
}

?>
<table class="contract-pilot-table is--striped is--bordered">
    <tbody>
    <?php foreach ($contract_pilot_attributes as $contract_pilot_attribute) { ?>
        <tr>
            <th scope="row"><?php echo esc_html($contract_pilot_attribute['label']); ?></th>
            <td>
                <?php
                if (
                    ! isset($contract_pilot_attribute['value'])
                    || '' === $contract_pilot_attribute['value']
                    || '&mdash;' === $contract_pilot_attribute['value']
                ) {
                    echo '&mdash;';
                } else {
                    echo esc_html($contract_pilot_attribute['value']);
                }
                ?>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
