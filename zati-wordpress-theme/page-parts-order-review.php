<?php
/*
Template Name: Parts Order Review
Template Post Type: page
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}


/* =========================
   Helpers
========================= */

function zati_parts_order_review_text(
    $key,
    $default = ''
) {
    if (!isset($_POST[$key])) {
        return $default;
    }

    return sanitize_text_field(
        wp_unslash($_POST[$key])
    );
}


function zati_parts_order_review_money(
    $value
) {
    $cleaned = preg_replace(
        '/[^0-9.\-]/',
        '',
        (string) $value
    );

    return $cleaned === ''
        ? 0
        : (float) $cleaned;
}


function zati_parts_order_country_name(
    $country_code
) {
    $countries = [
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
    ];

    return $countries[$country_code]
        ?? $country_code;
}


function zati_parts_order_address_from_post(
    $prefix
) {
    $fields = [
        'name',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
    ];

    $address = [];

    foreach ($fields as $field) {

        $key = $prefix . $field;

        $address[$field] = isset($_POST[$key])
            ? sanitize_text_field(
                wp_unslash($_POST[$key])
            )
            : '';
    }

    return $address;
}


function zati_parts_order_render_address(
    $address
) {
    $name = $address['name'] ?? '';
    $address1 = $address['address1'] ?? '';
    $address2 = $address['address2'] ?? '';
    $city = $address['city'] ?? '';
    $state = $address['state'] ?? '';
    $zip = $address['zip'] ?? '';
    $country = $address['country'] ?? '';

    $city_line = trim(
        implode(
            ', ',
            array_filter(
                [
                    $city,
                    trim($state . ' ' . $zip),
                ]
            )
        )
    );
    ?>

    <div class="zati-review-address">

        <?php if ($name !== '') : ?>
            <strong>
                <?php echo esc_html($name); ?>
            </strong>
        <?php endif; ?>

        <?php if ($address1 !== '') : ?>
            <div>
                <?php echo esc_html($address1); ?>
            </div>
        <?php endif; ?>

        <?php if ($address2 !== '') : ?>
            <div>
                <?php echo esc_html($address2); ?>
            </div>
        <?php endif; ?>

        <?php if ($city_line !== '') : ?>
            <div>
                <?php echo esc_html($city_line); ?>
            </div>
        <?php endif; ?>

        <?php if ($country !== '') : ?>
            <div>
                <?php
                echo esc_html(
                    zati_parts_order_country_name(
                        $country
                    )
                );
                ?>
            </div>
        <?php endif; ?>

    </div>

    <?php
}

/* =========================
   Review Request Mode
========================= */

$request_method =
    strtoupper(
        $_SERVER['REQUEST_METHOD']
        ?? 'GET'
    );

$review_token = '';

$review_data = null;


/* =========================
   GET - Load Saved Review
========================= */

if ($request_method === 'GET') {

    $review_token =
        isset($_GET['review_token'])
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['review_token']
                )
            )
            : '';

    if ($review_token === '') {

        wp_safe_redirect(
            home_url('/parts-order/')
        );

        exit;
    }


    $review_data =
        get_transient(
            'zati_parts_order_review_'
            . $review_token
        );


    if (
        !is_array($review_data)
        || empty($review_data['user_id'])
        || (int) $review_data['user_id']
            !== get_current_user_id()
    ) {

        wp_safe_redirect(
            home_url('/parts-order/')
        );

        exit;
    }


    $service_center_name =
        $review_data[
            'service_center_name'
        ] ?? '';

    $account_no =
        $review_data[
            'account_no'
        ] ?? '';

    $phone =
        $review_data[
            'phone'
        ] ?? '';

    $customer_po_no =
        $review_data[
            'customer_po_no'
        ] ?? '';

    $order_date =
        $review_data[
            'order_date'
        ] ?? '';

    $requested_ship_via =
        $review_data[
            'requested_ship_via'
        ] ?? '';

    $requested_ship_date =
        $review_data[
            'requested_ship_date'
        ] ?? 'ASAP';

    $terms =
        $review_data[
            'terms'
        ] ?? '';

    $remarks =
        $review_data[
            'remarks'
        ] ?? '';

    $sold_to =
        $review_data[
            'sold_to'
        ] ?? [];

    $ship_to =
        $review_data[
            'ship_to'
        ] ?? [];

    $parts =
        $review_data[
            'parts'
        ] ?? [];

    $parts_total =
        (float) (
            $review_data[
                'parts_total'
            ] ?? 0
        );

    $submit_token =
        $review_data[
            'submit_token'
        ] ?? '';
}
 
$errors = [];


/* =========================
   POST - Build Review Data
========================= */

if ($request_method === 'POST') {

    /* =========================
       Nonce Verification
    ========================= */

    $nonce =
        isset(
            $_POST[
                'zati_parts_order_nonce'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'zati_parts_order_nonce'
                    ]
                )
            )
            : '';

    if (
        !$nonce
        || !wp_verify_nonce(
            $nonce,
            'zati_parts_order_review'
        )
    ) {

        wp_die(
            esc_html__(
                'Security verification failed.',
                'twentytwentyone-child'
            )
        );
    }

/* =========================
   Basic Order Information
========================= */

$service_center_name =
    zati_parts_order_review_text(
        'service_center_name'
    );

$account_no =
    zati_parts_order_review_text(
        'account_no'
    );

$phone =
    zati_parts_order_review_text(
        'phone'
    );

$customer_po_no =
    zati_parts_order_review_text(
        'customer_po_no'
    );

$order_date =
    zati_parts_order_review_text(
        'order_date'
    );

$requested_ship_via =
    zati_parts_order_review_text(
        'requested_ship_via'
    );

$requested_ship_date =
    zati_parts_order_review_text(
        'requested_ship_date',
        'ASAP'
    );

$terms =
    zati_parts_order_review_text(
        'terms'
    );

$remarks =
    zati_parts_order_review_text(
        'remarks'
    );

/* =========================
   Validation
========================= */


if ($customer_po_no === '') {
    $errors[] =
        'Customer PO No. is required.';
}

/* =========================
   Sold To Address
========================= */

$sold_to_mode =
    zati_parts_order_review_text(
        'sold_to_mode',
        'registered'
    );

if ($sold_to_mode === 'different') {

    $sold_to = [
        'name' => zati_parts_order_review_text(
            'different_sold_to_name'
        ),
        'address1' => zati_parts_order_review_text(
            'different_sold_to_address1'
        ),
        'address2' => zati_parts_order_review_text(
            'different_sold_to_address2'
        ),
        'city' => zati_parts_order_review_text(
            'different_sold_to_city'
        ),
        'state' => zati_parts_order_review_text(
            'different_sold_to_state'
        ),
        'zip' => zati_parts_order_review_text(
            'different_sold_to_zip'
        ),
        'country' => zati_parts_order_review_text(
            'different_sold_to_country'
        ),
    ];

} else {

    $sold_to =
        zati_parts_order_address_from_post(
            'registered_sold_to_'
        );
}

/* =========================
   Ship To Address
========================= */

$ship_to_mode =
    zati_parts_order_review_text(
        'ship_to_mode',
        'registered'
    );

$ship_same_as_sold =
    isset($_POST['ship_same_as_sold'])
    && $_POST['ship_same_as_sold'] === '1';

if ($ship_same_as_sold) {

    $ship_to = $sold_to;

} elseif ($ship_to_mode === 'different') {

    $ship_to = [
        'name' => zati_parts_order_review_text(
            'different_ship_to_name'
        ),
        'address1' => zati_parts_order_review_text(
            'different_ship_to_address1'
        ),
        'address2' => zati_parts_order_review_text(
            'different_ship_to_address2'
        ),
        'city' => zati_parts_order_review_text(
            'different_ship_to_city'
        ),
        'state' => zati_parts_order_review_text(
            'different_ship_to_state'
        ),
        'zip' => zati_parts_order_review_text(
            'different_ship_to_zip'
        ),
        'country' => zati_parts_order_review_text(
            'different_ship_to_country'
        ),
    ];

} else {

    $ship_to =
        zati_parts_order_address_from_post(
            'registered_ship_to_'
        );
}

/* =========================
   Address Validation
========================= */

$required_address_fields = [
    'name',
    'address1',
    'city',
    'state',
    'zip',
    'country',
];

foreach (
    $required_address_fields
    as $field
) {
    if (
        empty($sold_to[$field])
        && $sold_to_mode === 'different'
    ) {
        $errors[] =
            'Please complete the Sold To address.';
        break;
    }
}

foreach (
    $required_address_fields
    as $field
) {
    if (
        empty($ship_to[$field])
        && $ship_to_mode === 'different'
        && !$ship_same_as_sold
    ) {
        $errors[] =
            'Please complete the Ship To address.';
        break;
    }
}

/* =========================
   Parts
========================= */

$parts = [];
$parts_total = 0;

$posted_parts = isset($_POST['parts'])
    && is_array($_POST['parts'])
        ? wp_unslash($_POST['parts'])
        : [];

foreach ($posted_parts as $posted_part) {

    if (!is_array($posted_part)) {
        continue;
    }

    $partnumber = sanitize_text_field(
        $posted_part['partnumber'] ?? ''
    );

    $description = sanitize_text_field(
        $posted_part['description'] ?? ''
    );

    $quantity = isset(
        $posted_part['quantity']
    )
        ? max(
            1,
            absint(
                $posted_part['quantity']
            )
        )
        : 1;

    $unit_price =
        zati_parts_order_review_money(
            $posted_part['unit_price'] ?? 0
        );

    if ($partnumber === '') {
        continue;
    }

    if (
        $description === ''
        || $unit_price <= 0
    ) {
        $errors[] =
            sprintf(
                'Please complete Lookup for part %s.',
                $partnumber
            );

        continue;
    }

    $extended_price =
        $quantity * $unit_price;

    $parts_total += $extended_price;

    $parts[] = [
        'partnumber' => $partnumber,
        'description' => $description,
        'quantity' => $quantity,
        'unit_price' => $unit_price,
        'extended_price' => $extended_price,
    ];
}

if (empty($parts)) {
    $errors[] =
        'At least one valid part is required.';
}
}

/* =========================
   Stop on Validation Errors
========================= */

if (!empty($errors)) {

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


    <span class="zati-subnav-item active">
            Parts Order Review
        </span>

    </div>

    <main class="zati-review-page">

        <section class="zati-review-wrap">

            <h1 class="zati-review-title">
                Parts Order Review
            </h1>

            <div class="zati-form-errors">

                <strong>
                    Please correct the following:
                </strong>

                <ul>
                    <?php foreach (
                        array_unique($errors)
                        as $error
                    ) : ?>

                        <li>
                            <?php echo esc_html($error); ?>
                        </li>

                    <?php endforeach; ?>
                </ul>

            </div>

            <div class="zati-review-actions">

                <button
                    type="button"
                    class="zati-secondary-btn"
                    onclick="history.back();"
                >
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
   POST - Save Review & Redirect
========================= */

if ($request_method === 'POST') {

    $review_token =
        wp_generate_uuid4();

    $submit_token =
        wp_generate_uuid4();


    $review_data = [

        'user_id' =>
            get_current_user_id(),

        'service_center_name' =>
            $service_center_name,

        'account_no' =>
            $account_no,

        'phone' =>
            $phone,

        'customer_po_no' =>
            $customer_po_no,

        'order_date' =>
            $order_date,

        'requested_ship_via' =>
            $requested_ship_via,

        'requested_ship_date' =>
            $requested_ship_date,

        'terms' =>
            $terms,

        'remarks' =>
            $remarks,

        'sold_to' =>
            $sold_to,

        'ship_to' =>
            $ship_to,

        'parts' =>
            $parts,

        'parts_total' =>
            $parts_total,

        'submit_token' =>
            $submit_token,
    ];


    set_transient(
        'zati_parts_order_review_'
        . $review_token,
        $review_data,
        HOUR_IN_SECONDS
    );


    $review_url =
        add_query_arg(
            'review_token',
            $review_token,
            home_url(
                '/parts-order-review/'
            )
        );


    wp_safe_redirect(
        $review_url
    );

    exit;
}

/* =========================
   Page Header
========================= */

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
        Parts Order Review
    </span>

</div>


<main class="zati-form-page zati-review-page">

    <h1>
        Parts Order Review
    </h1>


    <!-- =========================
         Service Center
    ========================== -->

    <div class="zati-review-header">

        <div>

            <span>
                Service Center Name
            </span>

            <strong>
                <?php
                echo esc_html(
                    $service_center_name
                );
                ?>
            </strong>

        </div>


        <div>

            <span>
                Service Center Account No
            </span>

            <strong>
                <?php
                echo esc_html(
                    $account_no
                );
                ?>
            </strong>

        </div>

    </div>


    <!-- =========================
         Notice
    ========================== -->

    <p class="zati-review-note">
        Please review your parts order before submitting.<br>
        Parts availability will be confirmed by
        Zojirushi America Technical Support.<br>
        All amounts are in USD.
    </p>

    <hr>


    <!-- =========================
         Review Layout
    ========================== -->

    <div class="zati-review-layout">


        <!-- =========================
             Main Information
        ========================== -->

        <div class="zati-review-main">


            <!-- Order / Shipping -->

            <div class="zati-review-columns">


                <!-- Order Information -->

                <section class="zati-review-section">

                    <h2>
                        Order Information
                    </h2>

                    <dl>

                        <dt>
                            Customer PO No.
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $customer_po_no
                            );
                            ?>
                        </dd>


                        <dt>
                            Order Date
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $order_date
                            );
                            ?>
                        </dd>


                        <dt>
                            Phone No.
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $phone
                            );
                            ?>
                        </dd>

                    </dl>

                </section>


                <!-- Shipping Request -->

                <section class="zati-review-section">

                    <h2>
                        Shipping Request
                    </h2>

                    <dl>

                        <dt>
                            Ship Via
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $requested_ship_via
                            );
                            ?>
                        </dd>


                        <dt>
                            Ship Date
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $requested_ship_date
                            );
                            ?>
                        </dd>


                        <dt>
                            Terms
                        </dt>

                        <dd>
                            <?php
                            echo esc_html(
                                $terms
                            );
                            ?>
                        </dd>


                        <dt>
                            Remarks
                        </dt>

                        <dd>
                            <?php
                            echo $remarks !== ''
                                ? esc_html($remarks)
                                : '—';
                            ?>
                        </dd>

                    </dl>

                </section>

            </div>


            <!-- Sold To / Ship To -->

            <div class="zati-review-columns">


                <!-- Sold To -->

                <section class="zati-review-section">

                    <h2>
                        Sold To
                    </h2>

                    <?php
                    zati_parts_order_render_address(
                        $sold_to
                    );
                    ?>

                </section>


                <!-- Ship To -->

                <section class="zati-review-section">

                    <h2>
                        Ship To
                    </h2>

                    <?php
                    zati_parts_order_render_address(
                        $ship_to
                    );
                    ?>

                </section>

            </div>


            <!-- =========================
                 Parts Ordered
            ========================== -->

            <section
                class="
                    zati-review-section
                    zati-review-parts
                "
            >

                <h2>
                    Parts Ordered
                </h2>

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

                        <?php foreach (
                            $parts as $part
                        ) : ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $part['partnumber']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $part['description']
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $part['quantity']
                                    );
                                    ?>
                                </td>

                                <td>
                                    $<?php
                                    echo esc_html(
                                        number_format(
                                            $part['unit_price'],
                                            2
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    $<?php
                                    echo esc_html(
                                        number_format(
                                            $part['extended_price'],
                                            2
                                        )
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </section>

        </div>


        <!-- =========================
             Order Summary
        ========================== -->

        <aside class="zati-claim-summary">

            <h2>
                Order Summary
            </h2>

            <dl>

                <dt>
                    Ship Via
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $requested_ship_via
                    );
                    ?>
                </dd>


                <dt>
                    Ship Date
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $requested_ship_date
                    );
                    ?>
                </dd>


                <dt>
                    Terms
                </dt>

                <dd>
                    <?php
                    echo esc_html(
                        $terms
                    );
                    ?>
                </dd>


                <dt>
                    Parts Total
                </dt>

                <dd>

                    <strong>
                        $<?php
                        echo esc_html(
                            number_format(
                                $parts_total,
                                2
                            )
                        );
                        ?>
                    </strong>

                </dd>

            </dl>

            <hr>

            <div class="zati-summary-total">

                <span>
                    Order Total
                </span>

                <strong>
                    $<?php
                    echo esc_html(
                        number_format(
                            $parts_total,
                            2
                        )
                    );
                    ?>
                </strong>

            </div>

        </aside>

    </div>


    <!-- =========================
         Submit Form
    ========================== -->

    <form
        method="post"
        action="<?php
        echo esc_url(
            home_url(
                '/parts-order-complete/'
            )
        );
        ?>"
    >

        <?php
        wp_nonce_field(
            'zati_parts_order_submit',
            'zati_parts_order_submit_nonce'
        );
        ?>
	<input
    	type="hidden"
   	 name="zati_order_submit_token"
    	value="<?php
        echo esc_attr(
            $submit_token
        );
        ?>"
	>

        <!-- Basic Order Information -->

        <input
            type="hidden"
            name="service_center_name"
            value="<?php
            echo esc_attr(
                $service_center_name
            );
            ?>"
        >

        <input
            type="hidden"
            name="account_no"
            value="<?php
            echo esc_attr(
                $account_no
            );
            ?>"
        >

        <input
            type="hidden"
            name="phone"
            value="<?php
            echo esc_attr(
                $phone
            );
            ?>"
        >

        <input
            type="hidden"
            name="customer_po_no"
            value="<?php
            echo esc_attr(
                $customer_po_no
            );
            ?>"
        >

        <input
            type="hidden"
            name="order_date"
            value="<?php
            echo esc_attr(
                $order_date
            );
            ?>"
        >


        <!-- Shipping Request -->

        <input
            type="hidden"
            name="requested_ship_via"
            value="<?php
            echo esc_attr(
                $requested_ship_via
            );
            ?>"
        >

        <input
            type="hidden"
            name="requested_ship_date"
            value="<?php
            echo esc_attr(
                $requested_ship_date
            );
            ?>"
        >

        <input
            type="hidden"
            name="terms"
            value="<?php
            echo esc_attr(
                $terms
            );
            ?>"
        >

        <input
            type="hidden"
            name="remarks"
            value="<?php
            echo esc_attr(
                $remarks
            );
            ?>"
        >


        <!-- Sold To -->

        <?php foreach (
            $sold_to as $field => $value
        ) : ?>

            <input
                type="hidden"
                name="sold_to[<?php
                echo esc_attr($field);
                ?>]"
                value="<?php
                echo esc_attr($value);
                ?>"
            >

        <?php endforeach; ?>


        <!-- Ship To -->

        <?php foreach (
            $ship_to as $field => $value
        ) : ?>

            <input
                type="hidden"
                name="ship_to[<?php
                echo esc_attr($field);
                ?>]"
                value="<?php
                echo esc_attr($value);
                ?>"
            >

        <?php endforeach; ?>


        <!-- Parts -->

        <?php foreach (
            $parts as $index => $part
        ) : ?>

            <?php foreach (
                $part as $field => $value
            ) : ?>

                <input
                    type="hidden"
                    name="parts[<?php
                    echo esc_attr($index);
                    ?>][<?php
                    echo esc_attr($field);
                    ?>]"
                    value="<?php
                    echo esc_attr($value);
                    ?>"
                >

            <?php endforeach; ?>

        <?php endforeach; ?>


        <input
            type="hidden"
            name="parts_total"
            value="<?php
            echo esc_attr(
                $parts_total
            );
            ?>"
        >


        <!-- Actions -->

        <div
            class="
                zati-form-actions
                zati-review-actions
            "
        >

            <button
                type="button"
                class="
                    zati-secondary-btn
                    bte
                "
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

</main>

<?php
get_template_part('app-footer');
get_footer('blank');
?>