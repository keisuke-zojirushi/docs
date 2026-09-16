<?php
/*
Template Name: Parts Order Complete
Template Post Type: page
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    auth_redirect();
}

$request_method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$order_id = 0;
$reference_no = '';
$submitted_on = '';

/* =========================
   Display Completed Order
========================= */

if ($request_method === 'GET') {

    $completion_token =
        isset($_GET['order_token'])
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['order_token']
                )
            )
            : '';

    if ($completion_token === '') {

        wp_safe_redirect(
            home_url('/parts-order/')
        );

        exit;
    }

    $completion_data = get_transient(
        'zati_parts_order_complete_'
        . $completion_token
    );

    if (
        !is_array($completion_data)
        || empty($completion_data['order_id'])
        || empty($completion_data['reference_no'])
    ) {
        wp_safe_redirect(
            home_url('/parts-order/')
        );

        exit;
    }

    $completion_user_id = absint(
        $completion_data['user_id'] ?? 0
    );

    /*
     * Only the submitter or an administrator
     * may view this completion result.
     */
    if (
        $completion_user_id
            !== get_current_user_id()
        && !current_user_can(
            'manage_options'
        )
    ) {
        wp_safe_redirect(
            home_url('/parts-order/')
        );

        exit;
    }

    $order_id = absint(
        $completion_data['order_id']
    );

    if (
        get_post_type($order_id)
            !== 'parts_order'
        || get_post_status($order_id)
            !== 'publish'
    ) {
        wp_safe_redirect(
            home_url('/parts-order/')
        );

        exit;
    }

    $reference_no = sanitize_text_field(
        $completion_data['reference_no']
    );

    $submitted_on = sanitize_text_field(
        $completion_data['submitted_on']
        ?? ''
    );
}

/* =========================
   Process Order Submission
========================= */

elseif ($request_method === 'POST') {

    /* =========================
       Nonce Check
    ========================== */

    $nonce =
        isset(
            $_POST[
                'zati_parts_order_submit_nonce'
            ]
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'zati_parts_order_submit_nonce'
                    ]
                )
            )
            : '';

    if (
        !$nonce
        || !wp_verify_nonce(
            $nonce,
            'zati_parts_order_submit'
        )
    ) {
        wp_die(
            esc_html__(
                'Invalid request. Please return to the form and try again.',
                'zati'
            ),
            esc_html__(
                'Invalid Request',
                'zati'
            ),
            [
                'response' => 403,
            ]
        );
    }

    /*
     * Generate one completion token for this POST.
     * It is also used to prevent duplicate orders.
     */
    $completion_token =
        isset($_POST['zati_order_submit_token'])
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['zati_order_submit_token']
                )
            )
            : '';

    if ($completion_token === '') {
        $completion_token =
            wp_generate_uuid4();
    }

    $existing_completion =
        get_transient(
            'zati_parts_order_complete_'
            . $completion_token
        );

    /*
     * Prevent duplicate submissions.
     */
    if (
        is_array($existing_completion)
        && !empty(
            $existing_completion['order_id']
        )
    ) {
        wp_safe_redirect(
            add_query_arg(
                'order_token',
                $completion_token,
                get_permalink()
            )
        );

        exit;
    }

    /* =========================
       Save Parts Order
    ========================== */

    $save_result =
        zati_save_parts_order($_POST);

    if (is_wp_error($save_result)) {

        wp_die(
            esc_html(
                $save_result
                    ->get_error_message()
            ),
            esc_html__(
                'Save Error',
                'zati'
            ),
            [
                'response' => 500,
            ]
        );
    }

    if (
        empty($save_result['order_id'])
        || empty(
            $save_result['reference_no']
        )
    ) {
        wp_die(
            esc_html__(
                'The parts order could not be saved. Please contact Zojirushi America Technical Support.',
                'zati'
            ),
            esc_html__(
                'Save Error',
                'zati'
            ),
            [
                'response' => 500,
            ]
        );
    }

    $order_id = absint(
        $save_result['order_id']
    );

    $reference_no = sanitize_text_field(
        $save_result['reference_no']
    );

    $submitted_on = wp_date(
        'm/d/Y',
        get_post_timestamp($order_id)
    );

    /* =========================
       Store Completion Result
    ========================== */

    set_transient(
        'zati_parts_order_complete_'
        . $completion_token,
        [
            'order_id'     => $order_id,
            'reference_no' => $reference_no,
            'submitted_on' => $submitted_on,
            'user_id'      =>
                get_current_user_id(),
        ],
        DAY_IN_SECONDS
    );

    /*
     * End POST state and redirect to
     * a refresh-safe GET completion page.
     */
    wp_safe_redirect(
        add_query_arg(
            'order_token',
            $completion_token,
            get_permalink()
        )
    );

    exit;
}

else {

    wp_safe_redirect(
        home_url('/parts-order/')
    );

    exit;
}

/* =========================
   Page Output
========================= */

get_header('blank');
get_template_part('app-header');
?>

<script>
sessionStorage.removeItem(
    'zati_parts_order_selected'
);
</script>


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
        Parts Order Complete
    </span>

</div>


<main
    class="
        zati-form-page
        zati-complete-page
    "
>

    <section class="zati-complete-wrap">

        <h1>
            Parts Order Submitted
        </h1>

        <div class="zati-complete-message">

            <div
                class="zati-complete-icon"
                aria-hidden="true"
            >
                ✓
            </div>

            <div>

                <h2>
                    Your parts order has been
                    successfully submitted.
                </h2>

                <p>
                    It will be reviewed and processed by
                    Zojirushi America Technical Support.
                </p>

            </div>

        </div>

        <div class="zati-complete-details">

            <p>

                <strong>
                    Reference No.:
                </strong>

                <?php
                echo esc_html(
                    $reference_no
                );
                ?>

            </p>

            <p>

                <strong>
                    Submitted On:
                </strong>

                <?php
                echo esc_html(
                    $submitted_on
                );
                ?>

            </p>

        </div>

        <p class="zati-complete-note">
            Parts availability and shipping details
            will be confirmed after review by
            Zojirushi America Technical Support.
        </p>

        <div
            class="
                zati-form-actions
                zati-complete-actions
            "
        >

            <a
                class="zati-primary-btn"
                href="<?php
                echo esc_url(
                    add_query_arg(
                        'new',
                        '1',
                        home_url('/parts-order/')
                    )
                );
                ?>"
            >
                SUBMIT ANOTHER ORDER
            </a>

            <a
                class="zati-secondary-btn"
                href="<?php
                echo esc_url(
                    home_url('/')
                );
                ?>"
            >
                BACK TO HOME
            </a>

        </div>

    </section>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');