<?php
/*
Template Name: Open Backorders
Template Post Type: page
*/

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

/*
 * Admin / ZAC TS only
 */
$current_user = wp_get_current_user();

$allowed_roles = [
    'administrator',
    'zac_ts',
];

$can_view_backorders = !empty(
    array_intersect(
        $allowed_roles,
        (array) $current_user->roles
    )
);

if (!$can_view_backorders) {
    wp_safe_redirect(
        home_url('/parts-order-archive/')
    );
    exit;
}

/**
 * Release Backorder
 */
if (
    isset($_POST['zati_release_backorder']) &&
    isset($_POST['zati_release_backorder_nonce'])
) {

    $release_order_id = isset($_POST['order_id'])
        ? absint($_POST['order_id'])
        : 0;

    $part_index = isset($_POST['part_index'])
        ? absint($_POST['part_index'])
        : -1;

    $release_qty = isset($_POST['release_qty'])
        ? absint($_POST['release_qty'])
        : 0;

    if (
        $release_order_id > 0 &&
        $part_index >= 0 &&
        wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['zati_release_backorder_nonce']
                )
            ),
            'zati_release_backorder_'
                . $release_order_id
                . '_'
                . $part_index
        )
    ) {

        $ts_parts = get_post_meta(
            $release_order_id,
            'ts_parts',
            true
        );

        if (
            is_array($ts_parts) &&
            isset($ts_parts[$part_index])
        ) {

            $original_backorder_qty = absint(
                $ts_parts[$part_index]['backorder_qty']
                    ?? 0
            );

            $already_released_qty = absint(
                $ts_parts[$part_index]['backorder_released_qty']
                    ?? 0
            );

            $current_open_qty = max(
                0,
                $original_backorder_qty
                    - $already_released_qty
            );

            /*
             * Do not allow releasing more
             * than the remaining open qty.
             */
            $release_qty = min(
                $release_qty,
                $current_open_qty
            );

            if ($release_qty > 0) {

                $new_released_qty =
                    $already_released_qty
                    + $release_qty;

                $ts_parts[$part_index][
                    'backorder_released_qty'
                ] = $new_released_qty;

                $ts_parts[$part_index][
                    'backorder_last_released_at'
                ] = current_time('mysql');

                $ts_parts[$part_index][
                    'backorder_last_released_by'
                ] = get_current_user_id();

                $release_history = $ts_parts[$part_index][
                    'backorder_release_history'
                ] ?? [];

                if (!is_array($release_history)) {
                    $release_history = [];
                }

                $release_history[] = [
                    'qty'         => $release_qty,
                    'released_at' => current_time('mysql'),
                    'released_by' => get_current_user_id(),
                ];

                $ts_parts[$part_index][
                    'backorder_release_history'
                ] = $release_history;

                update_post_meta(
                    $release_order_id,
                    'ts_parts',
                    $ts_parts
                );
            }
        }

        wp_safe_redirect(
            home_url('/open-backorders/')
        );

        exit;
    }
}


/*
 * Filters
 */
$selected_service_center = isset($_GET['service_center'])
    ? sanitize_text_field(
        wp_unslash($_GET['service_center'])
    )
    : '';

$part_search = isset($_GET['part_no'])
    ? sanitize_text_field(
        wp_unslash($_GET['part_no'])
    )
    : '';


/*
 * Get all Parts Orders
 */
$order_ids = get_posts([
    'post_type'      => 'parts_order',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'date',
    'order'          => 'DESC',
]);


/*
 * Build Backorder rows
 */
$backorder_rows = [];
$service_centers = [];

foreach ($order_ids as $order_id) {

    $service_center_name = sanitize_text_field(
        get_post_meta(
            $order_id,
            'service_center_name',
            true
        )
    );

    $account_no = sanitize_text_field(
        get_post_meta(
            $order_id,
            'account_no',
            true
        )
    );

    $reference_no = sanitize_text_field(
        get_post_meta(
            $order_id,
            'reference_no',
            true
        )
    );

    $order_date = sanitize_text_field(
        get_post_meta(
            $order_id,
            'order_date',
            true
        )
    );

    $customer_po_no = sanitize_text_field(
        get_post_meta(
            $order_id,
            'customer_po_no',
            true
        )
    );

    $order_status = sanitize_text_field(
        get_post_meta(
            $order_id,
            'order_status',
            true
        )
    );

    $parts = get_post_meta(
        $order_id,
        'parts',
        true
    );

    $ts_parts = get_post_meta(
        $order_id,
        'ts_parts',
        true
    );


    /*
     * Service Center filter choices
     */
    if ($service_center_name !== '') {
        $service_centers[$service_center_name] =
            $service_center_name;
    }


    if (
        !is_array($parts) ||
        !is_array($ts_parts)
    ) {
        continue;
    }


    /*
     * Service Center filter
     */
    if (
        $selected_service_center !== '' &&
        $service_center_name !==
            $selected_service_center
    ) {
        continue;
    }


    foreach ($ts_parts as $index => $ts_part) {

        $backorder_qty = absint(
            $ts_part['backorder_qty'] ?? 0
        );

        $backorder_released_qty = absint(
            $ts_part['backorder_released_qty'] ?? 0
        );

        $open_qty = max(
            0,
            $backorder_qty - $backorder_released_qty
        );

        /*
        * Only Open Backorders
        */
        if ($open_qty <= 0) {
            continue;
        }


        $original_part = $parts[$index] ?? [];


        $part_no = sanitize_text_field(
            $ts_part['part_no']
                ?? $original_part['partnumber']
                ?? ''
        );

        $description = sanitize_text_field(
            $original_part['description']
                ?? ''
        );

        $ts_note = sanitize_textarea_field(
            $ts_part['ts_note']
                ?? ''
        );


        /*
         * Part No. search
         */
        if (
            $part_search !== '' &&
            stripos(
                $part_no,
                $part_search
            ) === false
        ) {
            continue;
        }


        $backorder_rows[] = [
            'order_id'                => $order_id,
            'part_index'              => $index,
            'service_center_name'     => $service_center_name,
            'account_no'              => $account_no,
            'reference_no'            => $reference_no,
            'order_date'              => $order_date,
            'customer_po_no'          => $customer_po_no,
            'order_status'            => $order_status,
            'part_no'                 => $part_no,
            'description'             => $description,
            'backorder_qty'           => $backorder_qty,
            'backorder_released_qty'  => $backorder_released_qty,
            'open_qty'                => $open_qty,
            'ts_note'                 => $ts_note,
        ];
    }
}


/*
 * Sort Service Centers alphabetically
 */
ksort($service_centers);


    /*
    * Summary
    */
    $total_backorder_lines = count(
        $backorder_rows
    );

    $total_backorder_qty = 0;

    foreach ($backorder_rows as $row) {
         $total_backorder_qty +=
            absint($row['open_qty']);
    }


    /*
    * Backorder Summary by Part
    */
    $part_summary = [];

    foreach ($backorder_rows as $row) {

        $part_no = $row['part_no'];

        if (!isset($part_summary[$part_no])) {
            $part_summary[$part_no] = [
                'part_no'     => $part_no,
                'description' => $row['description'],
                'qty'         => 0,
            ];
        }

        $part_summary[$part_no]['qty'] +=
                absint($row['open_qty']);
    }

    uasort(
        $part_summary,
        function ($a, $b) {
            return $b['qty'] <=> $a['qty'];
        }
    );


    get_header('blank');
    get_template_part('app-header');
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

        <a
            class="zati-subnav-item"
            href="<?php echo esc_url(
                home_url('/parts-order-archive/')
            ); ?>"
        >
            Parts Order Archive
        </a>

        <span class="zati-subnav-item active">
            Open Backorders
        </span>

    </div>

<main class="zati-archive-page">

    <section class="zati-archive-wrap">

        <div class="zati-backorder-header">

            <h1>
                Open Backorders
            </h1>

            <p>
                View parts currently recorded as backordered by Technical Support.
            </p>

        </div>


        <!-- Filters -->

        <form
                method="get"
                class="zati-open-backorders-filter"
            >

                <div class="zati-filter-row">

            <div class="zati-archive-filter-field">

                <label for="service_center">
                    Service Center
                </label>

                <select
                    id="service_center"
                    name="service_center"
                >

                    <option value="">
                        All Service Centers
                    </option>

                    <?php
                    foreach (
                        $service_centers
                        as $service_center
                    ) :
                    ?>

                        <option
                            value="<?php
                            echo esc_attr(
                                $service_center
                            );
                            ?>"
                            <?php
                            selected(
                                $selected_service_center,
                                $service_center
                            );
                            ?>
                        >
                            <?php
                            echo esc_html(
                                $service_center
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="zati-archive-filter-field">

                <label for="part_no">
                    Part No.
                </label>

                <input
                    type="text"
                    id="part_no"
                    name="part_no"
                    value="<?php
                    echo esc_attr(
                        $part_search
                    );
                    ?>"
                    placeholder="Search Part No."
                >

            </div>


            <div class="zati-filter-actions">

                <button
                    type="submit"
                    class="zati-archive-filter-btn"
                >
                    FILTER
                </button>

                <a
                    class="zati-archive-action-btn"
                    href="<?php echo esc_url(
                        home_url('/parts-order-archive/')
                    ); ?>"
                >
                    PARTS ORDER ARCHIVE
                </a>

                <a
                    class="zati-archive-action-btn"
                    href="<?php echo esc_url(
                        home_url('/backorder-history/')
                    ); ?>"
                >
                    BACKORDER HISTORY
                </a>

            </div>

            </div><!-- .zati-filter-row -->

            </form>


        <!-- Summary -->

       <div class="zati-archive-summary">

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Backorder Lines
                </span>

                <strong class="zati-archive-summary-value">
                    <?php echo esc_html(
                        $total_backorder_lines
                    ); ?>
                </strong>

            </div>

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Total Open Qty
                </span>

                <strong class="zati-archive-summary-value">
                    <?php echo esc_html(
                        $total_backorder_qty
                    ); ?>
                </strong>

            </div>

      </div>

        <?php if (!empty($part_summary)) : ?>

            <div class="zati-backorder-part-summary">

                <h2>
                    Backorder Summary by Part
                </h2>

                <div class="zati-archive-table-wrap">

                    <table class="zati-archive-table">

                        <thead>
                            <tr>
                                <th>Part No.</th>
                                <th>Description</th>
                                <th>Total Open Qty</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach (
                                $part_summary as $summary
                            ) : ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo esc_html(
                                                $summary['part_no']
                                            ); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo esc_html(
                                            $summary['description']
                                        ); ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php echo esc_html(
                                                $summary['qty']
                                            ); ?>
                                        </strong>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        <?php endif; ?>



        <!-- Backorder Table -->

        <div class="zati-archive-table-wrap">

            <table class="zati-archive-table">

                <thead>
                    <tr>

                  
                        <th>Account No.</th>

                        <th>Reference No.</th>

                        <th>Order Date</th>

                        <th>Customer PO No.</th>

                        <th>Part No.</th>

                        <th>Original BO</th>

                        <th>Released</th>

                        <th>Open Qty</th>

                        <th>Action</th>

                    </tr>
                </thead>


                <tbody>

                <?php if (
                    empty($backorder_rows)
                ) : ?>

                    <tr>

                        <td
                            colspan="10"
                            style="text-align:center;"
                        >
                            No open backorders found.
                        </td>

                    </tr>

                <?php else : ?>

                    <?php
                    foreach (
                        $backorder_rows
                        as $row
                    ) :
                    ?>

                        <tr>


                            <td>
                                <?php
                                echo esc_html(
                                    $row['account_no']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $row['reference_no']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $row['order_date']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    $row['customer_po_no']
                                );
                                ?>
                            </td>

                            <td>
                                <strong>
                                    <?php
                                    echo esc_html(
                                        $row['part_no']
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo esc_html(
                                    $row['backorder_qty']
                                ); ?>
                            </td>

                            <td>

                                <form
                                    method="post"
                                    class="zati-backorder-release-form"
                                >

                                    <?php
                                    wp_nonce_field(
                                        'zati_release_backorder_'
                                            . $row['order_id']
                                            . '_'
                                            . $row['part_index'],
                                        'zati_release_backorder_nonce'
                                    );
                                    ?>

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?php echo esc_attr(
                                            $row['order_id']
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="part_index"
                                        value="<?php echo esc_attr(
                                            $row['part_index']
                                        ); ?>"
                                    >

                                    <input
                                        type="number"
                                        name="release_qty"
                                        min="1"
                                        max="<?php echo esc_attr(
                                            $row['open_qty']
                                        ); ?>"
                                        value=""
                                        placeholder="0"
                                    >

                                    <button
                                        type="submit"
                                        name="zati_release_backorder"
                                        value="1"
                                    >
                                        RELEASE
                                    </button>

                                </form>

                                <?php if (
                                    $row['backorder_released_qty'] > 0
                                ) : ?>

                                    <div class="zati-backorder-released-total">
                                        Released:
                                        <?php echo esc_html(
                                            $row['backorder_released_qty']
                                        ); ?>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>
                                <strong>
                                    <?php echo esc_html(
                                        $row['open_qty']
                                    ); ?>
                                </strong>
                            </td>

                            <td>

                                <a
                                    href="<?php
                                    echo esc_url(
                                        add_query_arg(
                                            'order_id',
                                            $row['order_id'],
                                            home_url(
                                                '/parts-order-detail/'
                                            )
                                        )
                                    );
                                    ?>"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


<style>

.zati-backorder-filter {
    display: flex;
    gap: 18px;
    align-items: flex-end;
    padding: 20px;
    margin: 25px 0;
    background: #f5f7f9;
    border: 1px solid #d7dee5;
}

.zati-backorder-filter > div {
    min-width: 220px;
}

.zati-backorder-filter label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
}

.zati-backorder-filter select,
.zati-backorder-filter input {
    width: 100%;
    min-height: 42px;
}

.zati-backorder-filter button {
    min-height: 42px;
    padding: 0 32px;
}

.zati-backorder-summary {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.zati-backorder-summary > div {
    border: 1px solid #d7dee5;
    padding: 18px;
    background: #fff;
}

.zati-backorder-summary span {
    display: block;
    margin-bottom: 8px;
}

.zati-backorder-summary strong {
    font-size: 22px;
}

@media (max-width: 800px) {

    .zati-backorder-filter {
        display: block;
    }

    .zati-backorder-filter > div {
        margin-bottom: 15px;
    }

    .zati-backorder-summary {
        grid-template-columns: 1fr;
    }
}

</style>


<?php
get_footer();