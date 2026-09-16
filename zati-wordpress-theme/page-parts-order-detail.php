<?php
/*
Template Name: Parts Order Detail
Template Post Type: page
*/

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

$order_id = isset($_GET['order_id'])
    ? absint($_GET['order_id'])
    : 0;

if (
    !$order_id ||
    !zati_current_user_can_view_parts_order($order_id)
) {
    wp_safe_redirect(
        home_url('/parts-order-archive/')
    );
    exit;
}

/**
 * Save TS Processing
 */
if (
    isset($_POST['zati_save_ts_processing']) &&
    isset($_POST['zati_ts_processing_nonce'])
) {

    $current_user = wp_get_current_user();

    $allowed_roles = [
        'administrator',
        'zac_ts',
    ];

    $can_process_order = ! empty(
        array_intersect(
            $allowed_roles,
            (array) $current_user->roles
        )
    );

    if (
        $can_process_order &&
        wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['zati_ts_processing_nonce'])
            ),
            'zati_save_ts_processing_' . $order_id
        )
    ) {

        $existing_ts_parts = get_post_meta(
            $order_id,
            'ts_parts',
            true
        );

        $posted_ts_parts = isset($_POST['ts_parts'])
            && is_array($_POST['ts_parts'])
                ? wp_unslash($_POST['ts_parts'])
                : [];

                $order_parts = get_post_meta(
                $order_id,
                'parts',
                true
            );

            if (!is_array($order_parts)) {
                $order_parts = [];
            }

        if ( is_array($existing_ts_parts) ) {

    $ts_processing_errors = [];
    $updated_ts_parts = $existing_ts_parts;

    foreach (
        $existing_ts_parts as $index => $existing_part
    ) {

        if (
            ! isset($posted_ts_parts[$index]) ||
            ! is_array($posted_ts_parts[$index])
        ) {
            continue;
        }

        $posted_part =
            $posted_ts_parts[$index];

        $ordered_qty =
            absint(
                $existing_part['ordered_qty']
                ?? 0
            );

        $confirmed_qty =
            isset(
                $posted_part['confirmed_qty']
            )
                ? absint(
                    $posted_part['confirmed_qty']
                )
                : 0;

        $backorder_qty =
            isset(
                $posted_part['backorder_qty']
            )
                ? absint(
                    $posted_part['backorder_qty']
                )
                : 0;

        if (
            $confirmed_qty
            + $backorder_qty
            > $ordered_qty
        ) {

            $error_part_no =
                sanitize_text_field(
                    $existing_part['part_no']
                    ?? (
                        $order_parts[$index]['partnumber']
                        ?? ''
                    )
                );

            $ts_processing_errors[] =
                sprintf(
                    'Part %s: Confirmed + Backorder cannot exceed Ordered Qty.',
                    $error_part_no
                );

            continue;
        }

        $updated_ts_parts[$index]['confirmed_qty'] =
            $confirmed_qty;

        $updated_ts_parts[$index]['backorder_qty'] =
            $backorder_qty;

        $updated_ts_parts[$index]['ts_note'] =
            isset($posted_part['ts_note'])
                ? sanitize_textarea_field(
                    $posted_part['ts_note']
                )
                : '';
    }


    if (empty($ts_processing_errors)) {

    update_post_meta(
        $order_id,
        'ts_parts',
        $updated_ts_parts
    );

    $order_status = 'TS Completed';

    update_post_meta(
        $order_id,
        'order_status',
        $order_status
    );

    if (
        ! get_post_meta(
            $order_id,
            'ts_completed_at',
            true
        )
    ) {
        update_post_meta(
            $order_id,
            'ts_completed_at',
            current_time('mysql')
        );
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'order_id' => $order_id,
                'ts_saved' => '1',
            ],
            home_url('/parts-order-detail/')
        )
    );

    exit;
}
}


            
    }
}

        get_header('blank');
        get_template_part('app-header');

        $reference_no = sanitize_text_field(
            get_post_meta($order_id, 'reference_no', true)
        );

        $order_status = sanitize_text_field(
            get_post_meta($order_id, 'order_status', true)
        );

        if ($order_status === '') {
            $order_status = 'Submitted';
        }

        $ts_completed_at = get_post_meta(
            $order_id,
            'ts_completed_at',
            true
        );

        $service_center_name = sanitize_text_field(
            get_post_meta($order_id, 'service_center_name', true)
        );

        $account_no = sanitize_text_field(
            get_post_meta($order_id, 'account_no', true)
        );

        $phone = sanitize_text_field(
            get_post_meta($order_id, 'phone', true)
        );

        $customer_po_no = sanitize_text_field(
            get_post_meta($order_id, 'customer_po_no', true)
        );

        $order_date = sanitize_text_field(
            get_post_meta($order_id, 'order_date', true)
        );

        $ship_via = sanitize_text_field(
            get_post_meta($order_id, 'requested_ship_via', true)
        );

        $ship_date = sanitize_text_field(
            get_post_meta($order_id, 'requested_ship_date', true)
        );

        $terms = sanitize_text_field(
            get_post_meta($order_id, 'terms', true)
        );

        $remarks = sanitize_text_field(
            get_post_meta($order_id, 'remarks', true)
        );

        $sold_to = get_post_meta(
            $order_id,
            'sold_to',
            true
        );

        $ship_to = get_post_meta(
            $order_id,
            'ship_to',
            true
        );

        $parts = get_post_meta(
            $order_id,
            'parts',
            true
        );

        $parts_total = (float) get_post_meta(
            $order_id,
            'parts_total',
            true
        );
        ?>

    <div class="zati-subnav">

        <a
            class="zati-subnav-item"
            href="<?php echo esc_url(
                home_url('/svc-form/')
            ); ?>"
        >
            Form
        </a>

        <a
            class="zati-subnav-item"
            href="<?php echo esc_url(
                home_url('/parts-order/')
            ); ?>"
        >
            Parts Order
        </a>


        <span class="zati-subnav-item active">
            Parts Order Detail
        </span>

    </div>

<main class="zati-archive-page">

    <section class="zati-archive-wrap">

        <div class="zati-form-header">

            <h1 class="zati-form-title">
                Parts Order Detail
            </h1>

            <div class="zati-detail-reference">

                <strong>
                    <?php echo esc_html(
                        $reference_no
                    ); ?>
                </strong>

                <?php
                $order_status_label =
                    $order_status === 'Submitted'
                        ? 'Submitted — Waiting for TS'
                        : $order_status;
                ?>

                <span
                    class="
                        zati-order-status
                        zati-order-status-badge
                    "
                >
                    <?php
                    echo esc_html(
                        $order_status_label
                    );
                    ?>
                </span>

                <?php if (
                    $order_status === 'TS Completed' &&
                    $ts_completed_at
                ) : ?>

                    <span class="zati-ts-completed-date">
                        Completed Date:
                        <?php
                        echo esc_html(
                            wp_date(
                                'M j, Y g:i A',
                                strtotime($ts_completed_at)
                            )
                        );
                        ?>
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <section class="zati-form-section zati-order-summary-section">

            <h2 class="zati-section-title">
                Order Information
            </h2>

            <div class="zati-order-info-table-wrap">

                <table class="zati-order-info-table">

                    <thead>
                        <tr>
                            <th>Service Center</th>
                            <th>Account No.</th>
                            <th>Phone No.</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                <?php echo esc_html(
                                    $service_center_name ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $account_no ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $phone ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $order_date ?: '—'
                                ); ?>
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>


            <h2 class="zati-section-title zati-shipping-title">
                Shipping Request
            </h2>

            <div class="zati-order-info-table-wrap">

                <table class="zati-order-info-table zati-shipping-info-table">

                    <thead>
                        <tr>
                            <th>Customer PO No.</th>
                            <th>Ship Via</th>
                            <th>Ship Date</th>
                            <th>Terms</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>
                                <?php echo esc_html(
                                    $customer_po_no ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $ship_via ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $ship_date ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $terms ?: '—'
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $remarks ?: '—'
                                ); ?>
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>
   
        
         <section class="zati-form-section">

            <div class="zati-review-main-grid">

                <div>

                    <h2 class="zati-section-title">
                        Sold To
                    </h2>

                    <?php
                    if (is_array($sold_to)) :
                    ?>

                        <div class="zati-address-card">

                            <?php foreach ($sold_to as $value) : ?>

                                <?php if ($value !== '') : ?>

                                    <div>
                                        <?php echo esc_html(
                                            $value
                                        ); ?>
                                    </div>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

                <div>

                    <h2 class="zati-section-title">
                        Ship To
                    </h2>

                    <?php
                    if (is_array($ship_to)) :
                    ?>

                        <div class="zati-address-card">

                            <?php foreach ($ship_to as $value) : ?>

                                <?php if ($value !== '') : ?>

                                    <div>
                                        <?php echo esc_html(
                                            $value
                                        ); ?>
                                    </div>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>
    

<section class="zati-form-section">

            <h2 class="zati-section-title">
                Parts Ordered
            </h2>

            <div class="zati-archive-table-wrap">

                <table class="zati-archive-table">

                    <thead>
                        <tr>
                            <th>Part No.</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Extended Price</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (is_array($parts)) : ?>

                            <?php foreach ($parts as $part) : ?>

                                <?php
                                $partnumber = sanitize_text_field(
                                    $part['partnumber'] ?? ''
                                );

                                $description = sanitize_text_field(
                                    $part['description'] ?? ''
                                );

                                $quantity = absint(
                                    $part['quantity'] ?? 0
                                );

                                $unit_price = $part['unit_price'] ?? '';

                                $extended_price =
                                    $part['extended_price'] ?? '';
                                ?>

                                <tr>

                                    <td>
                                        <?php echo esc_html(
                                            $partnumber
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            $description
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            $quantity
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            $unit_price
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            $extended_price
                                        ); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="4">
                                Parts Total
                            </th>
                            <th>
                                $<?php echo esc_html(
                                    number_format(
                                        $parts_total,
                                        2
                                    )
                                ); ?>
                            </th>
                        </tr>
                    </tfoot>

                </table>

            </div>

        </section>

        
   
        

<?php
/**
 * TS Processing
 * Display only for TS / Admin users.
 * Save function will be added in the next step.
 */

$current_user = wp_get_current_user();

$allowed_roles = [
    'administrator',
    'zac_ts',
];

$can_process_order = ! empty(
    array_intersect(
        $allowed_roles,
        (array) $current_user->roles
    )
);

if ( $can_process_order ) :
    $ts_parts = zati_initialize_ts_parts( $order_id );
/*
 * Keep posted TS values on validation error
 * without saving them to the database.
 */
$ts_form_parts = $ts_parts;

if (
    ! empty($ts_processing_error)
    && isset($posted_ts_parts)
    && is_array($posted_ts_parts)
) {

    foreach (
        $ts_form_parts as $index => $ts_part
    ) {

        if (
            ! isset($posted_ts_parts[$index])
            || ! is_array(
                $posted_ts_parts[$index]
            )
        ) {
            continue;
        }

        $posted_part =
            $posted_ts_parts[$index];

        $ts_form_parts[$index]['confirmed_qty'] =
            isset(
                $posted_part['confirmed_qty']
            )
                ? absint(
                    $posted_part['confirmed_qty']
                )
                : 0;

        $ts_form_parts[$index]['backorder_qty'] =
            isset(
                $posted_part['backorder_qty']
            )
                ? absint(
                    $posted_part['backorder_qty']
                )
                : 0;

        $ts_form_parts[$index]['ts_note'] =
            isset(
                $posted_part['ts_note']
            )
                ? sanitize_textarea_field(
                    $posted_part['ts_note']
                )
                : '';
    }
}

/*
 * TS Adjusted Order
 * Original "parts" data is never modified.
 */
$adjusted_parts   = [];
$backorder_parts  = [];
$unavailable_parts = [];

if (
    is_array($parts) &&
    is_array($ts_parts)
) {

    foreach ($ts_parts as $index => $ts_part) {

        $original_part = $parts[$index] ?? [];

        $part_no = sanitize_text_field(
            $ts_part['part_no']
                ?? $original_part['partnumber']
                ?? ''
        );

        $description = sanitize_text_field(
            $original_part['description'] ?? ''
        );

        $unit_price = (float) (
            $original_part['unit_price'] ?? 0
        );

        $ordered_qty = absint(
            $ts_part['ordered_qty']
                ?? $original_part['quantity']
                ?? 0
        );

        $confirmed_qty = absint(
            $ts_part['confirmed_qty'] ?? 0
        );

        $backorder_qty = absint(
            $ts_part['backorder_qty'] ?? 0
        );

        $unavailable_qty = max(
            0,
            $ordered_qty - $confirmed_qty - $backorder_qty
        );

        $ts_note = sanitize_text_field(
            $ts_part['ts_note'] ?? ''
        );

        /*
         * Parts that TS confirmed for this shipment.
         */
        if ($confirmed_qty > 0) {

            $adjusted_parts[] = [
                'part_no'        => $part_no,
                'description'    => $description,
                'quantity'       => $confirmed_qty,
                'unit_price'     => $unit_price,
                'extended_price' => $confirmed_qty * $unit_price,
                'ts_note'        => $ts_note,
            ];
        }
        /*
         * Parts remaining on backorder.
         */
        if ($backorder_qty > 0) {

            $backorder_parts[] = [
                'part_no'     => $part_no,
                'description' => $description,
                'quantity'    => $backorder_qty,
                'ts_note'     => $ts_note,
            ];
        }

        /*
         * Explicitly unavailable parts.
         */
        if ($unavailable_qty > 0) {

            $unavailable_parts[] = [
                'part_no'     => $part_no,
                'description' => $description,
                'quantity'    => $unavailable_qty,
                'ts_note'     => $ts_note,
            ];
        }
    }
}
?>



<section class="zati-form-section zati-adjusted-order">
<h2 class="zati-section-title">
        Shipping Order
    </h2>

    <p class="zati-adjusted-order-note">
        Final order prepared by Technical Support for shipment.
        The original SVC order remains unchanged.
    </p>

    <?php if (!empty($adjusted_parts)) : ?>

        <div class="zati-archive-table-wrap">

            <table class="zati-archive-table">

                <thead>
                    <tr>
                        <th>Part No.</th>
                        <th>Description</th>
                        <th>Qty to Ship</th>
                        <th>Unit Price</th>
                        <th>Extended Price</th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    $adjusted_total = 0;

                    foreach ($adjusted_parts as $adjusted_part) :

                        $adjusted_total +=
                            $adjusted_part['extended_price'];
                    ?>

                        <tr>

                            <td>
                                <?php echo esc_html(
                                    $adjusted_part['part_no']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $adjusted_part['description']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $adjusted_part['quantity']
                                ); ?>
                            </td>

                            <td>
                                $<?php echo esc_html(
                                    number_format(
                                        $adjusted_part['unit_price'],
                                        2
                                    )
                                ); ?>
                            </td>

                            <td>
                                $<?php echo esc_html(
                                    number_format(
                                        $adjusted_part['extended_price'],
                                        2
                                    )
                                ); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

                <tfoot>
                    <tr>

                        <th colspan="4">
                            Shipping Parts Total
                        </th>

                        <th>
                            $<?php echo esc_html(
                                number_format(
                                    $adjusted_total,
                                    2
                                )
                            ); ?>
                        </th>

                    </tr>
                </tfoot>

            </table>

        </div>

    <?php else : ?>

        <p>
            No parts are currently confirmed for shipment.
        </p>

    <?php endif; ?>


    <?php if (!empty($backorder_parts)) : ?>

        <h3 class="zati-adjusted-subtitle">
            Backordered Parts
        </h3>

        <div class="zati-archive-table-wrap">

            <table class="zati-archive-table">

                <thead>
                    <tr>
                        <th>Part No.</th>
                        <th>Description</th>
                        <th>Backorder Qty</th>
                        <th>TS Note</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($backorder_parts as $part) : ?>

                        <tr>

                            <td>
                                <?php echo esc_html(
                                    $part['part_no']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $part['description']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $part['quantity']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $part['ts_note'] !== ''
                                        ? $part['ts_note']
                                        : '—'
                                ); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>


    <?php if (!empty($unavailable_parts)) : ?>

        <h3 class="zati-adjusted-subtitle">
            Unavailable Parts
        </h3>

        <div class="zati-archive-table-wrap">

            <table class="zati-archive-table">

                <thead>
                    <tr>
                        <th>Part No.</th>
                        <th>Description</th>
                        <th>Ordered Qty</th>
                        <th>TS Note</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($unavailable_parts as $part) : ?>

                        <tr>

                            <td>
                                <?php echo esc_html(
                                    $part['part_no']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $part['description']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $part['quantity']
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $part['ts_note'] !== ''
                                        ? $part['ts_note']
                                        : '—'
                                ); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

<?php
$shipping_order_download_url = wp_nonce_url(
    add_query_arg(
        [
            'zati_download_shipping_order_xlsx' => 1,
            'order_id' => $order_id,
        ],
        home_url('/')
    ),
    'zati_download_shipping_order_' . $order_id
);
?>

<div class="zati-form-actions zati-shipping-order-actions">

    <a
        class="zati-primary-btn"
        href="<?php echo esc_url(
            $shipping_order_download_url
        ); ?>"
    >
        DOWNLOAD ORDER SHEET
    </a>

</div>

</section>


<section class="zati-ts-processing">
<form method="post">
    <h2 class="zati-section-title">
        TS Processing
    </h2>

    <div class="zati-ts-inline-status">

    <strong>
        Status:
    </strong>

    <span class="zati-order-status-badge">
        <?php
        echo esc_html(
            $order_status === 'Submitted'
                ? 'Submitted — Waiting for TS'
                : $order_status
        );
        ?>
    </span>

    </div>

    <?php if (
    ! empty($ts_processing_errors)
) : ?>

    <div class="zati-form-errors">

        <?php foreach (
            $ts_processing_errors as $error
        ) : ?>

            <div>
                <?php
                echo esc_html($error);
                ?>
            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

    <?php if ( ! empty( $ts_parts ) ) : ?>

        <div class="zati-table-wrap">

            <table class="zati-ts-parts-table">

                <thead>
                    <tr>
                       <th>Part No.</th>
                        <th>Ordered</th>
                        <th>Confirmed</th>
                        <th>Backorder</th>
                        <th>Unavailable</th>
                        <th>TS Note</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ( $ts_form_parts as $index => $ts_part ) : ?>

                    <tr>

                        <td>
                            <?php
                            $ts_part_no = $ts_part['part_no'] ?? '';

                            if (
                                $ts_part_no === '' &&
                                isset($parts[$index]['partnumber'])
                            ) {
                                $ts_part_no = $parts[$index]['partnumber'];
                            }

                            echo esc_html($ts_part_no);
                            ?>
                        </td>

                        <td>
                            <?php echo esc_html(
                                $ts_part['ordered_qty'] ?? 0
                            ); ?>
                        </td>

                        <td>
                            <input
                                type="number"
                                min="0"
                                name="ts_parts[<?php echo esc_attr( $index ); ?>][confirmed_qty]"
                                value="<?php echo esc_attr(
                                    $ts_part['confirmed_qty'] ?? 0
                                ); ?>"
                            >
                        </td>

                        <td>
                            <input
                                type="number"
                                min="0"
                                name="ts_parts[<?php echo esc_attr( $index ); ?>][backorder_qty]"
                                value="<?php echo esc_attr(
                                    $ts_part['backorder_qty'] ?? 0
                                ); ?>"
                            >
                        </td>


                             <?php
                        $ordered_qty = absint(
                            $ts_part['ordered_qty'] ?? 0
                        );

                        $confirmed_qty = absint(
                            $ts_part['confirmed_qty'] ?? 0
                        );

                        $backorder_qty = absint(
                            $ts_part['backorder_qty'] ?? 0
                        );

                        $unavailable_qty = max(
                            0,
                            $ordered_qty - $confirmed_qty - $backorder_qty
                        );
                        ?>

                        <td class="zati-ts-unavailable">
                            <?php
                            echo esc_html(
                                $order_status === 'Submitted'
                                    ? '—'
                                    : $unavailable_qty
                            );
                            ?>
                        </td>

                        <td>
                            <textarea
                                name="ts_parts[<?php echo esc_attr( $index ); ?>][ts_note]"
                                rows="2"
                            ><?php echo esc_textarea(
                                $ts_part['ts_note'] ?? ''
                            ); ?></textarea>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else : ?>

    <p>No parts found for this order.</p>

<?php endif; ?>

<?php wp_nonce_field(
    'zati_save_ts_processing_' . $order_id,
    'zati_ts_processing_nonce'
); ?>

    <div class="zati-form-actions zati-detail-actions">

        <a
            class="zati-secondary-btn"
            href="<?php echo esc_url(
                home_url('/parts-order-archive/')
            ); ?>"
        >
            BACK TO ARCHIVE
        </a>

        <button
            type="submit"
            name="zati_save_ts_processing"
            value="1"
            class="zati-primary-btn"
        >
            SAVE TS PROCESSING
        </button>

    </div>

    </form>

</section>


<?php endif; ?>

<?php
$svc_ts_parts = get_post_meta(
    $order_id,
    'ts_parts',
    true
);
?>

<section class="zati-ts-processing">

    <h2 class="zati-section-title">
        Order Processing Status
    </h2>

    <?php if ($order_status === 'Submitted') : ?>

        <p class="zati-ts-pending">
            TS processing has not started yet.
        </p>

    <?php elseif (
        is_array($svc_ts_parts) &&
        ! empty($svc_ts_parts)
    ) : ?>

        <div class="zati-table-wrap">

            <table class="zati-ts-parts-table zati-svc-status-table">

                <thead>
                    <tr>
                        <th>Part No.</th>
                        <th>Ordered</th>
                        <th>Confirmed</th>
                        <th>Backorder</th>
                        <th>Unavailable</th>
                        <th>Status</th>
                        <th>TS Note</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($svc_ts_parts as $index => $ts_part) : ?>

                        <?php
                        $ordered_qty =
                            absint(
                                $ts_part['ordered_qty']
                                ?? 0
                            );

                        $confirmed_qty =
                            absint(
                                $ts_part['confirmed_qty']
                                ?? 0
                            );

                        $backorder_qty =
                            absint(
                                $ts_part['backorder_qty']
                                ?? 0
                            );

                        $unavailable_qty =
                            max(
                                0,
                                $ordered_qty
                                - $confirmed_qty
                                - $backorder_qty
                            );

                        if (
                            $confirmed_qty > 0 &&
                            $backorder_qty > 0 &&
                            $unavailable_qty > 0
                        ) {

                            $item_status =
                                'Partial / Backordered / Unavailable';

                        } elseif (
                            $confirmed_qty > 0 &&
                            $backorder_qty > 0
                        ) {

                            $item_status =
                                'Partial / Backordered';

                        } elseif (
                            $confirmed_qty > 0 &&
                            $unavailable_qty > 0
                        ) {

                            $item_status =
                                'Partial / Unavailable';

                        } elseif (
                            $backorder_qty > 0 &&
                            $unavailable_qty > 0
                        ) {

                            $item_status =
                                'Backordered / Unavailable';

                        } elseif (
                            $confirmed_qty > 0
                        ) {

                            $item_status =
                                'Confirmed';

                        } elseif (
                            $backorder_qty > 0
                        ) {

                            $item_status =
                                'Backordered';

                        } elseif (
                            $unavailable_qty > 0
                        ) {

                            $item_status =
                                'Unavailable';

                        } else {

                            $item_status =
                                'Processed';
                        }
                        ?>

                        <tr>

                            <?php
                            $part_no =
                                sanitize_text_field(
                                    $ts_part['part_no']
                                    ?? (
                                        $parts[$index]['partnumber']
                                        ?? ''
                                    )
                                );
                            ?>

                            <td>
                                <?php echo esc_html(
                                    $part_no
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $ordered_qty
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $confirmed_qty
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $backorder_qty
                                ); ?>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $unavailable_qty
                                ); ?>
                            </td>

                            <td>
                                <span
                                    class="
                                        zati-processing-status
                                        zati-processing-status-<?php
                                        echo esc_attr(
                                            sanitize_html_class(
                                                strtolower(
                                                    str_replace(
                                                        ' ',
                                                        '-',
                                                        $item_status
                                                    )
                                                )
                                            )
                                        );
                                        ?>
                                    "
                                >
                                    <?php echo esc_html(
                                        $item_status
                                    ); ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                $ts_note =
                                    $ts_part['ts_note']
                                    ?? '';

                                echo esc_html(
                                    $ts_note !== ''
                                        ? $ts_note
                                        : '—'
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else : ?>

        <p class="zati-ts-pending">
            No TS processing information is available.
        </p>

    <?php endif; ?>

</section>




    </section>

</main>
<?php
get_template_part('app-footer');
get_footer('blank');