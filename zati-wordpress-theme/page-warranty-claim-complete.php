<?php
/*
Template Name: Warranty Claim Complete
Template Post Type: page
*/

if (!is_user_logged_in()) {
    auth_redirect();
}

$request_method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$claim_id     = 0;
$reference_no = '';
$submitted_on = '';

/* =========================
   Display Completed Claim
========================= */

if ($request_method === 'GET') {

    $completion_token = isset($_GET['claim_token'])
        ? sanitize_text_field(
            wp_unslash($_GET['claim_token'])
        )
        : '';

    if ($completion_token === '') {
        wp_safe_redirect(home_url('/warranty-claim/'));
        exit;
    }

    $completion_data = get_transient(
        'zati_warranty_complete_' . $completion_token
    );

    if (
        !is_array($completion_data) ||
        empty($completion_data['claim_id']) ||
        empty($completion_data['reference_no'])
    ) {
        wp_safe_redirect(home_url('/warranty-claim/'));
        exit;
    }

    $completion_user_id = absint(
        $completion_data['user_id'] ?? 0
    );

    /*
     * 送信者本人、Administrator、ZAC TSのみ表示可能
     */
    if (
        $completion_user_id !== get_current_user_id() &&
        !zati_is_warranty_claim_ts_user()
    ) {
        wp_safe_redirect(home_url('/warranty-claim/'));
        exit;
    }

    $claim_id = absint(
        $completion_data['claim_id']
    );

    if (
        get_post_type($claim_id) !== 'warranty_claim' ||
        get_post_status($claim_id) !== 'publish'
    ) {
        wp_safe_redirect(home_url('/warranty-claim/'));
        exit;
    }

    $reference_no = sanitize_text_field(
        $completion_data['reference_no']
    );

    $submitted_on = sanitize_text_field(
        $completion_data['submitted_on'] ?? ''
    );
}

/* =========================
   Process Claim Submission
========================= */

elseif ($request_method === 'POST') {

    /* =========================
       Nonce Check
    ========================= */

    if (
        !isset($_POST['zati_warranty_claim_submit_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['zati_warranty_claim_submit_nonce']
                )
            ),
            'zati_warranty_claim_submit'
        )
    ) {
        wp_die(
            esc_html__(
                'Invalid request. Please return to the form and try again.',
                'zati'
            ),
            esc_html__('Invalid Request', 'zati'),
            ['response' => 403]
        );
    }

    /* =========================
       Temporary Upload Token
    ========================= */

    $temp_upload_token = isset(
        $_POST['zati_temp_upload_token']
    )
        ? sanitize_text_field(
            wp_unslash(
                $_POST['zati_temp_upload_token']
            )
        )
        : '';

    if ($temp_upload_token === '') {
        wp_die(
            esc_html__(
                'The temporary upload information could not be found. Please return to the form and try again.',
                'zati'
            ),
            esc_html__('Upload Error', 'zati'),
            ['response' => 400]
        );
    }

    /*
     * 同じPOSTが再送信済みの場合は、
     * 新しいClaimを作らず既存の完了画面へ移動
     */
    $existing_completion = get_transient(
        'zati_warranty_complete_' . $temp_upload_token
    );

    if (
        is_array($existing_completion) &&
        !empty($existing_completion['claim_id'])
    ) {
        wp_safe_redirect(
            add_query_arg(
                'claim_token',
                $temp_upload_token,
                get_permalink()
            )
        );
        exit;
    }

    /* =========================
       Get Temporary Uploads
    ========================= */

    $temp_uploads = get_transient(
        'zati_warranty_upload_' . $temp_upload_token
    );

    if (!is_array($temp_uploads)) {
        wp_die(
            esc_html__(
                'The temporary attachment data has expired. Please return to the form and submit the claim again.',
                'zati'
            ),
            esc_html__('Attachment Data Expired', 'zati'),
            ['response' => 400]
        );
    }

    /* =========================
       Save Warranty Claim
    ========================= */

    $save_result = zati_save_warranty_claim($_POST);

    if (
        !$save_result ||
        empty($save_result['claim_id']) ||
        empty($save_result['reference_no'])
    ) {
        wp_die(
            esc_html__(
                'The warranty claim could not be saved. Please contact Zojirushi America Technical Support.',
                'zati'
            ),
            esc_html__('Save Error', 'zati'),
            ['response' => 500]
        );
    }

    $claim_id = absint(
        $save_result['claim_id']
    );

    $reference_no = sanitize_text_field(
        $save_result['reference_no']
    );

    /* =========================
       Save Attachments
    ========================= */

    $attachment_result =
        zati_save_warranty_attachments(
            $claim_id,
            $reference_no,
            $temp_uploads
        );

    if (
        !empty($attachment_result['errors']) &&
        is_array($attachment_result['errors'])
    ) {
        $error_messages = [];

        foreach (
            $attachment_result['errors']
            as $field => $error
        ) {
            if (is_wp_error($error)) {
                $error_messages[] =
                    $field
                    . ': '
                    . $error->get_error_message();
            }
        }

        wp_die(
            esc_html(
                'The claim was saved, but one or more attachments could not be registered. '
                . implode(' ', $error_messages)
            ),
            esc_html__('Attachment Save Error', 'zati'),
            ['response' => 500]
        );
    }

    /*
     * Purchase Receiptが実際にClaimへ登録されたか確認
     */
    $purchase_receipt_id = absint(
        get_post_meta(
            $claim_id,
            'purchase_receipt_attachment',
            true
        )
    );

    if (!$purchase_receipt_id) {

        /*
         * 不完全なClaimをArchiveへ表示しない
         */
        wp_update_post([
            'ID'          => $claim_id,
            'post_status' => 'draft',
        ]);

        wp_die(
            esc_html__(
                'The required purchase receipt could not be registered. Please contact Zojirushi America Technical Support.',
                'zati'
            ),
            esc_html__('Attachment Save Error', 'zati'),
            ['response' => 500]
        );
    }

    $submitted_on = wp_date(
        'm/d/Y',
        get_post_timestamp($claim_id)
    );

/* =========================
   Send Email Notifications
========================= */

$email_notification_result =
    zati_send_warranty_claim_notifications($claim_id);

    /* =========================
       Store Completion Result
    ========================= */

    set_transient(
        'zati_warranty_complete_' . $temp_upload_token,
        [
            'claim_id'      => $claim_id,
            'reference_no'  => $reference_no,
            'submitted_on'  => $submitted_on,
            'user_id'       => get_current_user_id(),
        ],
        DAY_IN_SECONDS
    );

    delete_transient(
        'zati_warranty_upload_' . $temp_upload_token
    );

    /*
     * POST状態を終了し、更新可能なGET完了画面へ移動
     */
    wp_safe_redirect(
        add_query_arg(
            'claim_token',
            $temp_upload_token,
            get_permalink()
        )
    );
    exit;
}

else {
    wp_safe_redirect(home_url('/warranty-claim/'));
    exit;
}

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
        Warranty Claim Complete
    </span>

</div>


<main class="zati-form-page zati-complete-page">

    <section class="zati-complete-wrap">

        <h1>Warranty Claim Submitted</h1>

        <div class="zati-complete-message">

            <div class="zati-complete-icon" aria-hidden="true">
                ✓
            </div>

            <div>
                <h2>Your warranty claim has been successfully submitted.</h2>

                <p>
                    It will be reviewed and processed by
                    Zojirushi America Technical Support.
                </p>
            </div>

        </div>

        <div class="zati-complete-details">

            <p>
                <strong>Reference No.:</strong>
                <?php echo esc_html($reference_no); ?>
            </p>

            <p>
                <strong>Submitted On:</strong>
                <?php echo esc_html($submitted_on); ?>
            </p>

        </div>

        <p class="zati-complete-note">
            Claims are collected at the end of each month and processed
            in the following month.
        </p>

        <div class="zati-form-actions zati-complete-actions">

    <a
        class="zati-primary-btn"
        href="<?php echo esc_url(home_url('/warranty-claim/')); ?>"
    >
        SUBMIT ANOTHER CLAIM
    </a>

    <a
        class="zati-secondary-btn"
        href="<?php echo esc_url(home_url('/warranty-claim-archive/')); ?>"
    >
        VIEW CLAIM ARCHIVE
    </a>

    <a
        class="zati-secondary-btn"
        href="<?php echo esc_url(home_url('/')); ?>"
    >
        BACK TO HOME
    </a>

</div>
    </section>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');