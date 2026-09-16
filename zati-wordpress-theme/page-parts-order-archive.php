<?php
/*
Template Name: Parts Order Archive
Template Post Type: page
*/

if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

if (!zati_current_user_can_access_parts_order()) {
    wp_safe_redirect(home_url('/svc-form/'));
    exit;
}

get_header('blank');
get_template_part('app-header');

$current_user = wp_get_current_user();

$is_ts_user =
    zati_is_parts_order_ts_user($current_user);

$is_svc_user =
    zati_is_parts_order_svc_user($current_user);

/*
 * Filters
 */
$selected_year = isset($_GET['order_year'])
    ? absint($_GET['order_year'])
    : (int) wp_date('Y');

$selected_month = isset($_GET['order_month'])
    ? absint($_GET['order_month'])
    : 0;

$selected_account_no = (
    $is_ts_user &&
    isset($_GET['service_center'])
)
    ? sanitize_text_field(
        wp_unslash($_GET['service_center'])
    )
    : '';

$selected_status = isset($_GET['order_status'])
    ? sanitize_text_field(
        wp_unslash($_GET['order_status'])
    )
    : '';

$query_args =
    zati_get_parts_order_archive_query_args(
        [
            'year'       => $selected_year,
            'month'      => $selected_month,
            'account_no' => $selected_account_no,
            'status'     => $selected_status,
        ]
    );

$orders_query = new WP_Query($query_args);

$total_orders = 0;
$total_parts_amount = 0;
$summary_service_centers = [];

if ( $orders_query->have_posts() ) {

    foreach ( $orders_query->posts as $order_post ) {

        $summary_order_id = $order_post->ID;

        $total_parts_amount += (float) get_post_meta(
            $summary_order_id,
            'parts_total',
            true
        );

        $summary_account_no = sanitize_text_field(
            get_post_meta(
                $summary_order_id,
                'account_no',
                true
            )
        );

        $summary_service_name = sanitize_text_field(
            get_post_meta(
                $summary_order_id,
                'service_center_name',
                true
            )
        );

        if ( $summary_account_no !== '' ) {

            $summary_service_centers[$summary_account_no] =
                $summary_service_name;
        }
    }

    $total_orders = count(
        $orders_query->posts
    );
}

$service_center_summary = '—';

if ( count($summary_service_centers) === 1 ) {

    $summary_account_no = array_key_first(
        $summary_service_centers
    );

    $summary_service_name =
        $summary_service_centers[$summary_account_no];

    $service_center_summary =
        $summary_service_name !== ''
            ? $summary_service_name
                . ' ('
                . $summary_account_no
                . ')'
            : $summary_account_no;

} elseif (
    count($summary_service_centers) > 1
) {

    $service_center_summary =
        'Multiple Service Centers';
}
/*
 * Available Service Centers for TS filter.
 */
$service_centers = [];

if ( $is_ts_user ) {

    $account_posts = get_posts(
        [
            'post_type'      => 'parts_order',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]
    );

    foreach ( $account_posts as $filter_order_id ) {

        $account_no = sanitize_text_field(
            get_post_meta(
                $filter_order_id,
                'account_no',
                true
            )
        );

        $service_center_name = sanitize_text_field(
            get_post_meta(
                $filter_order_id,
                'service_center_name',
                true
            )
        );

        if ( $account_no === '' ) {
            continue;
        }

        $service_centers[$account_no] =
            $service_center_name !== ''
                ? $service_center_name
                    . ' (' . $account_no . ')'
                : $account_no;
    }

    natcasesort( $service_centers );
}

$order_statuses = [
    'Submitted'    => 'Submitted',
    'TS Completed' => 'TS Completed',
];

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
            Parts Order Archive
        </span>

    </div>

<main class="zati-archive-page">

    <section class="zati-archive-wrap">


        <!-- =========================
             Archive Header
        ========================== -->

        <div class="zati-archive-header">

            <div class="zati-archive-header-text">

                <h1>
                    Parts Order Archive
                </h1>

                <p class="zati-archive-description">
                    View submitted parts orders and review order status.
                </p>

            </div>

            <?php if ( $is_svc_user ) : ?>

                <a
                    href="<?php echo esc_url(
                        add_query_arg(
                            'new',
                            '1',
                            home_url('/parts-order/')
                        )
                    ); ?>"
                    class="zati-archive-new-btn"
                >
                    SUBMIT NEW ORDER
                </a>

            <?php endif; ?>

        </div>


        <!-- =========================
             Archive Filters
        ========================== -->

        <form
            class="zati-archive-filters<?php
                echo $is_ts_user ? ' is-ts' : '';
            ?>"
            method="get"
            action="<?php echo esc_url(
                home_url('/parts-order-archive/')
            ); ?>"
        >

            <?php if ( $is_ts_user ) : ?>

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
                            as $account_no => $label
                        ) : ?>

                            <option
                                value="<?php echo esc_attr(
                                    $account_no
                                ); ?>"
                                <?php selected(
                                    $selected_account_no,
                                    $account_no
                                ); ?>
                            >
                                <?php echo esc_html(
                                    $label
                                ); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            <?php endif; ?>


            <div class="zati-archive-filter-field">

                <label for="order_year">
                    Year
                </label>

                <select
                    id="order_year"
                    name="order_year"
                >

                    <?php
                    $current_year = (int) wp_date('Y');

                    for (
                        $year = $current_year;
                        $year >= $current_year - 5;
                        $year--
                    ) :
                    ?>

                        <option
                            value="<?php echo esc_attr(
                                $year
                            ); ?>"
                            <?php selected(
                                $selected_year,
                                $year
                            ); ?>
                        >
                            <?php echo esc_html(
                                $year
                            ); ?>
                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <div class="zati-archive-filter-field">

                <label for="order_month">
                    Month
                </label>

                <select
                    id="order_month"
                    name="order_month"
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
                            value="<?php echo esc_attr(
                                $month
                            ); ?>"
                            <?php selected(
                                $selected_month,
                                $month
                            ); ?>
                        >
                            <?php echo esc_html(
                                DateTime::createFromFormat(
                                    '!m',
                                    (string) $month
                                )->format('F')
                            ); ?>
                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <div class="zati-archive-filter-field">

                <label for="order_status">
                    Status
                </label>

                <select
                    id="order_status"
                    name="order_status"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach (
                        $order_statuses
                        as $status_value => $status_label
                    ) : ?>

                        <option
                            value="<?php echo esc_attr(
                                $status_value
                            ); ?>"
                            <?php selected(
                                $selected_status,
                                $status_value
                            ); ?>
                        >
                            <?php echo esc_html(
                                $status_label
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="zati-archive-filter-btn"
            >
                FILTER
            </button>

             <a
                class="zati-archive-action-btn"
                href="<?php echo esc_url(
                    home_url('/open-backorders/')
                ); ?>"
            >
                OPEN BACKORDERS
            </a>

            <a
                class="zati-archive-action-btn"
                href="<?php echo esc_url(
                    home_url('/backorder-history/')
                ); ?>"
            >
                BACKORDER HISTORY
            </a>

        </form>


        <!-- =========================
             Archive Summary
        ========================== -->

        <div class="zati-archive-summary">

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Service Center
                </span>

                <strong class="zati-archive-summary-value svcn">
                    <?php echo esc_html(
                        $service_center_summary
                    ); ?>
                </strong>

            </div>


            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Total Orders
                </span>

                <strong class="zati-archive-summary-value">
                    <?php echo esc_html(
                        number_format($total_orders)
                    ); ?>
                </strong>

            </div>


            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Parts Total
                </span>

                <strong
                    class="zati-archive-summary-value is-money"
                >
                    $<?php echo esc_html(
                        number_format(
                            $total_parts_amount,
                            2
                        )
                    ); ?>
                </strong>

            </div>

        </div>


        <!-- =========================
             Archive Table
        ========================== -->

        <?php if ( $orders_query->have_posts() ) : ?>

            <div class="zati-archive-table-wrap">

                <table class="zati-archive-table">

                    <thead>

                        <tr>

                            <th>Reference No.</th>

                            <?php if ( $is_ts_user ) : ?>

                                <th>Account No.</th>
                                <th>Service Center</th>

                            <?php endif; ?>

                            <th>Order Date</th>
                            <th>Customer PO No.</th>
                            <th>Parts Total</th>
                            <th>Status</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php
                        while (
                            $orders_query->have_posts()
                        ) :

                            $orders_query->the_post();

                            $order_id = get_the_ID();

                            $reference_no =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'reference_no',
                                        true
                                    )
                                );

                            $account_no =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'account_no',
                                        true
                                    )
                                );

                            $service_center_name =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'service_center_name',
                                        true
                                    )
                                );

                            $customer_po_no =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'customer_po_no',
                                        true
                                    )
                                );

                            $order_date =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'order_date',
                                        true
                                    )
                                );

                            $ship_via =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'requested_ship_via',
                                        true
                                    )
                                );

                            $parts_total =
                                (float) get_post_meta(
                                    $order_id,
                                    'parts_total',
                                    true
                                );

                            $order_status =
                                sanitize_text_field(
                                    get_post_meta(
                                        $order_id,
                                        'order_status',
                                        true
                                    )
                                );

                            if ( $order_status === '' ) {
                                $order_status = 'Submitted';
                            }

                            $detail_url =
                                add_query_arg(
                                    'order_id',
                                    $order_id,
                                    home_url(
                                        '/parts-order-detail/'
                                    )
                                );

                        ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?php echo esc_html(
                                            $reference_no ?: '—'
                                        ); ?>
                                    </strong>

                                </td>


                                <?php if ( $is_ts_user ) : ?>

                                    <td class="zati-archive-account-no">

                                        <strong>
                                            <?php echo esc_html(
                                                $account_no ?: '—'
                                            ); ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?php echo esc_html(
                                            $service_center_name ?: '—'
                                        ); ?>

                                    </td>

                                <?php endif; ?>


                                <td>

                                    <?php echo esc_html(
                                        $order_date ?: '—'
                                    ); ?>

                                </td>


                                <td>

                                    <?php echo esc_html(
                                        $customer_po_no ?: '—'
                                    ); ?>

                                </td>



                                <td class="zati-money">

                                    $<?php echo esc_html(
                                        number_format(
                                            $parts_total,
                                            2
                                        )
                                    ); ?>

                                </td>


                                <td>

                                    <span
                                        class="zati-order-status"
                                        data-status="<?php
                                            echo esc_attr(
                                                sanitize_html_class(
                                                    strtolower(
                                                        str_replace(
                                                            ' ',
                                                            '-',
                                                            $order_status
                                                        )
                                                    )
                                                )
                                            );
                                        ?>"
                                    >
                                        <?php echo esc_html(
                                            $order_status
                                        ); ?>
                                    </span>

                                </td>


                                    <td class="zati-archive-actions">

                                    <a
                                        class="zati-archive-view-link"
                                        href="<?php echo esc_url(
                                            $detail_url
                                        ); ?>"
                                    >
                                        View
                                    </a>

                                    <span class="zati-archive-action-separator">|</span>

                                    <a
                                        class="zati-archive-excel-link"
                                        href="<?php echo esc_url(
                                            add_query_arg(
                                                [
                                                    'zati_download_parts_order_xlsx' => 1,
                                                    'order_id' => $order_id,
                                                ],
                                                home_url('/')
                                            )
                                        ); ?>"
                                    >
                                        Excel
                                    </a>

                            </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


        <?php else : ?>

            <div class="zati-archive-empty">
                No Parts Orders were found for the selected period.
            </div>

        <?php endif; ?>


    </section>

</main>


<?php

wp_reset_postdata();

get_template_part('app-footer');
get_footer('blank');