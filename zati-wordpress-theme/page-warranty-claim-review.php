<?php
/*
Template Name: Warranty Claim Review
Template Post Type: page
*/

get_header('blank');
get_template_part('app-header');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  wp_redirect(home_url('/warranty-claim/'));
  exit;
}

if (
  !isset($_POST['zati_warranty_claim_nonce']) ||
  !wp_verify_nonce($_POST['zati_warranty_claim_nonce'], 'zati_warranty_claim')
) {
  wp_die('Invalid request.');
}

$errors = zati_validate_warranty_claim(wp_unslash($_POST), $_FILES);

if (!empty($errors)) {
  ?>
  <main class="zati-form-page">
    <section class="zati-form-wrap">
      <h1>Warranty Claim Error</h1>

      <div class="zati-error-box">
        Please fix the errors below and submit again.
      </div>

      <ul class="zati-error-list">
        <?php foreach ($errors as $error) : ?>
          <li><?php echo esc_html($error); ?></li>
        <?php endforeach; ?>
      </ul>

      <div class="zati-form-actions">
        <button type="button" class="zati-secondary-btn" onclick="history.back();">
          BACK TO EDIT
        </button>
      </div>
    </section>
  </main>
  <?php
  get_template_part('app-footer');
  get_footer('blank');
  exit;
}

/* =========================
   Temporary Upload
========================= */

$service_invoice_temp = zati_handle_temp_upload(
    'service_invoice_attachment'
);

$purchase_receipt_temp = zati_handle_temp_upload(
    'purchase_receipt_attachment'
);

$shipping_in_receipt_temp = zati_handle_temp_upload(
    'shipping_in_receipt_attachment'
);

$shipping_out_receipt_temp = zati_handle_temp_upload(
    'shipping_out_receipt_attachment'
);


/* =========================
   Store Temporary File Data
========================= */

$temp_uploads = [
    'service_invoice_attachment'      => $service_invoice_temp,
    'purchase_receipt_attachment'     => $purchase_receipt_temp,
    'shipping_in_receipt_attachment'  => $shipping_in_receipt_temp,
    'shipping_out_receipt_attachment' => $shipping_out_receipt_temp,
];

/* アップロードされていない空データを除外 */
$temp_uploads = array_filter(
    $temp_uploads,
    static function ($upload) {
        return is_array($upload) && !empty($upload['file']);
    }
);

$temp_upload_token = wp_generate_uuid4();

set_transient(
    'zati_warranty_upload_' . $temp_upload_token,
    $temp_uploads,
    30 * MINUTE_IN_SECONDS
);





function zati_post_value($key, $default = '') {
  return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : $default;
}

$service_center_name = zati_post_value('service_center_name');
$account_no          = zati_post_value('account_no');
$invoice_no          = zati_post_value('invoice_no');
$date_in             = zati_post_value('date_in');
$date_out            = zati_post_value('date_out');
$claim_code          = zati_post_value('claim_code');
$model_no            = zati_post_value('model_no');
$lot_no              = zati_post_value('lot_no');
$purchase_date       = zati_post_value('purchase_date');
$store_name          = zati_post_value('store_name');

$labor_total         = zati_post_value('labor_total', '$60.00');
$parts_price_total   = zati_post_value('parts_price_total', '$0.00');
$stocking_fee_total  = zati_post_value('stocking_fee_total', '$0.00');
$shipping_fee_in     = zati_post_value('shipping_fee_in_total', '$0.00');
$shipping_fee_out    = zati_post_value('shipping_fee_out_total', '$0.00');
$total_payment       = zati_post_value('total_payment', '$60.00');

$parts = isset($_POST['parts']) && is_array($_POST['parts']) ? $_POST['parts'] : [];

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
        Warranty Claim Review
    </span>

</div>


<main class="zati-form-page zati-review-page">

    <h1>Warranty Claim Review</h1>

    <div class="zati-review-header">
      <div>
        <span>Service Center Name</span>
        <strong><?php echo esc_html($service_center_name); ?></strong>
      </div>
      <div>
        <span>Service Center Account No</span>
        <strong><?php echo esc_html($account_no); ?></strong>
      </div>
    </div>

    <p class="zati-review-note">
      Purchase receipt is required for warranty claims.<br>
      Enter Shipping Fee Out only when a receipt is attached.<br>
      All amounts are in USD.
    </p>

    <hr>

    <div class="zati-review-layout">

      <div class="zati-review-main">

        <div class="zati-review-columns">

          <section class="zati-review-section">
            <h2>Claim Information</h2>

            <dl>
              <dt>Invoice No</dt>
              <dd><?php echo esc_html($invoice_no); ?></dd>

              <dt>Claim code</dt>
              <dd><?php echo esc_html($claim_code); ?></dd>

              <dt>Date In</dt>
              <dd><?php echo esc_html($date_in); ?></dd>

              <dt>Date Out</dt>
              <dd><?php echo esc_html($date_out); ?></dd>

	<dt>Invoice Attachment</dt>
	<dd>
    	<?php if (!empty($service_invoice_temp['url'])) : ?>
        <a
            class="zati-attached"
            href="<?php echo esc_url($service_invoice_temp['url']); ?>"
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
              <dd><?php echo esc_html($model_no); ?></dd>

              <dt>Lot No.</dt>
              <dd><?php echo esc_html($lot_no); ?></dd>

              <dt>Purchase Date</dt>
              <dd><?php echo esc_html($purchase_date); ?></dd>

              <dt>Store Name</dt>
              <dd><?php echo esc_html($store_name); ?></dd>

      <dt>Purchase Receipt</dt>
	<dd>
   	 <?php if (!empty($purchase_receipt_temp['url'])) : ?>
       	 <a
            class="zati-attached"
            href="<?php echo esc_url($purchase_receipt_temp['url']); ?>"
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

        <section class="zati-review-section zati-review-parts">
          <h2>Parts Used</h2>

          <table>
            <thead>
              <tr>
                <th>Parts</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Extended Price</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($parts)) : ?>
                <?php foreach ($parts as $part) : ?>
                  <?php
                    $partnumber     = isset($part['partnumber']) ? sanitize_text_field(wp_unslash($part['partnumber'])) : '';
                    $quantity       = isset($part['quantity']) ? sanitize_text_field(wp_unslash($part['quantity'])) : '';
                    $unit_price     = isset($part['unit_price']) ? sanitize_text_field(wp_unslash($part['unit_price'])) : '';
                    $extended_price = isset($part['extended_price']) ? sanitize_text_field(wp_unslash($part['extended_price'])) : '';

                    if ($partnumber === '') {
                      continue;
                    }
                  ?>
                  <tr>
                    <td><?php echo esc_html($partnumber); ?></td>
                    <td><?php echo esc_html($quantity); ?></td>
                    <td><?php echo esc_html($unit_price); ?></td>
                    <td><?php echo esc_html($extended_price); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="4">No parts entered.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </section>

      </div>

      <aside class="zati-claim-summary">
        <h2>Claim Summary</h2>

        <dl>
          <dt>Labor</dt>
          <dd><?php echo esc_html($labor_total); ?></dd>

          <dt>Shipping Fee In</dt>
          <dd><?php echo esc_html($shipping_fee_in); ?></dd>

          <dt>Shipping Fee Out</dt>
          <dd><?php echo esc_html($shipping_fee_out); ?></dd>

          <dt>Shipping In Receipt</dt>
       <dd>
    <?php if (!empty($shipping_in_receipt_temp['url'])) : ?>
        <a
            class="zati-attached"
            href="<?php echo esc_url($shipping_in_receipt_temp['url']); ?>"
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
    <?php if (!empty($shipping_out_receipt_temp['url'])) : ?>
        <a
            class="zati-attached"
            href="<?php echo esc_url($shipping_out_receipt_temp['url']); ?>"
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
          <dd><?php echo esc_html($stocking_fee_total); ?></dd>

          <dt>Parts Price Total</dt>
          <dd><strong><?php echo esc_html($parts_price_total); ?></strong></dd>
        </dl>

        <hr>

        <div class="zati-summary-total">
          <span>Total Payment</span>
          <strong><?php echo esc_html($total_payment); ?></strong>
        </div>
      </aside>

    </div>

    
<form
    method="post"
    action="<?php echo esc_url(home_url('/warranty-claim-complete/')); ?>"
>
    <?php
    wp_nonce_field(
        'zati_warranty_claim_submit',
        'zati_warranty_claim_submit_nonce'
    );
    ?>

    <?php zati_render_hidden_fields($_POST); ?>

    <input
        type="hidden"
        name="zati_temp_upload_token"
        value="<?php echo esc_attr($temp_upload_token); ?>"
    >

    <div class="zati-form-actions zati-review-actions">

        <button
            type="button"
            class="zati-secondary-btn bte"
            onclick="history.back();"
        >
            BACK TO EDIT
        </button>

        <button
            type="submit"
            class="zati-primary-btn"
        >
            SUBMIT
        </button>

    </div>

</form>

  </section>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');
?>