<?php
/*
Template Name: Parts Order
Template Post Type: page
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

get_header('blank');
get_template_part('app-header');


/* =========================
   Current Service Center Data
========================= */

$current_user_id = get_current_user_id();

$service_center_name = function_exists(
    'zati_get_current_service_center_name'
)
    ? zati_get_current_service_center_name()
    : '';

$account_no = function_exists(
    'zati_get_current_service_center_account_no'
)
    ? zati_get_current_service_center_account_no()
    : '';

$phone = function_exists(
    'zati_get_current_service_center_phone'
)
    ? zati_get_current_service_center_phone()
    : '';

$default_ship_via = function_exists(
    'zati_get_current_service_center_default_ship_via'
)
    ? zati_get_current_service_center_default_ship_via()
    : '';

$terms = function_exists(
    'zati_get_current_service_center_terms'
)
    ? zati_get_current_service_center_terms()
    : '';

$sold_to = function_exists(
    'zati_get_current_service_center_sold_to'
)
    ? zati_get_current_service_center_sold_to()
    : [];

$registered_ship_to = function_exists(
    'zati_get_current_service_center_ship_to'
)
    ? zati_get_current_service_center_ship_to()
    : [];

$has_registered_ship_to = function_exists(
    'zati_service_center_has_default_ship_to'
)
    ? zati_service_center_has_default_ship_to(
        $current_user_id
    )
    : false;


/* =========================
   Address Defaults
========================= */

$address_defaults = [
    'name'     => '',
    'address1' => '',
    'address2' => '',
    'city'     => '',
    'state'    => '',
    'zip'      => '',
    'country'  => '',
];

$sold_to = wp_parse_args(
    $sold_to,
    $address_defaults
);

$registered_ship_to = wp_parse_args(
    $registered_ship_to,
    $address_defaults
);

$has_registered_sold_to = (
    $sold_to['name'] !== ''
    || $sold_to['address1'] !== ''
    || $sold_to['city'] !== ''
    || $sold_to['state'] !== ''
    || $sold_to['zip'] !== ''
);

$use_registered_addresses = (
    $has_registered_sold_to
    || $has_registered_ship_to
);


/* =========================
   Country Display Helper
========================= */

$country_name = static function ($country_code) {

    $countries = [
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
    ];

    return $countries[$country_code]
        ?? $country_code;
};


/* =========================
   City / State / ZIP Lines
========================= */

$sold_to_city_line = trim(
    implode(
        ', ',
        array_filter(
            [
                $sold_to['city'],
                trim(
                    $sold_to['state']
                    . ' '
                    . $sold_to['zip']
                ),
            ]
        )
    )
);

$ship_to_city_line = trim(
    implode(
        ', ',
        array_filter(
            [
                $registered_ship_to['city'],
                trim(
                    $registered_ship_to['state']
                    . ' '
                    . $registered_ship_to['zip']
                ),
            ]
        )
    )
);
?>

<div class="zati-subnav">

    <a
        class="zati-subnav-item"
        href="<?php echo esc_url(home_url('/svc-form/')); ?>"
    >
        Form
    </a>

    <span class="zati-subnav-item active">
        Parts Order
    </span>

</div>

<main class="zati-form-page">

    <section class="zati-form-wrap">
        <!-- =========================
             Page Header
        ========================== -->

        <div class="zati-form-header">

            <h1 class="zati-form-title">
                Parts Order
            </h1>

        </div>


        <form
    	id="zati-parts-order-form"
   	 method="post"
    	action="<?php
        echo esc_url(
            home_url('/parts-order-review/')
     	   );
   	 ?>"
    	novalidate
	>
            <?php
            wp_nonce_field(
                'zati_parts_order_review',
                'zati_parts_order_nonce'
            );
            ?>


            <!-- =========================
                 Service Center Information
            ========================== -->

            <section class="zati-form-section">

                <h2 class="zati-section-title">
                    Service Center Information
                </h2>

                <div class="zati-form-row-3">

                    <div class="zati-field">

                        <label for="service_center_name">
                            Service Center Name
                        </label>

                        <input
                            type="text"
                            id="service_center_name"
                            name="service_center_name"
                            value="<?php
                            echo esc_attr(
                                $service_center_name
                            );
                            ?>"
                            class="zati-readonly"
                            readonly
                        >

                    </div>


                    <div class="zati-field">

                        <label for="account_no">
                            Account No.
                        </label>

                        <input
                            type="text"
                            id="account_no"
                            name="account_no"
                            value="<?php
                            echo esc_attr(
                                $account_no
                            );
                            ?>"
                            class="zati-readonly"
                            readonly
                        >

                    </div>


                    <div class="zati-field">

                        <label for="phone">
                            Phone No.
                        </label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?php
                            echo esc_attr(
                                $phone
                            );
                            ?>"
                            class="zati-readonly"
                            readonly
                        >

                    </div>

                </div>


                <div class="zati-parts-order-meta-row">

    <div class="zati-field">

        <label for="customer_po_no">
            Customer PO No.
            <span class="zati-required">*</span>
        </label>

        <input
            type="text"
            id="customer_po_no"
            name="customer_po_no"
            required
        >

    </div>

    <div class="zati-field">

        <label for="order_date">
            Order Date
        </label>

        <input
            type="date"
            id="order_date"
            name="order_date"
            value="<?php echo esc_attr(wp_date('Y-m-d')); ?>"
            class="zati-readonly"
            readonly
        >

    </div>

</div>
            </section>


 <!-- =========================
     Address Information
========================== -->

<section class="zati-form-section">

    <h2 class="zati-section-title">
        Address Information
    </h2>

    <div class="zati-address-compare-grid">


        <!-- =========================
             Sold To
        ========================== -->

        <div class="zati-address-column">

            <h3 class="zati-address-column-title">
                Sold To
            </h3>

            <div class="zati-address-control-area">

                <label class="zati-address-option">

                    <input
                        type="radio"
                        name="sold_to_mode"
                        value="registered"
                        checked
                    >

                    <span>
                        Registered
                    </span>

                </label>

                <label class="zati-address-option">

                    <input
                        type="radio"
                        name="sold_to_mode"
                        value="different"
                    >

                    <span>
                        Different Address
                    </span>

                </label>

            </div>


            <!-- Registered Sold To -->

            <div
                id="registered-sold-to-panel"
                class="zati-address-panel"
            >

                <div class="zati-address-display">

                    <?php if ($sold_to['name'] !== '') : ?>
                        <strong>
                            <?php echo esc_html($sold_to['name']); ?>
                        </strong>
                    <?php endif; ?>

                    <?php if ($sold_to['address1'] !== '') : ?>
                        <div>
                            <?php echo esc_html($sold_to['address1']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sold_to['address2'] !== '') : ?>
                        <div>
                            <?php echo esc_html($sold_to['address2']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sold_to_city_line !== '') : ?>
                        <div>
                            <?php echo esc_html($sold_to_city_line); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sold_to['country'] !== '') : ?>
                        <div>
                            <?php
                            echo esc_html(
                                $country_name($sold_to['country'])
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>


            <!-- Different Sold To -->

            <div
                id="different-sold-to-panel"
                class="zati-address-panel"
                hidden
            >

                <div class="zati-address-edit-grid">

                    <div class="zati-field zati-field--full">

                        <label for="different_sold_to_name">
                            Company / Recipient Name
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_sold_to_name"
                            name="different_sold_to_name"
                        >

                    </div>

                    <div class="zati-field zati-field--full">

                        <label for="different_sold_to_address1">
                            Address Line 1
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_sold_to_address1"
                            name="different_sold_to_address1"
                        >

                    </div>

                    <div class="zati-field zati-field--full">

                        <label for="different_sold_to_address2">
                            Address Line 2
                        </label>

                        <input
                            type="text"
                            id="different_sold_to_address2"
                            name="different_sold_to_address2"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_sold_to_city">
                            City
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_sold_to_city"
                            name="different_sold_to_city"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_sold_to_state">
                            State / Province
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_sold_to_state"
                            name="different_sold_to_state"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_sold_to_zip">
                            ZIP / Postal Code
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_sold_to_zip"
                            name="different_sold_to_zip"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_sold_to_country">
                            Country
                            <span class="zati-required">*</span>
                        </label>

                        <div class="zati-select-wrap">

                            <select
                                id="different_sold_to_country"
                                name="different_sold_to_country"
                                class="zati-select"
                            >
                                <option value="">
                                    Select Country
                                </option>

                                <option value="US">
                                    United States
                                </option>

                                <option value="CA">
                                    Canada
                                </option>

                                <option value="MX">
                                    Mexico
                                </option>
                            </select>

                            <span
                                class="zati-select-icon"
                                aria-hidden="true"
                            >
                                ▼
                            </span>

                        </div>

                    </div>

                </div>

                <label class="zati-address-save-option">

                    <input
                        type="checkbox"
                        name="update_registered_sold_to"
                        value="1"
                    >

                    <span>
                        Save as registered Sold To
                    </span>

                </label>

            </div>

        </div>


        <!-- =========================
             Ship To
        ========================== -->

        <div class="zati-address-column">

            <h3 class="zati-address-column-title">
                Ship To
            </h3>

            <div class="zati-address-control-area">

                <div class="zati-address-control-row">

                    <label class="zati-address-option">

                        <input
                            type="radio"
                            name="ship_to_mode"
                            value="registered"
                            checked
                        >

                        <span>
                            Registered
                        </span>

                    </label>

                    <label class="zati-address-option">

                        <input
                            type="radio"
                            name="ship_to_mode"
                            value="different"
                        >

                        <span>
                            Different Address
                        </span>

                    </label>

                </div>

                <label class="zati-same-address-option">

                    <input
                        type="checkbox"
                        id="same-as-sold-to"
                        name="ship_same_as_sold"
                        value="1"
                    >

                    <span>
                        Same as Sold To
                    </span>

                </label>

            </div>


            <!-- Registered Ship To -->

            <div
                id="registered-ship-to-panel"
                class="zati-address-panel"
            >

                <div class="zati-address-display">

                    <?php if (
                        $registered_ship_to['name'] !== ''
                    ) : ?>
                        <strong>
                            <?php
                            echo esc_html(
                                $registered_ship_to['name']
                            );
                            ?>
                        </strong>
                    <?php endif; ?>

                    <?php if (
                        $registered_ship_to['address1'] !== ''
                    ) : ?>
                        <div>
                            <?php
                            echo esc_html(
                                $registered_ship_to['address1']
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $registered_ship_to['address2'] !== ''
                    ) : ?>
                        <div>
                            <?php
                            echo esc_html(
                                $registered_ship_to['address2']
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($ship_to_city_line !== '') : ?>
                        <div>
                            <?php echo esc_html($ship_to_city_line); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (
                        $registered_ship_to['country'] !== ''
                    ) : ?>
                        <div>
                            <?php
                            echo esc_html(
                                $country_name(
                                    $registered_ship_to['country']
                                )
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>


            <!-- Sold To Copy -->

            <div
                id="sold-to-copy-panel"
                class="zati-address-panel"
                hidden
            >

                <div class="zati-address-display">

                    <?php if ($sold_to['name'] !== '') : ?>
                        <strong>
                            <?php echo esc_html($sold_to['name']); ?>
                        </strong>
                    <?php endif; ?>

                    <?php if ($sold_to['address1'] !== '') : ?>
                        <div>
                            <?php echo esc_html($sold_to['address1']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sold_to['address2'] !== '') : ?>
                        <div>
                            <?php echo esc_html($sold_to['address2']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sold_to_city_line !== '') : ?>
                        <div>
                            <?php echo esc_html($sold_to_city_line); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sold_to['country'] !== '') : ?>
                        <div>
                            <?php
                            echo esc_html(
                                $country_name($sold_to['country'])
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>


            <!-- Different Ship To -->

            <div
                id="different-ship-to-panel"
                class="zati-address-panel"
                hidden
            >

                <div class="zati-address-edit-grid">

                    <div class="zati-field zati-field--full">

                        <label for="different_ship_to_name">
                            Company / Recipient Name
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_ship_to_name"
                            name="different_ship_to_name"
                        >

                    </div>

                    <div class="zati-field zati-field--full">

                        <label for="different_ship_to_address1">
                            Address Line 1
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_ship_to_address1"
                            name="different_ship_to_address1"
                        >

                    </div>

                    <div class="zati-field zati-field--full">

                        <label for="different_ship_to_address2">
                            Address Line 2
                        </label>

                        <input
                            type="text"
                            id="different_ship_to_address2"
                            name="different_ship_to_address2"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_ship_to_city">
                            City
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_ship_to_city"
                            name="different_ship_to_city"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_ship_to_state">
                            State / Province
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_ship_to_state"
                            name="different_ship_to_state"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_ship_to_zip">
                            ZIP / Postal Code
                            <span class="zati-required">*</span>
                        </label>

                        <input
                            type="text"
                            id="different_ship_to_zip"
                            name="different_ship_to_zip"
                        >

                    </div>

                    <div class="zati-field">

                        <label for="different_ship_to_country">
                            Country
                            <span class="zati-required">*</span>
                        </label>

                        <div class="zati-select-wrap">

                            <select
                                id="different_ship_to_country"
                                name="different_ship_to_country"
                                class="zati-select"
                            >
                                <option value="">
                                    Select Country
                                </option>

                                <option value="US">
                                    United States
                                </option>

                                <option value="CA">
                                    Canada
                                </option>

                                <option value="MX">
                                    Mexico
                                </option>
                            </select>

                            <span
                                class="zati-select-icon"
                                aria-hidden="true"
                            >
                                ▼
                            </span>

                        </div>

                    </div>

                </div>

                <label class="zati-address-save-option">

                    <input
                        type="checkbox"
                        name="update_registered_ship_to"
                        value="1"
                    >

                    <span>
                        Save as registered Ship To
                    </span>

                </label>

            </div>

        </div>

    </div>


    <?php foreach ($sold_to as $field => $value) : ?>

        <input
            type="hidden"
            name="registered_sold_to_<?php
            echo esc_attr($field);
            ?>"
            value="<?php echo esc_attr($value); ?>"
        >

    <?php endforeach; ?>


    <?php foreach (
        $registered_ship_to as $field => $value
    ) : ?>

        <input
            type="hidden"
            name="registered_ship_to_<?php
            echo esc_attr($field);
            ?>"
            value="<?php echo esc_attr($value); ?>"
        >

    <?php endforeach; ?>

</section>


            <!-- =========================
                 Shipping Request
            ========================== -->

            <section class="zati-form-section">

                <h2 class="zati-section-title">
                    Shipping Request
                </h2>

                

<div class="zati-parts-order-shipping-row">

    <div class="zati-field">

        <label for="requested_ship_via">
            Requested Ship Via
        </label>

        <div class="zati-select-wrap">

            <select
                id="requested_ship_via"
                name="requested_ship_via"
                class="zati-select"
            >

                <option value="">
                    Select
                </option>

                <option
                    value="UPS Ground"
                    <?php selected(
                        $default_ship_via,
                        'UPS Ground'
                    ); ?>
                >
                    UPS Ground
                </option>

                <option
                    value="UPS 2nd Day"
                    <?php selected(
                        $default_ship_via,
                        'UPS 2nd Day'
                    ); ?>
                >
                    UPS 2nd Day
                </option>

                <option
                    value="UPS Next Day"
                    <?php selected(
                        $default_ship_via,
                        'UPS Next Day'
                    ); ?>
                >
                    UPS Next Day
                </option>

                <option
                    value="FedEx Ground"
                    <?php selected(
                        $default_ship_via,
                        'FedEx Ground'
                    ); ?>
                >
                    FedEx Ground
                </option>

                <option
                    value="Customer UPS Account"
                    <?php selected(
                        $default_ship_via,
                        'Customer UPS Account'
                    ); ?>
                >
                    Customer UPS Account
                </option>

                <option
                    value="Customer FedEx Account"
                    <?php selected(
                        $default_ship_via,
                        'Customer FedEx Account'
                    ); ?>
                >
                    Customer FedEx Account
                </option>

                <option
                    value="Other"
                    <?php selected(
                        $default_ship_via,
                        'Other'
                    ); ?>
                >
                    Other
                </option>

            </select>

            <span
                class="zati-select-icon"
                aria-hidden="true"
            >
                ▼
            </span>

        </div>

    </div>


    <div class="zati-field">

        <label for="requested_ship_date">
            Ship Date
        </label>

        <input
            type="text"
            id="requested_ship_date"
            name="requested_ship_date"
            value="ASAP"
            class="zati-readonly"
            readonly
        >

    </div>


    <div class="zati-field">

        <label for="terms">
            Terms
        </label>

        <input
            type="text"
            id="terms"
            name="terms"
            value="<?php echo esc_attr($terms); ?>"
            class="zati-readonly"
            placeholder="Assigned by ZAC"
            readonly
        >

    </div>


    <div class="zati-field">

        <label for="remarks">
            Remarks
         </label>

         <input
             type="text"
             id="remarks"
             name="remarks"
             maxlength="200"
         >

      </div>

    </div>
                
            </section>


            <!-- =========================
     Parts
========================== -->

<section class="zati-form-section">

   <div class="zati-parts-section-header">
     <h2 class="zati-section-title">
        Parts
    </h2>
        <a
            class="zati-add-parts-from-model"
            href="<?php
            echo esc_url(
                home_url('/model-search/')
            );
            ?>"
        >
            ADD PARTS FROM MODEL
        </a>

    </div>

    <div class="zati-parts-section">

        <table
            class="zati-parts-table zati-zac-parts-table"
            id="zati-parts-order-table"
        >

            <thead>
                <tr>
                    <th class="zati-col-parts">
                        Parts
                    </th>

                    <th class="zati-col-qty">
                        Qty
                    </th>

                    <th class="zati-col-description">
                        Description
                    </th>

                    <th class="zati-col-price">
                        Unit Price
                    </th>

                    <th class="zati-col-price">
                        Extended Price
                    </th>

                    <th class="zati-col-actions"></th>
                </tr>
            </thead>

            <tbody id="zati-parts-order-body">

                <tr class="zati-part-row">

                    <td>

                        <div class="zati-part-lookup-cell">

                            <input
                                type="text"
                                name="parts[0][partnumber]"
                                class="zati-part-number"
                                autocomplete="off"
                            >

                            <button
                                type="button"
                                class="zati-text-action zati-part-lookup"
                            >
                                Lookup
                            </button>

                            <button
                                type="button"
                                class="zati-text-action zati-part-clear"
                            >
                                Clear
                            </button>

                        </div>
                        <div
    class="zati-part-error"
    hidden
    aria-live="polite"
    ></div>

                    </td>

                    <td>

                        <input
                            type="number"
                            name="parts[0][quantity]"
                            class="zati-part-qty"
                            value="1"
                            min="1"
                            step="1"
                        >

                    </td>

                    <td>

                        <input
                            type="text"
                            name="parts[0][description]"
                            class="zati-readonly zati-part-description"
                            value=""
                            readonly
                        >

                    </td>

                    <td>

                        <input
                            type="text"
                            name="parts[0][unit_price]"
                            class="zati-readonly zati-part-unit-price"
                            value=""
                            readonly
                        >

                    </td>

                    <td>

                        <input
                            type="text"
                            name="parts[0][extended_price]"
                            class="zati-readonly zati-part-extended-price"
                            value="$0.00"
                            readonly
                        >

                    </td>

                    <td class="zati-parts-row-actions">

                        <button
                            type="button"
                            class="zati-row-add"
                            aria-label="Add row"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>

                        <button
                            type="button"
                            class="zati-row-remove is-hidden"
                            aria-label="Remove row"
                        >
                            <i class="fa-solid fa-minus"></i>
                        </button>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</section>

<div class="zati-parts-order-total-row">

    <div class="zati-field zati-total-field">

        <label for="parts_order_total">
            Parts Total
        </label>

        <input
            type="text"
            id="parts_order_total"
            name="parts_order_total"
            value="$0.00"
            class="zati-readonly"
            readonly
        >

    </div>

</div>

<div class="zati-form-actions">

    <button
        type="submit"
        class="zati-primary-btn"
    >
        NEXT
    </button>

</div>

        </form>

    </section>



</main>

<script>
window.zatiForms = {
    ajaxUrl: <?php
        echo wp_json_encode(
            admin_url('admin-ajax.php')
        );
    ?>,
    nonce: <?php
        echo wp_json_encode(
            wp_create_nonce(
                'zati_lookup_part_nonce'
            )
        );
    ?>
};
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById(
        'zati-parts-order-form'
    );

    if (!form) {
        return;
    }

    const registeredSoldPanel =
        document.getElementById(
            'registered-sold-to-panel'
        );

    const differentSoldPanel =
        document.getElementById(
            'different-sold-to-panel'
        );

    const registeredShipPanel =
        document.getElementById(
            'registered-ship-to-panel'
        );

    const differentShipPanel =
        document.getElementById(
            'different-ship-to-panel'
        );

    const soldToCopyPanel =
        document.getElementById(
            'sold-to-copy-panel'
        );

    const sameAsSoldCheckbox =
        document.getElementById(
            'ship_same_as_sold'
        );

    const addressFields = [
        'name',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country'
    ];

    const requiredFields = [
        'name',
        'address1',
        'city',
        'state',
        'zip',
        'country'
    ];


    function getCheckedValue(name, fallback) {

        const checked = form.querySelector(
            'input[name="' + name + '"]:checked'
        );

        return checked
            ? checked.value
            : fallback;
    }


    function updateSoldToDisplay() {

        const soldMode = getCheckedValue(
            'sold_to_mode',
            'registered'
        );

        if (registeredSoldPanel) {
            registeredSoldPanel.hidden =
                soldMode !== 'registered';
        }

        if (differentSoldPanel) {
            differentSoldPanel.hidden =
                soldMode !== 'different';
        }

        updateRequiredFields();
        updateShipToDisplay();

    }


    function updateShipToDisplay() {

        const shipMode = getCheckedValue(
            'ship_to_mode',
            'registered'
        );

        const sameAsSold =
            sameAsSoldCheckbox
            && sameAsSoldCheckbox.checked;

        if (registeredShipPanel) {
            registeredShipPanel.hidden =
                sameAsSold
                || shipMode !== 'registered';
        }

        if (differentShipPanel) {
            differentShipPanel.hidden =
                sameAsSold
                || shipMode !== 'different';
        }

        if (soldToCopyPanel) {
            soldToCopyPanel.hidden =
                !sameAsSold;
        }

        form.querySelectorAll(
            'input[name="ship_to_mode"]'
        ).forEach(function (field) {

            field.disabled = sameAsSold;

        });

        if (sameAsSold) {
            copySoldToIntoShipTo();
        }

        updateRequiredFields();

    }


    function copySoldToIntoShipTo() {

        const soldMode = getCheckedValue(
            'sold_to_mode',
            'registered'
        );

        if (soldMode !== 'different') {
            return;
        }

        addressFields.forEach(function (fieldName) {

            const soldField =
                document.getElementById(
                    'different_sold_to_' + fieldName
                );

            const shipField =
                document.getElementById(
                    'different_ship_to_' + fieldName
                );

            if (!soldField || !shipField) {
                return;
            }

            shipField.value = soldField.value;

        });

    }


    function updateRequiredFields() {

        const soldMode = getCheckedValue(
            'sold_to_mode',
            'registered'
        );

        const shipMode = getCheckedValue(
            'ship_to_mode',
            'registered'
        );

        const sameAsSold =
            sameAsSoldCheckbox
            && sameAsSoldCheckbox.checked;

        requiredFields.forEach(function (fieldName) {

            const soldField =
                document.getElementById(
                    'different_sold_to_' + fieldName
                );

            const shipField =
                document.getElementById(
                    'different_ship_to_' + fieldName
                );

            if (soldField) {
                soldField.required =
                    soldMode === 'different';
            }

            if (shipField) {
                shipField.required =
                    shipMode === 'different'
                    && !sameAsSold;
            }

        });

    }


    form.querySelectorAll(
        'input[name="sold_to_mode"]'
    ).forEach(function (field) {

        field.addEventListener(
            'change',
            updateSoldToDisplay
        );

    });


    form.querySelectorAll(
        'input[name="ship_to_mode"]'
    ).forEach(function (field) {

        field.addEventListener(
            'change',
            updateShipToDisplay
        );

    });


    if (sameAsSoldCheckbox) {

        sameAsSoldCheckbox.addEventListener(
            'change',
            updateShipToDisplay
        );

    }


    addressFields.forEach(function (fieldName) {

        const soldField =
            document.getElementById(
                'different_sold_to_' + fieldName
            );

        if (!soldField) {
            return;
        }

        soldField.addEventListener(
            'input',
            copySoldToIntoShipTo
        );

        soldField.addEventListener(
            'change',
            copySoldToIntoShipTo
        );

    });


    updateSoldToDisplay();
    updateShipToDisplay();

});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const partsTable = document.getElementById(
        'zati-parts-order-table'
    );

    if (!partsTable) {
        return;
    }


    function zatiParseMoney(value) {

        const cleaned = String(value || '')
            .replace(/[^0-9.-]/g, '');

        const amount = parseFloat(cleaned);

        return Number.isFinite(amount)
            ? amount
            : 0;
    }


    function zatiFormatMoney(value) {

        const amount = Number(value) || 0;

        return '$' + amount.toFixed(2);
    }


    function zatiRecalculatePartRow(row) {

        if (!row) {
            return;
        }

        const qtyInput = row.querySelector(
            '.zati-part-qty'
        );

        const priceInput = row.querySelector(
            '.zati-part-unit-price'
        );

        const extendedInput = row.querySelector(
            '.zati-part-extended-price'
        );

        const quantity = Math.max(
            0,
            parseFloat(qtyInput?.value || 0) || 0
        );

        const unitPrice = zatiParseMoney(
            priceInput?.value
        );

        const extendedPrice =
            quantity * unitPrice;

        if (extendedInput) {
            extendedInput.value =
                zatiFormatMoney(extendedPrice);
        }
    }


    function recalculateOrderTotal() {

        let partsTotal = 0;

        partsTable
            .querySelectorAll('tbody tr')
            .forEach(function (row) {

                zatiRecalculatePartRow(row);

                const extendedInput =
                    row.querySelector(
                        '.zati-part-extended-price'
                    );

                partsTotal += zatiParseMoney(
                    extendedInput?.value
                );

            });

        const partsTotalInput =
            document.getElementById(
                'parts_order_total'
            );

        if (partsTotalInput) {
            partsTotalInput.value =
                zatiFormatMoney(partsTotal);
        }
    }


    function zatiUpdatePartRowIndexes() {

        const rows = partsTable.querySelectorAll(
            'tbody tr'
        );

        rows.forEach(function (row, index) {

            row.querySelectorAll('input')
                .forEach(function (input) {

                    const name =
                        input.getAttribute('name');

                    if (!name) {
                        return;
                    }

                    input.setAttribute(
                        'name',
                        name.replace(
                            /parts\[\d+\]/,
                            'parts[' + index + ']'
                        )
                    );

                });

            const removeBtn =
                row.querySelector(
                    '.zati-row-remove'
                );

            if (removeBtn) {

                removeBtn.classList.toggle(
                'is-hidden',
                rows.length === 1
            );

            }

        });
    }


    zatiUpdatePartRowIndexes();
    recalculateOrderTotal();


    


    partsTable.addEventListener(
        'click',
        function (event) {

            const lookupBtn =
                event.target.closest(
                    '.zati-part-lookup'
                );

            const clearBtn =
                event.target.closest(
                    '.zati-part-clear'
                );

            const addBtn =
                event.target.closest(
                    '.zati-row-add'
                );

            const removeBtn =
                event.target.closest(
                    '.zati-row-remove'
                );


        /* =========================
   Lookup
========================== */

if (lookupBtn) {

    const row =
        lookupBtn.closest('tr');

    const partInput =
        row.querySelector(
            '.zati-part-number'
        );

    const descInput =
        row.querySelector(
            '.zati-part-description'
        );

    const priceInput =
        row.querySelector(
            '.zati-part-unit-price'
        );

    const qtyInput =
        row.querySelector(
            '.zati-part-qty'
        );

    const errorBox =
        row.querySelector(
            '.zati-part-error'
        );

    /*
     * Clear the previous error before
     * starting a new lookup.
     */
    if (errorBox) {
        errorBox.textContent = '';
        errorBox.hidden = true;
    }

    partInput.classList.remove(
        'zati-input-error'
    );

    const partnumber =
        partInput.value.trim();


    /* Empty Part Number */

    if (!partnumber) {

        if (errorBox) {
            errorBox.textContent =
                'Please enter a part number.';

            errorBox.hidden = false;
        }

        partInput.classList.add(
            'zati-input-error'
        );

        partInput.focus();

        return;
    }


    lookupBtn.disabled = true;


    const formData =
        new FormData();

    formData.append(
        'action',
        'zati_lookup_part'
    );

    formData.append(
        'nonce',
        zatiForms.nonce
    );

    formData.append(
        'partnumber',
        partnumber
    );
    
      formData.append(
       'price_context',
       'parts_order'
      );


    fetch(
        zatiForms.ajaxUrl,
        {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }
    )
        .then(function (response) {

            return response.text();

        })
        .then(function (responseText) {

            /*
             * WordPress may return -1 when
             * nonce verification fails.
             */
            if (responseText.trim() === '-1') {

                throw new Error(
                    'Security verification failed. Please reload the page.'
                );
            }


            let data;

            try {

                data = JSON.parse(
                    responseText
                );

            } catch (parseError) {

                console.error(
                    'Unexpected lookup response:',
                    responseText
                );

                throw new Error(
                    'The server returned an invalid response.'
                );
            }


            /* Part Not Found */

            if (!data.success) {

                const message =
                    data.data
                    && data.data.message
                        ? data.data.message
                        : 'Part was not found.';

                if (errorBox) {
                    errorBox.textContent =
                        message;

                    errorBox.hidden = false;
                }

                partInput.classList.add(
                    'zati-input-error'
                );

                descInput.value = '';
                priceInput.value = '';

                zatiRecalculatePartRow(row);
                recalculateOrderTotal();

                return;
            }


            /* Lookup Success */

            if (errorBox) {
                errorBox.textContent = '';
                errorBox.hidden = true;
            }

            partInput.classList.remove(
                'zati-input-error'
            );

            descInput.value =
                data.data.description || '';

            priceInput.value =
                data.data.unit_price
                || '$0.00';

            if (
                qtyInput
                && !qtyInput.value
            ) {
                qtyInput.value = '1';
            }

            zatiRecalculatePartRow(row);
            recalculateOrderTotal();

        })
        .catch(function (error) {

            console.error(
                'Parts lookup error:',
                error
            );

            if (errorBox) {
                errorBox.textContent =
                    error.message
                    || 'Lookup failed. Please try again.';

                errorBox.hidden = false;
            }

            partInput.classList.add(
                'zati-input-error'
            );

        })
        .finally(function () {

            lookupBtn.disabled = false;

        });

    return;
}   









/* =========================
   Clear
========================== */
            if (clearBtn) {

                const row =
                    clearBtn.closest('tr');

                row.querySelector(
                    '.zati-part-number'
                ).value = '';

                row.querySelector(
                    '.zati-part-description'
                ).value = '';

                row.querySelector(
                    '.zati-part-unit-price'
                ).value = '';

                const qtyInput =
                    row.querySelector(
                        '.zati-part-qty'
                    );

                if (qtyInput) {
                    qtyInput.value = '1';
                }

                const extendedInput =
                    row.querySelector(
                        '.zati-part-extended-price'
                    );

                if (extendedInput) {
                    extendedInput.value = '$0.00';
                }

                recalculateOrderTotal();

                return;
            }


            /* =========================
               Add Row
            ========================== */

            if (addBtn) {

                const currentRow =
                    addBtn.closest('tr');

                const tbody =
                    currentRow.closest('tbody');

                const newRow =
                    currentRow.cloneNode(true);

                newRow
                    .querySelectorAll('input')
                    .forEach(function (input) {

                        input.value = '';

                    });

                const qtyInput =
                    newRow.querySelector(
                        '.zati-part-qty'
                    );

                if (qtyInput) {
                    qtyInput.value = '1';
                }

                const extendedInput =
                    newRow.querySelector(
                        '.zati-part-extended-price'
                    );

                if (extendedInput) {
                    extendedInput.value = '$0.00';
                }

                tbody.appendChild(newRow);

                zatiUpdatePartRowIndexes();
                recalculateOrderTotal();

                const newPartInput =
                    newRow.querySelector(
                        '.zati-part-number'
                    );

                if (newPartInput) {
                    newPartInput.focus();
                }

                return;
            }


            /* =========================
               Remove Row
            ========================== */

            if (removeBtn) {

    const row =
        removeBtn.closest('tr');

    const tbody =
        row.closest('tbody');

    const partInput =
        row.querySelector(
            '.zati-part-number'
        );

    const partnumber =
        partInput
            ? partInput.value.trim()
            : '';

    if (
        tbody.querySelectorAll('tr')
            .length > 1
    ) {

        /*
         * Remove from sessionStorage.
         */
        if (partnumber) {

            const storageKey =
                'zati_parts_order_selected';

            let storedParts = [];

            try {

                storedParts =
                    JSON.parse(
                        sessionStorage.getItem(
                            storageKey
                        )
                    ) || [];

            } catch (error) {

                storedParts = [];

            }

            storedParts =
            storedParts.filter(
                function (storedPart) {

                    const storedPartnumber =
                        typeof storedPart === 'string'
                            ? storedPart
                            : (
                                storedPart.partnumber
                                || ''
                            );

                    return (
                        storedPartnumber
                        !== partnumber
                    );
                }
            );

            sessionStorage.setItem(
                storageKey,
                JSON.stringify(
                    storedParts
                )
            );
        }


        /*
         * Remove row from Parts Order.
         */
        row.remove();

        zatiUpdatePartRowIndexes();
        recalculateOrderTotal();
    }
}

        }
    );

    /* =========================
    Prefill Parts from Session Storage
    ========================= */

    const storageKey =
    'zati_parts_order_selected';

    const pageParams =
        new URLSearchParams(
            window.location.search
        );

    if (
        pageParams.get('new') === '1'
    ) {
        sessionStorage.removeItem(
            storageKey
        );
    }

    let selectedParts = [];

    try {

        selectedParts =
            JSON.parse(
                sessionStorage.getItem(
                    storageKey
                )
            ) || [];

    } catch (error) {

        selectedParts = [];
    }


    if (selectedParts.length > 0) {

        const tbody =
            document.getElementById(
                'zati-parts-order-body'
            );

        const firstRow =
            tbody
                ? tbody.querySelector(
                    '.zati-part-row'
                )
                : null;

        if (tbody && firstRow) {

            /*
            * Create enough rows.
            */
            while (
                tbody.querySelectorAll(
                    '.zati-part-row'
                ).length
                < selectedParts.length
            ) {

                const newRow =
                    firstRow.cloneNode(true);

                newRow
                    .querySelectorAll('input')
                    .forEach(function (input) {

                        input.value = '';

                    });

                const qtyInput =
                    newRow.querySelector(
                        '.zati-part-qty'
                    );

                if (qtyInput) {
                    qtyInput.value = '1';
                }

                const extendedInput =
                    newRow.querySelector(
                        '.zati-part-extended-price'
                    );

                if (extendedInput) {
                    extendedInput.value =
                        '$0.00';
                }

                tbody.appendChild(
                    newRow
                );
            }


            zatiUpdatePartRowIndexes();


            const rows =
                tbody.querySelectorAll(
                    '.zati-part-row'
                );


            selectedParts.forEach(
                function (
                    part,
                    index
                ) {

                    const row =
                        rows[index];

                    if (!row) {
                        return;
                    }

                    const partInput =
                        row.querySelector(
                            '.zati-part-number'
                        );

                    const qtyInput =
                        row.querySelector(
                            '.zati-part-qty'
                        );

                    const lookupButton =
                        row.querySelector(
                            '.zati-part-lookup'
                        );

                    if (partInput) {
                        partInput.value =
                            part.partnumber;
                    }

                    if (qtyInput) {
                        qtyInput.value =
                            part.quantity;
                    }

                    if (lookupButton) {
                        lookupButton.click();
                    }

                }
            );
                    }
    }
    /* =========================
       Quantity Change
    ========================== */

    partsTable.addEventListener(
        'input',
        function (event) {

            if (
                !event.target.classList.contains(
                    'zati-part-qty'
                )
            ) {
                return;
            }

            const row =
    event.target.closest('tr');

    zatiRecalculatePartRow(row);
    recalculateOrderTotal();


    const partInput =
        row.querySelector(
            '.zati-part-number'
        );

    const partnumber =
        partInput
            ? partInput.value.trim()
            : '';

    const quantity =
        parseInt(
            event.target.value,
            10
        ) || 1;


    if (partnumber) {

        const storageKey =
            'zati_parts_order_selected';

            const pageParams =
            new URLSearchParams(
                window.location.search
            );

        if (
            pageParams.get('new') === '1'
        ) {

            sessionStorage.removeItem(
                storageKey
            );

        }

        let storedParts = [];

        try {

            storedParts =
                JSON.parse(
                    sessionStorage.getItem(
                        storageKey
                    )
                ) || [];

        } catch (error) {

            storedParts = [];

        }


        /*
        * Temporary compatibility:
        * Convert old string format
        * into object format.
        */
        storedParts =
            storedParts.map(
                function (item) {

                    if (
                        typeof item === 'string'
                    ) {
                        return {
                            partnumber: item,
                            quantity: 1
                        };
                    }

                    return item;
                }
            );


        const storedPart =
            storedParts.find(
                function (item) {
                    return (
                        item.partnumber
                        === partnumber
                    );
                }
            );


        if (storedPart) {

            storedPart.quantity =
                quantity;

        } else {

            storedParts.push(
                {
                    partnumber: partnumber,
                    quantity: quantity
                }
            );

        }


        sessionStorage.setItem(
            storageKey,
            JSON.stringify(
                storedParts
            )
        );
    }

        }
    );
/* =========================
   Clear Lookup Error on Input
========================== */

partsTable.addEventListener(
    'input',
    function (event) {

        if (
            !event.target.classList.contains(
                'zati-part-number'
            )
        ) {
            return;
        }

        const row =
            event.target.closest('tr');

        const errorBox =
            row.querySelector(
                '.zati-part-error'
            );

        if (errorBox) {
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        event.target.classList.remove(
            'zati-input-error'
        );

    }
    );
    /* =========================
    Clean Empty Rows on Back
    ========================= */

    window.addEventListener(
        'pageshow',
        function () {

            const tbody =
                document.getElementById(
                    'zati-parts-order-body'
                );

            if (!tbody) {
                return;
            }

            const rows =
                Array.from(
                    tbody.querySelectorAll(
                        '.zati-part-row'
                    )
                );

            if (rows.length <= 1) {
                return;
            }

            rows.forEach(
                function (row) {

                    const partInput =
                        row.querySelector(
                            '.zati-part-number'
                        );

                    const partnumber =
                        partInput
                            ? partInput.value.trim()
                            : '';

                    if (
                        !partnumber &&
                        tbody.querySelectorAll(
                            '.zati-part-row'
                        ).length > 1
                    ) {
                        row.remove();
                    }

                }
            );

            zatiUpdatePartRowIndexes();
            recalculateOrderTotal();

        }
    );

});
</script>

<?php
get_template_part('app-footer');
get_footer('blank');
?>