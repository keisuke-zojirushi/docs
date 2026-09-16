<?php
/*
Template Name: Backorder History
Template Post Type: page
*/

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();

$allowed_roles = [
    'administrator',
    'zac_ts',
];

$can_view_history = !empty(
    array_intersect(
        $allowed_roles,
        (array) $current_user->roles
    )
);

if (!$can_view_history) {
    wp_safe_redirect(
        home_url('/parts-order-archive/')
    );
    exit;
}


/*
 * Filters
 */
$selected_service_center = isset($_GET['service_center'])
    ? sanitize_text_field(
        wp_unslash($_GET['service_center'])
    )
    : '';


$selected_year = isset($_GET['release_year'])
    ? absint($_GET['release_year'])
    : 0;

$selected_month = isset($_GET['release_month'])
    ? absint($_GET['release_month'])
    : 0;


/*
 * Get Parts Orders
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
 * Build History rows
 */
$history_rows = [];
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

    if ($service_center_name !== '') {
        $service_centers[$service_center_name] =
            $service_center_name;
    }

    if (
        $selected_service_center !== '' &&
        $service_center_name !== $selected_service_center
    ) {
        continue;
    }

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

    if (
        !is_array($parts) ||
        !is_array($ts_parts)
    ) {
        continue;
    }

    foreach ($ts_parts as $index => $ts_part) {

        $release_history =
            $ts_part['backorder_release_history']
            ?? [];

        if (
            !is_array($release_history) ||
            empty($release_history)
        ) {
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

    
        foreach ($release_history as $release) {

            $released_qty = absint(
                $release['qty'] ?? 0
            );

            $released_at = sanitize_text_field(
                $release['released_at'] ?? ''
            );

            $released_by = absint(
                $release['released_by'] ?? 0
            );

            if (
                $released_qty <= 0 ||
                $released_at === ''
            ) {
                continue;
            }

            $timestamp = strtotime($released_at);

            if (!$timestamp) {
                continue;
            }

            if (
                $selected_year > 0 &&
                (int) wp_date('Y', $timestamp)
                    !== $selected_year
            ) {
                continue;
            }

            if (
                $selected_month > 0 &&
                (int) wp_date('n', $timestamp)
                    !== $selected_month
            ) {
                continue;
            }

            $released_user =
                $released_by > 0
                    ? get_userdata($released_by)
                    : false;

            $released_by_name =
                $released_user
                    ? $released_user->display_name
                    : '—';

            $history_rows[] = [
                'order_id'            => $order_id,
                'service_center_name' => $service_center_name,
                'account_no'          => $account_no,
                'reference_no'        => $reference_no,
                'order_date'          => $order_date,
                'customer_po_no'      => $customer_po_no,
                'part_no'             => $part_no,
                'description'         => $description,
                'released_qty'        => $released_qty,
                'released_at'         => $released_at,
                'timestamp'           => $timestamp,
                'released_by_name'    => $released_by_name,
            ];
        }
    }
}


/*
 * Newest release first
 */
usort(
    $history_rows,
    function ($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    }
);

ksort($service_centers);


/*
 * Summary
 */
$total_release_events = count($history_rows);

$total_released_qty = 0;

foreach ($history_rows as $row) {
    $total_released_qty +=
        absint($row['released_qty']);
}


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
            Backorder History
        </span>

    </div>


<main class="zati-archive-page">

    <section class="zati-archive-wrap">

        <div class="zati-backorder-history-header">

            <h1>
                Backorder History
            </h1>

            <p>
                View previously released backorder activity.
            </p>

        </div>


            <form
            method="get"
            class="zati-backorder-history-filter zati-open-backorders-filter"
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

                        <?php foreach (
                            $service_centers
                            as $service_center
                        ) : ?>

                            <option
                                value="<?php
                                echo esc_attr($service_center);
                                ?>"
                                <?php
                                selected(
                                    $selected_service_center,
                                    $service_center
                                );
                                ?>
                            >
                                <?php
                                echo esc_html($service_center);
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="zati-archive-filter-field">

                    <label for="release_year">
                        Year
                    </label>

                    <select
                        id="release_year"
                        name="release_year"
                    >

                        <option value="0">
                            All Years
                        </option>

                        <?php
                        $current_year = (int) wp_date('Y');

                        for (
                            $year = $current_year;
                            $year >= $current_year - 5;
                            $year--
                        ) :
                        ?>

                            <option
                                value="<?php echo esc_attr($year); ?>"
                                <?php
                                selected(
                                    $selected_year,
                                    $year
                                );
                                ?>
                            >
                                <?php echo esc_html($year); ?>
                            </option>

                        <?php endfor; ?>

                    </select>

                </div>


                <div class="zati-archive-filter-field">

                    <label for="release_month">
                        Month
                    </label>

                    <select
                        id="release_month"
                        name="release_month"
                    >

                        <option value="0">
                            All Months
                        </option>

                        <?php
                        for (
                            $month = 1;
                            $month <= 12;
                            $month++
                        ) :
                        ?>

                            <option
                                value="<?php
                                echo esc_attr($month);
                                ?>"
                                <?php
                                selected(
                                    $selected_month,
                                    $month
                                );
                                ?>
                            >
                                <?php
                                echo esc_html(
                                    DateTime::createFromFormat(
                                        '!m',
                                        (string) $month
                                    )->format('F')
                                );
                                ?>
                            </option>

                        <?php endfor; ?>

                    </select>

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
                            home_url('/open-backorders/')
                        ); ?>"
                    >
                        OPEN BACKORDERS
                    </a>

                </div>

            </div>

        </form>

 <div class="zati-archive-summary">

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Release Events
                </span>

                <strong class="zati-archive-summary-value">

                    <?php
                    echo esc_html(
                        $total_release_events
                    );
                    ?>
                </strong>
            </div>

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Total Released Qty
                </span>

                <strong class="zati-archive-summary-value">

                    <?php
                    echo esc_html(
                        $total_released_qty
                    );
                    ?>
                </strong>
            </div>

        </div>


        <div class="zati-archive-table-wrap">

            <table class="zati-archive-table">

                <thead>

                    <tr>
                        <th>Account No.</th>
                        <th>Reference No.</th>
                        <th>Customer PO No.</th>
                        <th>Part No.</th>
                        <th>Released Qty</th>
                        <th>Released Date</th>
                        <th>Released By</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (
                        empty($history_rows)
                    ) : ?>

                        <tr>
                            <td
                                colspan="8"
                                style="text-align:center;"
                            >
                                No backorder history found.
                            </td>
                        </tr>

                    <?php else : ?>

                        <?php foreach (
                            $history_rows as $row
                        ) : ?>

                            <tr>

                                <td>
                                    <?php echo esc_html(
                                        $row['account_no']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $row['reference_no']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $row['customer_po_no']
                                            ?: '—'
                                    ); ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo esc_html(
                                            $row['part_no']
                                        ); ?>
                                    </strong>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo esc_html(
                                            $row['released_qty']
                                        ); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        wp_date(
                                            'M j, Y g:i A',
                                            $row['timestamp']
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        $row['released_by_name']
                                    ); ?>
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




<?php
get_template_part('app-footer');
get_footer('blank');