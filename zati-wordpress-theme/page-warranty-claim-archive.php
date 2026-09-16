<?php
/*
Template Name: Warranty Claim Archive
Template Post Type: page
*/


/* =========================
   Archive Filters
========================= */

$current_year = (int) wp_date('Y');

$selected_year = isset($_GET['claim_year'])
    ? absint($_GET['claim_year'])
    : $current_year;

$selected_month = isset($_GET['claim_month'])
    ? absint($_GET['claim_month'])
    : 0;

/* =========================
   TS Service Center Filter
========================= */

$is_warranty_ts = zati_is_warranty_claim_ts_user();

$selected_account_no = isset($_GET['service_center'])
    ? sanitize_text_field(
        wp_unslash($_GET['service_center'])
    )
    : '';

$service_center_options = [];

if ($is_warranty_ts) {

    $service_center_claim_ids = get_posts([
        'post_type'      => 'warranty_claim',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    foreach ($service_center_claim_ids as $claim_id) {

        $account_no = sanitize_text_field(
            get_post_meta(
                $claim_id,
                'account_no',
                true
            )
        );

        $service_center_name = sanitize_text_field(
            get_post_meta(
                $claim_id,
                'service_center',
                true
            )
        );

        if ($account_no === '') {
            continue;
        }

        /*
         * 新しいClaimのService Center名を優先
         */
        if (!isset($service_center_options[$account_no])) {
            $service_center_options[$account_no] = [
                'account_no' => $account_no,
                'name'       => $service_center_name,
            ];
        }
    }

    uasort(
        $service_center_options,
        static function ($a, $b) {

            $a_label = $a['name'] !== ''
                ? $a['name']
                : $a['account_no'];

            $b_label = $b['name'] !== ''
                ? $b['name']
                : $b['account_no'];

            return strcasecmp($a_label, $b_label);
        }
    );
}

/* =========================
   Build Query
========================= */

$query_args = zati_get_warranty_archive_query_args([
    'year'       => $selected_year,
    'month'      => $selected_month,
    'account_no' => $selected_account_no,
]);

$claims_query = new WP_Query($query_args);

/* =========================
   CSV Download URL
========================= */

$csv_download_url = wp_nonce_url(
    add_query_arg(
        [
            'zati_warranty_download' => 'csv',
            'service_center'         => $selected_account_no,
            'claim_year'             => $selected_year,
            'claim_month'            => $selected_month,
        ],
        get_permalink()
    ),
    'zati_download_warranty_csv'
);

$attachments_download_url = wp_nonce_url(
    add_query_arg(
        [
            'zati_warranty_download' => 'attachments',
            'service_center'         => $selected_account_no,
            'claim_year'             => $selected_year,
            'claim_month'            => $selected_month,
        ],
        get_permalink()
    ),
    'zati_download_warranty_attachments'
);

/* =========================
   Monthly Summary Download URL
========================= */

$monthly_summary_url = wp_nonce_url(
    add_query_arg(
        [
            'zati_warranty_download' => 'monthly_summary',
            'service_center'         => $selected_account_no,
            'claim_year'             => $selected_year,
            'claim_month'            => $selected_month,
        ],
        get_permalink()
    ),
    'zati_download_warranty_monthly_summary'
);

/* =========================
   Summary Values
========================= */

$total_claims  = 0;
$total_payment = 0;
$service_centers = [];

/*
 * ACF Date Picker may contain:
 * Ymd, Y-m-d, or m/d/Y.
 */
$format_claim_date = static function ($value) {

    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    $formats = [
        'Ymd',
        'Y-m-d',
        'm/d/Y',
        'd/m/Y',
    ];

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);

        if ($date instanceof DateTime) {
            return $date->format('m/d/Y');
        }
    }

    $timestamp = strtotime($value);

    if ($timestamp !== false) {
        return wp_date('m/d/Y', $timestamp);
    }

    return $value;
};

if ($claims_query->have_posts()) {
    foreach ($claims_query->posts as $claim_post) {

        $claim_id = $claim_post->ID;

        $total_payment += (float) get_post_meta(
            $claim_id,
            'total_payment',
            true
        );

        $service_center = sanitize_text_field(
            get_post_meta($claim_id, 'service_center', true)
        );

        $account_no = sanitize_text_field(
            get_post_meta($claim_id, 'account_no', true)
        );

        $service_key = trim($service_center . '|' . $account_no);

        if ($service_key !== '|') {
            $service_centers[$service_key] = [
                'name'       => $service_center,
                'account_no' => $account_no,
            ];
        }
    }

    $total_claims = count($claims_query->posts);
}

/* =========================
   Service Center Summary
========================= */

$service_center_summary = '—';

if (count($service_centers) === 1) {

    $service_data = reset($service_centers);

    $service_center_summary = $service_data['account_no'];

    if (
        $service_data['name'] !== '' &&
        $service_data['account_no'] !== ''
    ) {
        $service_center_summary =
            $service_data['name'] .
            ' (' .
            $service_data['account_no'] .
            ')';
    } elseif ($service_data['name'] !== '') {
        $service_center_summary = $service_data['name'];
    }

} elseif (count($service_centers) > 1) {
    $service_center_summary = 'Multiple Service Centers';
}

$downloads_enabled = (
    $selected_month >= 1 &&
    $selected_month <= 12 &&
    $total_claims > 0
);

$monthly_summary_enabled = (
    $is_warranty_ts &&
    $selected_account_no !== '' &&
    $selected_year > 0 &&
    $selected_month >= 1 &&
    $selected_month <= 12 &&
    $total_claims > 0
);

$show_service_center_column = (
    $is_warranty_ts &&
    $selected_account_no === ''
);


/* =========================
   Available Years
========================= */

$available_years = [];

$year_posts = get_posts([
    'post_type'      => 'warranty_claim',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

foreach ($year_posts as $claim_id) {
    $year = (int) get_the_date('Y', $claim_id);

    if ($year > 0) {
        $available_years[$year] = $year;
    }
}

if (empty($available_years)) {
    $available_years[$current_year] = $current_year;
}

krsort($available_years);

/* =========================
   Page Output
========================= */

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
            home_url('/warranty-claim/')
        ); ?>"
    >
        Warranty Claim
    </a>

    <span class="zati-subnav-item active">
        Warranty Claim Archive
    </span>

</div>

<main class="zati-archive-page">

    <section class="zati-archive-wrap">

        <div class="zati-archive-header">

            <div class="zati-archive-header-text">

                <h1>Warranty Claim Archive</h1>

                <p class="zati-archive-description">
                    View submitted warranty claims and download monthly claim data.
                </p>

            </div>

            <a
                href="<?php echo esc_url(home_url('/warranty-claim/')); ?>"
                class="zati-archive-new-btn"
            >
                SUBMIT NEW CLAIM
            </a>

        </div>

        <?php
        $claim_notice = isset($_GET['claim_notice'])
            ? sanitize_key(wp_unslash($_GET['claim_notice']))
            : '';
        ?>

        <?php if ($claim_notice === 'not_found') : ?>

            <div class="zati-archive-notice" role="alert">
                The warranty claim could not be found or you do not have
                permission to view it.
            </div>

        <?php endif; ?>
<?php
$download_notice = isset($_GET['download_notice'])
    ? sanitize_key(
        wp_unslash($_GET['download_notice'])
    )
    : '';

$download_messages = [
    'select_month' =>
        'Please select a month before downloading attachments.',

    'no_claims' =>
        'No warranty claims were found for the selected period.',

    'no_attachments' =>
        'No attachments were found for the selected period.',

    'zip_unavailable' =>
        'ZIP downloads are not available on this server.',

    'zip_failed' =>
        'The attachment ZIP file could not be created.',
];
?>

<?php if (
    $download_notice !== '' &&
    isset($download_messages[$download_notice])
) : ?>

    <div class="zati-archive-notice" role="alert">
        <?php echo esc_html(
            $download_messages[$download_notice]
        ); ?>
    </div>

<?php endif; ?>

        <form
           class="zati-archive-filters<?php echo $is_warranty_ts ? ' is-ts' : ''; ?>"
           method="get"
           action="<?php echo esc_url(get_permalink()); ?>"
               >

<?php if ($is_warranty_ts) : ?>

    <div class="zati-archive-filter-field">

        <label for="archive-service-center">
            Service Center
        </label>

        <select
            id="archive-service-center"
            name="service_center"
        >
            <option value="">
                All Service Centers
            </option>

            <?php foreach (
                $service_center_options as $service_center
            ) : ?>

                <?php
                $service_center_label =
                    $service_center['account_no'];

                if ($service_center['name'] !== '') {
                    $service_center_label =
                        $service_center['name']
                        . ' ('
                        . $service_center['account_no']
                        . ')';
                }
                ?>

                <option
                    value="<?php echo esc_attr(
                        $service_center['account_no']
                    ); ?>"
                    <?php selected(
                        $selected_account_no,
                        $service_center['account_no']
                    ); ?>
                >
                    <?php echo esc_html(
                        $service_center_label
                    ); ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>

<?php endif; ?>
            <div class="zati-archive-filter-field">

                <label for="archive-year">Year</label>

                <select id="archive-year" name="claim_year">

                    <?php foreach ($available_years as $year) : ?>

                        <option
                            value="<?php echo esc_attr($year); ?>"
                            <?php selected($selected_year, $year); ?>
                        >
                            <?php echo esc_html($year); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="zati-archive-filter-field">

                <label for="archive-month">Month</label>

                <select id="archive-month" name="claim_month">

                    <option value="0">
                        All Months
                    </option>

                    <?php for ($month = 1; $month <= 12; $month++) : ?>

                        <option
                            value="<?php echo esc_attr($month); ?>"
                            <?php selected($selected_month, $month); ?>
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

            <button
    type="submit"
    class="zati-archive-filter-btn"
>
    FILTER
</button>

<?php if ($downloads_enabled) : ?>

    <a
        href="<?php echo esc_url(
            $attachments_download_url
        ); ?>"
        class="zati-archive-attachments-btn"
        title="Download attachments as a ZIP file."
    >
        ATTACHMENTS ZIP
    </a>

<?php else : ?>

    <span
        class="zati-archive-attachments-btn is-disabled"
        aria-disabled="true"
        title="Select a month with warranty claims first."
    >
        ATTACHMENTS ZIP
    </span>

<?php endif; ?>

<?php if ($downloads_enabled) : ?>

    <a
        href="<?php echo esc_url(
            $csv_download_url
        ); ?>"
        class="zati-archive-csv-btn"
    >
        DOWNLOAD CSV
    </a>

<?php else : ?>

    <span
        class="zati-archive-csv-btn is-disabled"
        aria-disabled="true"
        title="Select a month with warranty claims first."
    >
        DOWNLOAD CSV
    </span>

<?php endif; ?>

<?php if ($is_warranty_ts) : ?>

    <?php if ($monthly_summary_enabled) : ?>

        <a
            href="<?php echo esc_url($monthly_summary_url); ?>"
            class="zati-archive-monthly-summary-btn"
            title="Generate the Monthly Warranty Summary."
        >
            MONTHLY SUMMARY
        </a>

    <?php else : ?>

        <span
            class="zati-archive-monthly-summary-btn is-disabled"
            aria-disabled="true"
            title="Select a Service Center, Year, and Month with warranty claims first."
        >
            MONTHLY SUMMARY
        </span>

    <?php endif; ?>

<?php endif; ?>

        </form>

        <div class="zati-archive-summary">


           <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Service Center
                </span>

                <strong class="zati-archive-summary-value svcn">
                    <?php echo esc_html($service_center_summary); ?>
                </strong>

            </div>

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Total Claims
                </span>

                <strong class="zati-archive-summary-value">
                    <?php echo esc_html(number_format($total_claims)); ?>
                </strong>

            </div>

            <div class="zati-archive-summary-item">

                <span class="zati-archive-summary-label">
                    Total Payment
                </span>

                <strong class="zati-archive-summary-value is-money">
                    $<?php echo esc_html(number_format($total_payment, 2)); ?>
                </strong>

            </div>

 

        </div>

        <?php if ($claims_query->have_posts()) : ?>

            <div class="zati-archive-table-wrap">

                <table class="zati-archive-table">

                    <thead>
                        <tr>
                            <th>Reference No.</th>

				<?php if ($show_service_center_column) : ?>
                                                           <th>Account No.</th>
                                                               <?php endif; ?>

				<th>Submitted</th>      

                                 <th>Date Out</th>
                            <th>Invoice No.</th>
                            <th>Model #</th>
                            <th>Claim</th>
                            <th>Parts Total</th>
                            <th>Labor</th>
                            <th>Shipping</th>
                            <th>Total Payment</th>
                                  <th>View</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($claims_query->posts as $claim_post) : ?>

                            <?php
                            $claim_id = $claim_post->ID;

                            $reference_no = get_post_meta(
                                $claim_id,
                                'reference_no',
                                true
                            );

			$row_account_no = sanitize_text_field(
    			get_post_meta(
      			  $claim_id,
       			 'account_no',
      			  true
   			 )
			);

                            $date_out = get_post_meta(
                                $claim_id,
                                'date_out',
                                true
                            );

                            $invoice_no = get_post_meta(
                                $claim_id,
                                'invoice_no',
                                true
                            );

                            $model_no = get_post_meta(
                                $claim_id,
                                'model_no',
                                true
                            );

                            $claim_code = get_post_meta(
                                $claim_id,
                                'claim_code',
                                true
                            );

                            $parts_total = (float) get_post_meta(
                                $claim_id,
                                'parts_total',
                                true
                            );

                            $labor = (float) get_post_meta(
                                $claim_id,
                                'labor',
                                true
                            );

                            $shipping_fee_in = (float) get_post_meta(
                                $claim_id,
                                'shipping_fee_in',
                                true
                            );

                            $shipping_fee_out = (float) get_post_meta(
                                $claim_id,
                                'shipping_fee_out',
                                true
                            );

                            $shipping_total =
                                $shipping_fee_in +
                                $shipping_fee_out;

                            $claim_total = (float) get_post_meta(
                                $claim_id,
                                'total_payment',
                                true
                            );

                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $reference_no ?: '—'
                                        );
                                        ?>
                                    </strong>
                                </td>
			<?php if ($show_service_center_column) : ?>

   			 <td class="zati-archive-account-no">
      			  <strong>
          		  <?php echo esc_html(
               		 $row_account_no ?: '—'
           		 ); ?>
       			 </strong>
   			 </td>

			<?php endif; ?>
				<td>
    				 <?php echo esc_html(get_the_date('m/d/Y', $claim_id)); ?>
				</td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $format_claim_date($date_out)
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo esc_html($invoice_no ?: '—'); ?>
                                </td>

                                <td>
                                    <?php echo esc_html($model_no ?: '—'); ?>
                                </td>

                               <td>
    				<?php
   				 $claim_description = preg_replace(
      				  '/^\d{4}\s+/',
       					 '',
       				 (string) $claim_code
    					);

   				 echo esc_html($claim_description ?: '—');
    					?>
				</td>

                                <td class="zati-money">
                                    $<?php echo esc_html(number_format($parts_total, 2)); ?>
                                </td>

                                <td class="zati-money">
                                    $<?php echo esc_html(number_format($labor, 2)); ?>
                                </td>

                                <td class="zati-money">
                                    $<?php echo esc_html(number_format($shipping_total, 2)); ?>
                                </td>

                                <td class="zati-money zati-total-payment">
                                    $<?php echo esc_html(number_format($claim_total, 2)); ?>
                                </td>

                                <td>
   				<a class="zati-archive-view-link" href="<?php echo esc_url( add_query_arg('claim_id', $claim_id, home_url('/warranty-claim-detail/'))); ?>">
                                                         View </a>
                                                                   </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else : ?>

            <div class="zati-archive-empty">
                No warranty claims were found for the selected period.
            </div>

        <?php endif; ?>

    </section>

</main>

<?php
wp_reset_postdata();

get_template_part('app-footer');
get_footer('blank');
?>