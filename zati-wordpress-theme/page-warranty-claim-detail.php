<?php
/*
Template Name: Warranty Claim Detail
Template Post Type: page
*/

/* =========================
   Login Check
========================= */

if (!is_user_logged_in()) {
    auth_redirect();
}

/* =========================
   Claim ID
========================= */

$claim_id = isset($_GET['claim_id'])
    ? absint($_GET['claim_id'])
    : 0;

if (
    !$claim_id ||
    get_post_type($claim_id) !== 'warranty_claim' ||
    get_post_status($claim_id) !== 'publish'
) {
    wp_safe_redirect(
        add_query_arg(
            'claim_notice',
            'not_found',
            home_url('/warranty-claim-archive/')
        )
    );
    exit;
}

/* =========================
   View Permission
========================= */

if (!zati_current_user_can_view_warranty_claim($claim_id)) {
    wp_safe_redirect(
        add_query_arg(
            'claim_notice',
            'not_found',
            home_url('/warranty-claim-archive/')
        )
    );
    exit;
}

/* =========================
   Claim Data
========================= */

$reference_no  = get_post_meta($claim_id, 'reference_no', true);
$status        = get_post_meta($claim_id, 'status', true);
$service_center = get_post_meta($claim_id, 'service_center', true);
$account_no    = get_post_meta($claim_id, 'account_no', true);
$invoice_no    = get_post_meta($claim_id, 'invoice_no', true);
$model_no      = get_post_meta($claim_id, 'model_no', true);
$claim_code    = get_post_meta($claim_id, 'claim_code', true);
$date_in       = get_post_meta($claim_id, 'date_in', true);
$date_out      = get_post_meta($claim_id, 'date_out', true);
$purchase_date = get_post_meta($claim_id, 'purchase_date', true);
$store_name    = get_post_meta($claim_id, 'store_name', true);

$labor            = (float) get_post_meta($claim_id, 'labor', true);
$parts_total       = (float) get_post_meta($claim_id, 'parts_total', true);
$shipping_fee_in   = (float) get_post_meta($claim_id, 'shipping_fee_in', true);
$shipping_fee_out  = (float) get_post_meta($claim_id, 'shipping_fee_out', true);
$stocking_fee      = (float) get_post_meta($claim_id, 'stocking_fee', true);
$total_payment     = (float) get_post_meta($claim_id, 'total_payment', true);

$shipping_total = $shipping_fee_in + $shipping_fee_out;
/* =========================
   Attachments
========================= */

$purchase_receipt_id     = (int) get_field('purchase_receipt_attachment', $claim_id);
$service_invoice_id      = (int) get_field('service_invoice_attachment', $claim_id);
$shipping_in_receipt_id  = (int) get_field('shipping_in_receipt_attachment', $claim_id);
$shipping_out_receipt_id = (int) get_field('shipping_out_receipt_attachment', $claim_id);

$purchase_receipt_url = $purchase_receipt_id
    ? wp_get_attachment_url($purchase_receipt_id)
    : '';

$service_invoice_url = $service_invoice_id
    ? wp_get_attachment_url($service_invoice_id)
    : '';

$shipping_in_receipt_url = $shipping_in_receipt_id
    ? wp_get_attachment_url($shipping_in_receipt_id)
    : '';

$shipping_out_receipt_url = $shipping_out_receipt_id
    ? wp_get_attachment_url($shipping_out_receipt_id)
    : '';



/* =========================
   Parts JSON
========================= */

$parts_json = get_post_meta($claim_id, 'parts_json', true);
$parts      = json_decode($parts_json, true);

if (!is_array($parts)) {
    $parts = [];
}

/* =========================
   Date Formatter
========================= */

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

    <a
        class="zati-subnav-item"
        href="<?php echo esc_url(
            home_url('/warranty-claim-archive/')
        ); ?>"
    >
        Warranty Claim Archive
    </a>

    <span class="zati-subnav-item active">
        Warranty Claim Detail
    </span>

</div>

<main class="zati-review-page zati-claim-detail-page">

    <div class="zati-detail-header">

        <div>
            <h1>Warranty Claim Detail</h1>

            <p class="zati-detail-reference">
                <?php echo esc_html($reference_no ?: '—'); ?>
            </p>
        </div>

        <span class="zati-archive-status">
            <?php echo esc_html($status ?: 'Submitted'); ?>
        </span>

    </div>

    <div class="zati-review-header">

        <div>
            <span>Service Center Name</span>
            <strong>
                <?php echo esc_html($service_center ?: '—'); ?>
            </strong>
        </div>

        <div>
            <span>Service Center Account No.</span>
            <strong>
                <?php echo esc_html($account_no ?: '—'); ?>
            </strong>
        </div>

    </div>

    <hr>

    <div class="zati-review-layout">

        <div class="zati-review-main">

            <div class="zati-review-columns">

                <section class="zati-review-section">

                    <h2>Claim Information</h2>

                    <dl>
                        <dt>Invoice No.</dt>
                        <dd><?php echo esc_html($invoice_no ?: '—'); ?></dd>
	

                        <dt>Claim Code</dt>
                        <dd><?php echo esc_html($claim_code ?: '—'); ?></dd>

                        <dt>Date In</dt>
                        <dd><?php echo esc_html($format_claim_date($date_in)); ?></dd>

                        <dt>Date Out</dt>
                        <dd><?php echo esc_html($format_claim_date($date_out)); ?></dd>
		<dt>Invoice Attachment</dt>
	<dd>
   	 <?php if ($service_invoice_url !== '') : ?>
        <a
            class="zati-attached"
            href="<?php echo esc_url($service_invoice_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            Attached
        </a>
   	 <?php else : ?>
        —
   	 <?php endif; ?>
	</dd> 
         </dl>

                </section>

                <section class="zati-review-section">

                    <h2>Product Information</h2>

                    <dl>
                        <dt>Model #</dt>
                        <dd><?php echo esc_html($model_no ?: '—'); ?></dd>

                        <dt>Purchase Date</dt>
                        <dd>
                            <?php echo esc_html($format_claim_date($purchase_date)); ?>
                        </dd>

                        <dt>Store Name</dt>
                        <dd><?php echo esc_html($store_name ?: '—'); ?></dd>

                        <dt>Purchase Receipt</dt>
	<dd>
    	<?php if ($purchase_receipt_url !== '') : ?>
       	 <a
            class="zati-attached"
            href="<?php echo esc_url($purchase_receipt_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
       	 >
        	    Attached
        	</a>
   	 <?php else : ?>
     	   —
    	<?php endif; ?>
	</dd>
       </dl>

                </section>

            </div>

            <section class="zati-review-parts">

                <h2>Parts Used</h2>

                <?php if (!empty($parts)) : ?>

                    <table>

                        <thead>
                            <tr>
                                <th>Parts</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Extended Price</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($parts as $part) : ?>

                                <tr>
                                    <td>
                                        <?php
                                        echo esc_html(
                                            $part['partnumber'] ?? '—'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            $part['description'] ?? '—'
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo esc_html(
                                            (int) ($part['quantity'] ?? 0)
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        $<?php
                                        echo esc_html(
                                            number_format(
                                                (float) ($part['unit_price'] ?? 0),
                                                2
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        $<?php
                                        echo esc_html(
                                            number_format(
                                                (float) ($part['extended_price'] ?? 0),
                                                2
                                            )
                                        );
                                        ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else : ?>

                    <p class="zati-detail-empty">
                        No parts were recorded for this claim.
                    </p>

                <?php endif; ?>

            </section>

        </div>

        <aside class="zati-claim-summary">

            <h2>Claim Summary</h2>

            <dl>
                <dt>Labor</dt>
                <dd>$<?php echo esc_html(number_format($labor, 2)); ?></dd>

                <dt>Shipping Fee In</dt>
                <dd>
                    $<?php echo esc_html(number_format($shipping_fee_in, 2)); ?>
                </dd>

                <dt>Shipping Fee Out</dt>
                <dd>
                    $<?php echo esc_html(number_format($shipping_fee_out, 2)); ?>
                </dd>

                <dt>Shipping Total</dt>
                <dd>
                    $<?php echo esc_html(number_format($shipping_total, 2)); ?>
                </dd>
<dt>Shipping In Receipt</dt>
<dd>
    <?php if ($shipping_in_receipt_url !== '') : ?>
        <a
            class="zati-attached"
            href="<?php echo esc_url($shipping_in_receipt_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            Attached
        </a>
    <?php else : ?>
        —
    <?php endif; ?>
</dd>

<dt>Shipping Out Receipt</dt>
<dd>
    <?php if ($shipping_out_receipt_url !== '') : ?>
        <a
            class="zati-attached"
            href="<?php echo esc_url($shipping_out_receipt_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            Attached
        </a>
    <?php else : ?>
        —
    <?php endif; ?>
</dd>
                <dt>Stocking Fee</dt>
                <dd>
                    $<?php echo esc_html(number_format($stocking_fee, 2)); ?>
                </dd>

                <dt>Parts Price Total</dt>
                <dd>
                    <strong>
                        $<?php echo esc_html(number_format($parts_total, 2)); ?>
                    </strong>
                </dd>
            </dl>

            <hr>

            <div class="zati-summary-total">
                <span>Total Payment</span>

                <strong>
                    $<?php echo esc_html(number_format($total_payment, 2)); ?>
                </strong>
            </div>

        </aside>

    </div>

    <div class="zati-detail-actions">

        <a
            class="zati-secondary-btn"
            href="<?php echo esc_url(home_url('/warranty-claim-archive/')); ?>"
        >
            BACK TO ARCHIVE
        </a>

    </div>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');
?>