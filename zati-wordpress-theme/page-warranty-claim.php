<?php
/*
Template Name: Warranty Claim Form
Template Post Type: page
*/

get_header('blank');
get_template_part('app-header');
?>

<div class="zati-subnav">

    <a
        class="zati-subnav-item"
        href="<?php echo esc_url(home_url('/svc-form/')); ?>"
    >
        Form
    </a>

    <span class="zati-subnav-item active">
        Warranty Claim Form
    </span>

</div>

<main class="zati-form-page">
  <section class="zati-form-wrap">

    <h1>Warranty Claim Form</h1>

<?php
$errors = [];
$old = [];

function zati_old($name, $default = '') {
  global $old;
  return esc_attr($old[$name] ?? $default);
}

function zati_error($name) {
  global $errors;

  if (!empty($errors[$name])) {
    echo '<div class="zati-field-error">'
      . esc_html($errors[$name])
      . '</div>';
  }
}
?>
<form
    class="zati-form"
    method="post"
    action="<?php echo esc_url(home_url('/warranty-claim-review/')); ?>"
    enctype="multipart/form-data"
>
    <?php wp_nonce_field('zati_warranty_claim', 'zati_warranty_claim_nonce'); ?>


<!-- Service Center Information -->

<section class="zati-form-section">
  <h2 class="zati-section-title">
    Service Center Information
  </h2>

  <div class="zati-form-grid">
    <div class="zati-field zati-field-wide">
      <label>Service Center Name</label>
      <input
    type="text"
    class="zati-readonly"
    name="service_center_name"
    value="<?php echo esc_attr(
        zati_get_current_service_center_name()
    ); ?>"readonly>    
      </div>

    <div class="zati-field">
      <label>Account No.</label>
     <input
    type="text"
    class="zati-readonly"
    name="account_no"
    value="<?php echo esc_attr(
        zati_get_current_service_center_account_no()
    ); ?>"readonly>
      </div>

    <div class="zati-field zati-new-row">
      <label>Invoice No <span class="required">*</span></label>
      <input type="text" name="invoice_no" value="<?php echo zati_old('invoice_no'); ?>" class="<?php echo isset($errors['invoice_no']) ? 'zati-input-error' : ''; ?>">
      <?php zati_error('invoice_no'); ?>
    </div>
  

    <div class="zati-field zati-field-wide">
      <label>Invoice Attachment</label>
      <input type="file" name="service_invoice_attachment" accept=".pdf,.jpg,.jpeg,.png" >
    <?php zati_error('service_invoice_attachment'); ?>   
      </div>
 </div>

</section>

<!-- Claim Information -->

<section class="zati-form-section">
  <h2 class="zati-section-title">
    Claim Information
  </h2>

  <div class="zati-form-grid">
    <div class="zati-field">
      <label>Model # <span class="required">*</span></label>
      <input type="text" name="model_no" value="<?php echo zati_old('model_no'); ?>" class="<?php echo isset($errors['model_no']) ? 'zati-input-error' : ''; ?>">      
             <?php zati_error('model_no'); ?>
    </div>

    <div class="zati-field">
      <label>Lot No.</label>
      <input type="text" name="lot_no" value="<?php echo zati_old('lot_no'); ?>">
    </div>

    <div class="zati-field">
      <label>Date In <span class="required">*</span></label>
      <input type="date" name="date_in" value="<?php echo zati_old('date_in'); ?>" class="<?php echo isset($errors['date_in']) ? 'zati-input-error' : ''; ?>"> 
            <?php zati_error('date_in'); ?>
    </div>

    <div class="zati-field">
      <label>Date Out <span class="required">*</span></label>
      <input type="date" name="date_out" value="<?php echo zati_old('date_out'); ?>" class="<?php echo isset($errors['date_out']) ? 'zati-input-error' : ''; ?>"> 
      <?php zati_error('date_out'); ?>
    </div>



<!-- Claim Code & Labor  -->
    <div class="zati-field">
  <label>Claim Code <span class="required">*</span></label>

  <div class="zati-select-wrap">
    <select
      name="claim_code"
      class="zati-select <?php echo isset($errors['claim_code']) ? 'zati-input-error' : ''; ?>"
    >
      <option value="">Please select</option>

      <?php foreach (zati_claim_codes() as $code) : ?>
        <option
          value="<?php echo esc_attr($code); ?>"
          <?php selected(zati_old('claim_code'), $code); ?>
        >
          <?php echo esc_html($code); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <i class="fa-solid fa-chevron-down zati-select-icon"></i>
  </div>

  <?php zati_error('claim_code'); ?>
</div>

<div class="zati-field">
  <label>
    Labor Fee
    <span class="zati-help-text">$60.00/hr default</span>
  </label>

  <input
    type="text"
    name="labor"
    value="<?php echo zati_old('labor', '$60.00'); ?>"
  >

  <small class="zati-field-note">
    Default is $60.00/hr. Edit if adjustment is needed.
  </small>
</div>

</section>

<!-- Purchase Information -->
<section class="zati-form-section">
  <h2 class="zati-section-title">
    Purchase Information
  </h2>

  <div class="zati-form-grid">
    <div class="zati-field">
      <label>Purchase Date <span class="required">*</span></label>
      <input type="date" name="purchase_date" value="<?php echo zati_old('purchase_date'); ?>" class="<?php echo isset($errors['purchase_date']) ? 'zati-input-error' : ''; ?>">
      <?php zati_error('purchase_date'); ?>
    </div>

    <div class="zati-field">
      <label>Store Name</label>
      <input type="text" name="store_name" value="<?php echo zati_old('store_name'); ?>">
    </div>

    <div class="zati-field zati-field-wide">
      <label>Purchase Receipt <span class="required">*</span></label>
      <input type="file" name="purchase_receipt_attachment" accept=".pdf,.jpg,.jpeg,.png" class="<?php echo isset($errors['purchase_receipt']) ? 'zati-input-error' : ''; ?>">
      <?php zati_error('purchase_receipt_attachment'); ?>
    </div>
  </div>
</section>

<!-- Shipping Information -->
<section class="zati-form-section">
  <h2 class="zati-section-title">
    Shipping Information
  </h2>

  <div class="zati-shipping-grid">
    <div class="zati-field">
      <label>Shipping Fee In</label>
      <input type="text" name="shipping_fee_in" value="<?php echo zati_old('shipping_fee_in'); ?>" placeholder="$0.00">
      <?php zati_error('shipping_fee_in'); ?>
    </div>

    <div class="zati-field">
      <label>Shipping In Receipt</label>
      <input type="file" name="shipping_in_receipt_attachment" accept=".pdf,.jpg,.jpeg,.png" >
      <?php zati_error('shipping_in_receipt_attachment'); ?>
    </div>

    <div class="zati-field">
      <label>Shipping Fee Out</label>
      <input type="text" name="shipping_fee_out" value="<?php echo zati_old('shipping_fee_out'); ?>" placeholder="$0.00">
      <?php zati_error('shipping_fee_out'); ?>
    </div>

    <div class="zati-field">
      <label>Shipping Out Receipt</label>
      <input type="file" name="shipping_out_receipt_attachment" accept=".pdf,.jpg,.jpeg,.png" >
      <?php zati_error('shipping_out_receipt_attachment'); ?>
    </div>
  </div>
</section>

<!-- Parts Used -->
<section class="zati-form-section">
  <h2 class="zati-section-title">
    Parts Used
  </h2>

  <div class="zati-parts-section">
    <table class="zati-parts-table zati-zac-parts-table">
      <thead>
        <tr>
          <th class="zati-col-parts">Parts</th>
                    <th class="zati-col-qty">Qty</th>
          <th class="zati-col-description">Description</th>
          <th class="zati-col-price">Unit Price</th>
                    <th class="zati-col-price">Extended Price</th>
          <th class="zati-col-actions"></th>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td>
            <div class="zati-part-lookup-cell">
              <input type="text" name="parts[0][partnumber]" class="zati-part-number" value="<?php echo esc_attr($old['parts'][0]['partnumber'] ?? ''); ?>">

	          <button type="button" class="zati-text-action zati-part-lookup">Lookup</button>
	          <button type="button" class="zati-text-action zati-part-clear">Clear</button>            
	       </div>
          </td>
 		<td>
		<input type="number"  class="zati-readonly zati-part-qty"  name="parts[0][quantity]" class="zati-part-qty" value="1" min="1" step="1">
		</td>
          <td>
            <input type="text" class="zati-readonly zati-part-description" name="parts[0][description]" value="<?php echo esc_attr($old['parts'][0]['description'] ?? ''); ?>" readonly> 
          </td>

          <td>
            <input type="text" class="zati-readonly zati-part-unit-price" name="parts[0][unit_price]" value="<?php echo esc_attr($old['parts'][0]['unit_price'] ?? ''); ?>" readonly>
          </td>
	   <td>
	<input type="text" class="zati-readonly zati-part-extended-price" name="parts[0][extended_price]" value="$0.00" readonly>
               </td>
         <td class="zati-parts-row-actions">
                       <button type="button" class="zati-row-add" aria-label="Add row">
                         <i class="fa-solid fa-plus"></i>
                       </button>

                       <button type="button" class="zati-row-remove" aria-label="Remove row" style="display:none;">
                         <i class="fa-solid fa-minus"></i>
                      </button>
                     </td>        
                  </tr>
      </tbody>
    </table>
  </div>
</section>

<!-- Claim Summary -->
<section class="zati-form-section">
  <h2 class="zati-section-title">
    Claim Summary
  </h2>

  <div class="zati-total-grid">
    <div class="zati-field">
      <label>Parts Price Total</label>
      <input type="text" name="parts_price_total" value="$0.00" readonly>
    </div>

    <div class="zati-field">
      <label>Labor</label>
      <input type="text" name="labor_total" value="$60.00" readonly>
    </div>

    <div class="zati-field">
      <label>Stocking Fee</label>
      <input type="text" name="stocking_fee_total" value="$0.00" readonly>
    </div>

    <div class="zati-field">
      <label>Shipping Fee IN</label>
      <input type="text" name="shipping_fee_in_total" value="$0.00" readonly>
    </div>

    <div class="zati-field">
      <label>Shipping Fee Out</label>
      <input type="text" name="shipping_fee_out_total" value="$0.00" readonly>
    </div>

    <div class="zati-field">
      <label>Total Payment</label>
      <input type="text" name="total_payment" value="$60.00" readonly>
    </div>
  </div>
</section>

<div class="zati-form-actions">
  <button type="submit" class="zati-primary-btn">NEXT</button>
</div>

</form>

  </section>
</main>

<?php
get_template_part('app-footer');
get_footer('blank');
?>