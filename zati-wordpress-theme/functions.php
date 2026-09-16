<?php
function zats_child_enqueue_styles() {
    wp_enqueue_style(
        'twentytwentyfive-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    wp_enqueue_style(
        'twentytwentyfive-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('twentytwentyfive-parent-style'),
        time()
    );
}

/* =========================
   Remove Unused Admin Menus
========================= */

add_action('admin_menu', function () {

    // Posts
    remove_menu_page('edit.php');

    // Comments
    remove_menu_page('edit-comments.php');

    // Tools
    remove_menu_page('tools.php');

});

/* =========================
   Disable Comments
========================= */

add_action('admin_init', function () {

    // コメントを閉じる
    foreach (get_post_types() as $post_type) {

        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});

// 管理メニューから削除
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

// 管理バーから削除
add_action('wp_before_admin_bar_render', function () {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
});

add_action('wp_enqueue_scripts', 'zats_child_enqueue_styles');

function zac_register_news_post_type() {
  register_post_type('zac_news', array(
    'labels' => array(
      'name' => 'News Topics',
      'singular_name' => 'News Topic',
      'add_new_item' => 'Add News Topic',
      'edit_item' => 'Edit News Topic',
    ),

    'has_archive' => true,
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-megaphone',
    'supports' => array('title', 'editor'),
    'rewrite' => array('slug' => 'news'),
  ));
}
add_action('init', 'zac_register_news_post_type');

function zac_remove_parent_theme_styles() {
  wp_dequeue_style('twenty-twenty-one-style');
  wp_deregister_style('twenty-twenty-one-style');
}
add_action('wp_enqueue_scripts', 'zac_remove_parent_theme_styles', 20);

function zati_get_svc_price($distributor_price)
{
    if ($distributor_price === '' || $distributor_price === null) {
        return '';
    }

    return round((float) $distributor_price * 0.82, 2);
}

function zati_get_part_price_by_role($distributor_price)
{
    if ($distributor_price === '' || $distributor_price === null) {
        return '';
    }

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

    if (in_array('mexico_svc', $roles, true)) {
        return round((float) $distributor_price, 2);
    }

    if (in_array('canada_parts_sales', $roles, true)) {
        return round((float) $distributor_price * 0.82 * 0.85, 2);
    }

    if (in_array('us_canada_svc', $roles, true)) {
        return zati_get_svc_price($distributor_price);
    }

    return round((float) $distributor_price, 2);
}

add_action('admin_menu', function () {

    $post_types = [
        'model_alias',
        'parts',
                'post',
               'news_topics',
    ];

    foreach ($post_types as $pt) {
        remove_meta_box('commentstatusdiv', $pt, 'normal');
        remove_meta_box('commentsdiv', $pt, 'normal');
    }

});


function zati_load_fontawesome() {
    wp_enqueue_style(
        'fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        [],
        '6.5.2'
    );
}
add_action('wp_enqueue_scripts', 'zati_load_fontawesome');


/* =========================
   ZATI Form CSS
========================= */

function zati_enqueue_forms_css() {

     if (!is_page([
    'warranty-claim',
    'warranty-claim-review',
    'warranty-claim-complete',
    'warranty-claim-archive',
    'warranty-claim-detail',
    'svc-form',
    'parts-order',
    'parts-order-review',
    'parts-order-complete',
    'parts-order-archive',
    'parts-order-detail',
    'open-backorders',
    'backorder-history',

    ])) {
    return;
   }
    $css_path = get_stylesheet_directory() . '/assets/css/zati-forms.css';

    wp_enqueue_style(
        'zati-forms',
        get_stylesheet_directory_uri() . '/assets/css/zati-forms.css',
        [],
        file_exists($css_path) ? filemtime($css_path) : '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'zati_enqueue_forms_css');


/* =========================
   ZATI Form JavaScript
========================= */

function zati_forms_scripts() {

    if (!is_page([
        'warranty-claim',
        'warranty-claim-review',
    ])) {
        return;
    }

    $js_path = get_stylesheet_directory() . '/assets/js/zati-forms.js';

    wp_enqueue_script(
        'zati-forms',
        get_stylesheet_directory_uri() . '/assets/js/zati-forms.js',
        [],
        file_exists($js_path) ? filemtime($js_path) : '1.0.0',
        true
    );

    wp_localize_script('zati-forms', 'zatiForms', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('zati_lookup_part_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'zati_forms_scripts');

/* =========================
   Require Login for ZATI
========================= */

add_action('template_redirect', 'zati_require_frontend_login', 1);

function zati_require_frontend_login() {

    /*
     * ログイン済みの場合は何もしない
     */
    if (is_user_logged_in()) {
        return;
    }

    /*
     * ログイン前でも表示を許可するページ
     */
    $public_pages = [
        'login',
        'forgot-password',
        'forgot-password-confirmation',
        'terms-of-use',
    ];

    if (is_page($public_pages)) {
        return;
    }

    /*
     * その他のフロントページはLoginへ移動
     */
    wp_safe_redirect(home_url('/login/'));
    exit;
}

/* =========================
   Restrict Warranty Claim Pages
========================= */

add_action(
    'template_redirect',
    'zati_restrict_warranty_claim_pages',
    5
);

function zati_restrict_warranty_claim_pages() {

    /*
     * 未ログイン時は既存のLogin制限に任せる
     */
    if (!is_user_logged_in()) {
        return;
    }

    $warranty_claim_pages = [
        'warranty-claim',
        'warranty-claim-review',
        'warranty-claim-complete',
        'warranty-claim-archive',
        'warranty-claim-detail',
    ];

    if (!is_page($warranty_claim_pages)) {
        return;
    }

    /*
     * Administrator／ZAC TS／US & Canada SVCは許可
     */
    if (zati_current_user_can_access_warranty_claim()) {
        return;
    }

    /*
     * Mexico SVC／Parts RetailerなどはFormsトップへ戻す
     */
    wp_safe_redirect(
        add_query_arg(
            'module_notice',
            'warranty_unavailable',
            home_url('/svc-form/')
        )
    );
    exit;
}

/* =========================
   ZATI Service Center User Profile
========================= */

add_action(
    'edit_user_profile',
    'zati_render_service_center_profile_fields'
);

function zati_render_service_center_profile_fields($user) {

    if (
        !$user instanceof WP_User ||
        !current_user_can('edit_user', $user->ID)
    ) {
        return;
    }

    /*
     * Service Center basic information
     */
    $service_center_name = sanitize_text_field(
        get_user_meta(
            $user->ID,
            'zati_service_center_name',
            true
        )
    );

    $account_no = sanitize_text_field(
        get_user_meta(
            $user->ID,
            'zati_account_no',
            true
        )
    );

    $phone = sanitize_text_field(
        get_user_meta(
            $user->ID,
            'zati_phone',
            true
        )
    );
    $default_ship_via = sanitize_text_field(
      get_user_meta(
        $user->ID,
        'zati_default_ship_via',
        true
       )
    );

    $terms = sanitize_text_field(
       get_user_meta(
        $user->ID,
        'zati_terms',
        true
       )
    );

    /*
     * Sold To information
     */
    $sold_to = zati_get_service_center_address(
        $user->ID,
        'sold_to'
    );

    /*
     * Default Ship To information
     */
    $ship_to = zati_get_service_center_address(
        $user->ID,
        'ship_to'
    );
    ?>

    <h2>ZATI Service Center Information</h2>

    <?php
    wp_nonce_field(
        'zati_save_service_center_profile',
        'zati_service_center_profile_nonce'
    );
    ?>

    <table class="form-table" role="presentation">

        <tr>
            <th>
                <label for="zati_service_center_name">
                    Service Center Name
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="zati_service_center_name"
                    name="zati_service_center_name"
                    value="<?php echo esc_attr(
                        $service_center_name
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="zati_account_no">
                    Account No.
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="zati_account_no"
                    name="zati_account_no"
                    value="<?php echo esc_attr(
                        $account_no
                    ); ?>"
                    class="regular-text"
                >

                <p class="description">
                    Used to control access to Parts Orders and
                    Warranty Claims.
                </p>
            </td>
        </tr>

        <tr>
            <th>
                <label for="zati_phone">
                    Phone No.
                </label>
            </th>

<tr>
    <th>
        <label for="zati_default_ship_via">
            Default Ship Via
        </label>
    </th>

    <td>
        <select
            id="zati_default_ship_via"
            name="zati_default_ship_via"
        >
            <option value="">
                Select Default Ship Via
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

        <p class="description">
            Automatically selected when this Service Center
            opens a Parts Order.
        </p>
    </td>
</tr>

<tr>
    <th>
        <label for="zati_terms">
            Terms
        </label>
    </th>

    <td>
        <input
            type="text"
            id="zati_terms"
            name="zati_terms"
            value="<?php echo esc_attr($terms); ?>"
            class="regular-text"
            placeholder="Example: Net 30"
        >

        <p class="description">
            Displayed as read-only on the Parts Order form.
        </p>
    </td>
</tr>

            <td>
                <input
                    type="text"
                    id="zati_phone"
                    name="zati_phone"
                    value="<?php echo esc_attr($phone); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

    </table>

    <h2>Sold To Information</h2>

    <p>
        This address will be automatically entered as the
        Sold To address on the Parts Order form.
    </p>

    <?php
    zati_render_service_center_address_fields(
        'sold_to',
        $sold_to
    );
    ?>

    <h2>Default Ship To Information</h2>

    <p>
        This address will be automatically entered as the
        default Ship To address on the Parts Order form.
    </p>

    <?php
    zati_render_service_center_address_fields(
        'ship_to',
        $ship_to
    );
}


/* =========================
   Service Center Address Field Renderer
========================= */

function zati_render_service_center_address_fields(
    $address_type,
    $values = []
) {
    $address_type = $address_type === 'ship_to'
        ? 'ship_to'
        : 'sold_to';

    $prefix = 'zati_' . $address_type . '_';

    $values = wp_parse_args(
        $values,
        [
            'name'     => '',
            'address1' => '',
            'address2' => '',
            'city'     => '',
            'state'    => '',
            'zip'      => '',
            'country'  => '',
        ]
    );
    ?>

    <table class="form-table" role="presentation">

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'name'
                ); ?>">
                    Company / Recipient Name
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr(
                        $prefix . 'name'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'name'
                    ); ?>"
                    value="<?php echo esc_attr(
                        $values['name']
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'address1'
                ); ?>">
                    Address Line 1
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr(
                        $prefix . 'address1'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'address1'
                    ); ?>"
                    value="<?php echo esc_attr(
                        $values['address1']
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'address2'
                ); ?>">
                    Address Line 2
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr(
                        $prefix . 'address2'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'address2'
                    ); ?>"
                    value="<?php echo esc_attr(
                        $values['address2']
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'city'
                ); ?>">
                    City
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr(
                        $prefix . 'city'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'city'
                    ); ?>"
                    value="<?php echo esc_attr(
                        $values['city']
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'state'
                ); ?>">
                    State / Province
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr(
                        $prefix . 'state'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'state'
                    ); ?>"
                    value="<?php echo esc_attr(
                        $values['state']
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'zip'
                ); ?>">
                    ZIP / Postal Code
                </label>
            </th>

            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr(
                        $prefix . 'zip'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'zip'
                    ); ?>"
                    value="<?php echo esc_attr(
                        $values['zip']
                    ); ?>"
                    class="regular-text"
                >
            </td>
        </tr>

        <tr>
            <th>
                <label for="<?php echo esc_attr(
                    $prefix . 'country'
                ); ?>">
                    Country
                </label>
            </th>

            <td>
                <select
                    id="<?php echo esc_attr(
                        $prefix . 'country'
                    ); ?>"
                    name="<?php echo esc_attr(
                        $prefix . 'country'
                    ); ?>"
                >
                    <option value="">
                        Select Country
                    </option>

                    <option
                        value="US"
                        <?php selected(
                            $values['country'],
                            'US'
                        ); ?>
                    >
                        United States
                    </option>

                    <option
                        value="CA"
                        <?php selected(
                            $values['country'],
                            'CA'
                        ); ?>
                    >
                        Canada
                    </option>

                    <option
                        value="MX"
                        <?php selected(
                            $values['country'],
                            'MX'
                        ); ?>
                    >
                        Mexico
                    </option>
                </select>
            </td>
        </tr>

    </table>

    <?php
}


/* =========================
   Save Service Center Profile
========================= */

add_action(
    'edit_user_profile_update',
    'zati_save_service_center_profile_fields'
);

function zati_save_service_center_profile_fields($user_id) {

    $user_id = absint($user_id);

    if (
        !$user_id ||
        !current_user_can('edit_user', $user_id)
    ) {
        return;
    }

    if (
        empty($_POST['zati_service_center_profile_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST[
                        'zati_service_center_profile_nonce'
                    ]
                )
            ),
            'zati_save_service_center_profile'
        )
    ) {
        return;
    }

    /*
     * Basic information
     */
   $basic_fields = [
    'zati_service_center_name',
    'zati_account_no',
    'zati_phone',
    'zati_default_ship_via',
    'zati_terms',
   ];
    foreach ($basic_fields as $field_name) {

        $value = isset($_POST[$field_name])
            ? sanitize_text_field(
                wp_unslash($_POST[$field_name])
            )
            : '';

        update_user_meta(
            $user_id,
            $field_name,
            $value
        );
    }

    /*
     * Address information
     */
    foreach (['sold_to', 'ship_to'] as $address_type) {

        $address = [];

        foreach (
            [
                'name',
                'address1',
                'address2',
                'city',
                'state',
                'zip',
                'country',
            ] as $field
        ) {
            $post_key =
                'zati_' . $address_type . '_' . $field;

            $address[$field] = isset($_POST[$post_key])
                ? sanitize_text_field(
                    wp_unslash($_POST[$post_key])
                )
                : '';
        }

        zati_update_service_center_address(
            $user_id,
            $address_type,
            $address
        );
    }
}


/* =========================
   Service Center Address Helpers
========================= */

function zati_get_service_center_address(
    $user_id,
    $address_type = 'sold_to'
) {
    $user_id = absint($user_id);

    $address_type = $address_type === 'ship_to'
        ? 'ship_to'
        : 'sold_to';

    $address = [];

    foreach (
        [
            'name',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
        ] as $field
    ) {
        $meta_key =
            'zati_' . $address_type . '_' . $field;

        $address[$field] = sanitize_text_field(
            get_user_meta(
                $user_id,
                $meta_key,
                true
            )
        );
    }

    return $address;
}


function zati_update_service_center_address(
    $user_id,
    $address_type,
    $address
) {
    $user_id = absint($user_id);

    if (!$user_id) {
        return false;
    }

    $address_type = $address_type === 'ship_to'
        ? 'ship_to'
        : 'sold_to';

    $address = is_array($address)
        ? $address
        : [];

    foreach (
        [
            'name',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
        ] as $field
    ) {
        $meta_key =
            'zati_' . $address_type . '_' . $field;

        $value = isset($address[$field])
            ? sanitize_text_field($address[$field])
            : '';

        update_user_meta(
            $user_id,
            $meta_key,
            $value
        );
    }

    return true;
}


/* =========================
   Current Service Center
========================= */

function zati_get_current_service_center_name() {

    return sanitize_text_field(
        get_user_meta(
            get_current_user_id(),
            'zati_service_center_name',
            true
        )
    );
}


function zati_get_current_service_center_account_no() {

    return sanitize_text_field(
        get_user_meta(
            get_current_user_id(),
            'zati_account_no',
            true
        )
    );
}


function zati_get_current_service_center_phone() {

    return sanitize_text_field(
        get_user_meta(
            get_current_user_id(),
            'zati_phone',
            true
        )
    );
}


function zati_get_current_service_center_sold_to() {

    return zati_get_service_center_address(
        get_current_user_id(),
        'sold_to'
    );
}

function zati_get_current_service_center_default_ship_via() {

    return sanitize_text_field(
        get_user_meta(
            get_current_user_id(),
            'zati_default_ship_via',
            true
        )
    );
}


function zati_get_current_service_center_terms() {

    return sanitize_text_field(
        get_user_meta(
            get_current_user_id(),
            'zati_terms',
            true
        )
    );
}


function zati_get_current_service_center_ship_to() {

    return zati_get_service_center_address(
        get_current_user_id(),
        'ship_to'
    );
}


/* =========================
   Default Ship To Check
========================= */

function zati_service_center_has_default_ship_to(
    $user_id = 0
) {
    $user_id = $user_id
        ? absint($user_id)
        : get_current_user_id();

    $ship_to = zati_get_service_center_address(
        $user_id,
        'ship_to'
    );

    return (
        $ship_to['name'] !== '' ||
        $ship_to['address1'] !== '' ||
        $ship_to['city'] !== '' ||
        $ship_to['state'] !== '' ||
        $ship_to['zip'] !== ''
    );
}



/* =========================
   Warranty Claim User Roles
========================= */

function zati_is_warranty_claim_ts_user($user = null) {

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    return in_array('administrator', $user->roles, true)
        || in_array('zac_ts', $user->roles, true);
}

function zati_is_warranty_claim_svc_user($user = null) {

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    return in_array(
        'us_canada_svc',
        (array) $user->roles,
        true
    );
}

/* =========================
   Warranty Claim View Permission
========================= */

function zati_current_user_can_view_warranty_claim($claim_id) {

    $claim_id = absint($claim_id);

    if (
        !$claim_id ||
        get_post_type($claim_id) !== 'warranty_claim' ||
        get_post_status($claim_id) !== 'publish'
    ) {
        return false;
    }

    $current_user = wp_get_current_user();

    /*
     * Administrator／ZAC TSは全件閲覧可能
     */
    if (zati_is_warranty_claim_ts_user($current_user)) {
        return true;
    }

    /*
     * Warranty Claimを利用できるSVCは
     * US & Canada SVCだけ
     */
    if (!zati_is_warranty_claim_svc_user($current_user)) {
        return false;
    }

    $user_account_no =
        zati_get_current_service_center_account_no();

    $claim_account_no = sanitize_text_field(
        get_post_meta(
            $claim_id,
            'account_no',
            true
        )
    );

    if (
        $user_account_no === '' ||
        $claim_account_no === ''
    ) {
        return false;
    }

    return strcasecmp(
        trim($user_account_no),
        trim($claim_account_no)
    ) === 0;
}

/* =========================
   ZATI Module Permissions
========================= */

function zati_current_user_can_access_warranty_claim() {

    $user = wp_get_current_user();

    $allowed_roles = [
        'administrator',
        'zac_ts',
        'us_canada_svc',
    ];

    return (bool) array_intersect(
        $allowed_roles,
        (array) $user->roles
    );
}

function zati_current_user_can_access_parts_order() {

    $user = wp_get_current_user();

    $allowed_roles = [
        'administrator',
        'zac_ts',
        'us_canada_svc',
        'canada_parts_sales',
        'mexico_svc',
    ];

    return (bool) array_intersect(
        $allowed_roles,
        (array) $user->roles
    );
}


/* =========================
   Restrict WordPress Admin
========================= */

function zati_current_user_can_access_wp_admin() {

    $user = wp_get_current_user();

    $admin_roles = [
        'administrator',
        'zac_ts',
    ];

    return (bool) array_intersect(
        $admin_roles,
        (array) $user->roles
    );
}

add_action('admin_init', 'zati_restrict_wp_admin_access');

function zati_restrict_wp_admin_access() {

    /*
     * AJAX処理は妨げない
     */
    if (wp_doing_ajax()) {
        return;
    }

    /*
     * フォームのバックエンド処理は妨げない
     */
    global $pagenow;

    if ($pagenow === 'admin-post.php') {
        return;
    }

    /*
     * Administrator／ZAC TSは管理画面を許可
     */
    if (zati_current_user_can_access_wp_admin()) {
        return;
    }

    /*
     * その他のユーザーはZATIトップへ戻す
     */
    wp_safe_redirect(home_url('/'));
    exit;
}

/* =========================
   Hide Admin Bar for SVC Users
========================= */

add_filter('show_admin_bar', 'zati_control_admin_bar');

function zati_control_admin_bar($show) {

    if (!is_user_logged_in()) {
        return false;
    }

    return zati_current_user_can_access_wp_admin();
}

/* =========================
   Warranty Claim Archive Query
========================= */

function zati_get_warranty_archive_query_args($filters = []) {

    $year = isset($filters['year'])
        ? absint($filters['year'])
        : 0;

    $month = isset($filters['month'])
        ? absint($filters['month'])
        : 0;

    $service_center_account = isset($filters['account_no'])
        ? sanitize_text_field($filters['account_no'])
        : '';

    $query_args = [
        'post_type'      => 'warranty_claim',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ($year > 0) {

        $date_query = [
            'year' => $year,
        ];

        if ($month >= 1 && $month <= 12) {
            $date_query['monthnum'] = $month;
        }

        $query_args['date_query'] = [
            $date_query,
        ];
    }

    $meta_query = [
        'relation' => 'AND',
    ];

    $current_user = wp_get_current_user();

    /*
     * SVCは自分のAccount No.に強制固定
     */
    if (zati_is_warranty_claim_svc_user($current_user)) {

        $current_account_no =
            zati_get_current_service_center_account_no();

        if ($current_account_no === '') {

            /*
             * Account No.未設定の場合は何も表示しない
             */
            $query_args['post__in'] = [0];

        } else {

            $meta_query[] = [
                'key'     => 'account_no',
                'value'   => $current_account_no,
                'compare' => '=',
            ];
        }

    } elseif (zati_is_warranty_claim_ts_user($current_user)) {

        /*
         * TS／AdministratorのみService Centerを指定可能
         */
        if ($service_center_account !== '') {
            $meta_query[] = [
                'key'     => 'account_no',
                'value'   => $service_center_account,
                'compare' => '=',
            ];
        }

    } else {

        /*
         * Warranty Claim閲覧対象外のロール
         */
        $query_args['post__in'] = [0];
    }

    if (count($meta_query) > 1) {
        $query_args['meta_query'] = $meta_query;
    }

    return $query_args;
}

/* =========================
   Warranty Claim CSV Download
========================= */

add_action(
    'template_redirect',
    'zati_handle_warranty_claim_csv_download',
    20
);

function zati_handle_warranty_claim_csv_download() {

    $download_type = isset($_GET['zati_warranty_download'])
        ? sanitize_key(
            wp_unslash($_GET['zati_warranty_download'])
        )
        : '';

    if ($download_type !== 'csv') {
        return;
    }

    if (
        !is_user_logged_in() ||
        !zati_current_user_can_access_warranty_claim()
    ) {
        wp_safe_redirect(home_url('/login/'));
        exit;
    }

    $nonce = isset($_GET['_wpnonce'])
        ? sanitize_text_field(
            wp_unslash($_GET['_wpnonce'])
        )
        : '';

    if (
        !wp_verify_nonce(
            $nonce,
            'zati_download_warranty_csv'
        )
    ) {
        wp_die(
            esc_html__(
                'The download link is invalid or has expired.',
                'zati'
            ),
            esc_html__('Download Error', 'zati'),
            ['response' => 403]
        );
    }

    $year = isset($_GET['claim_year'])
        ? absint($_GET['claim_year'])
        : (int) wp_date('Y');

    $month = isset($_GET['claim_month'])
        ? absint($_GET['claim_month'])
        : 0;

    $selected_account_no = isset($_GET['service_center'])
        ? sanitize_text_field(
            wp_unslash($_GET['service_center'])
        )
        : '';

    $query_args = zati_get_warranty_archive_query_args([
        'year'       => $year,
        'month'      => $month,
        'account_no' => $selected_account_no,
    ]);

    $claims_query = new WP_Query($query_args);

    /*
     * SVCではURL上のAccount No.を信用せず、
     * ログインユーザーのAccount No.を使用する。
     */
    if (zati_is_warranty_claim_svc_user()) {
        $filename_account =
            zati_get_current_service_center_account_no();
    } else {
        $filename_account = $selected_account_no;
    }

    if ($filename_account === '') {
        $filename_account = 'all-service-centers';
    }

    $filename_period = (string) $year;

    if ($month >= 1 && $month <= 12) {
        $filename_period .= '-'
            . str_pad(
                (string) $month,
                2,
                '0',
                STR_PAD_LEFT
            );
    } else {
        $filename_period .= '-all-months';
    }

    $filename = sanitize_file_name(
        'warranty-claims_'
        . $filename_account
        . '_'
        . $filename_period
        . '.csv'
    );

    /*
     * Excelの数式として解釈される文字列を防止
     */
    $safe_csv_text = static function ($value) {

        $value = (string) $value;

        if (
            $value !== '' &&
            preg_match('/^[=\-+@]/', $value)
        ) {
            return "'" . $value;
        }

        return $value;
    };

    /*
     * ACFの日付形式をUS形式に変換
     */
    $format_csv_date = static function ($value) {

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $formats = [
            'Ymd',
            'Y-m-d',
            'm/d/Y',
            'd/m/Y',
        ];

        foreach ($formats as $format) {

            $date = DateTime::createFromFormat(
                $format,
                $value
            );

            if ($date instanceof DateTime) {
                return $date->format('m/d/Y');
            }
        }

        $timestamp = strtotime($value);

        return $timestamp !== false
            ? wp_date('m/d/Y', $timestamp)
            : $value;
    };

    /*
     * ヘッダー出力前に既存の出力バッファを破棄
     */
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    nocache_headers();

    header('Content-Type: text/csv; charset=UTF-8');
    header(
        'Content-Disposition: attachment; filename="'
        . $filename
        . '"'
    );

    $output = fopen('php://output', 'w');

    if ($output === false) {
        exit;
    }

    /*
     * Excelで文字化けさせないためのUTF-8 BOM
     */
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Reference No.',
        'Submitted',
        'Service Center',
        'Account No.',
        'Invoice No.',
        'Model',
        'Claim Code',
        'Date In',
        'Date Out',
        'Purchase Date',
        'Store Name',
        'Parts Total',
        'Stocking Fee',
        'Labor',
        'Shipping In',
        'Shipping Out',
        'Total Payment',
    ]);

    foreach ($claims_query->posts as $claim_post) {

        $claim_id = $claim_post->ID;

        fputcsv($output, [
            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'reference_no',
                    true
                )
            ),

            get_the_date('m/d/Y', $claim_id),

            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'service_center',
                    true
                )
            ),

            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'account_no',
                    true
                )
            ),

            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'invoice_no',
                    true
                )
            ),

            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'model_no',
                    true
                )
            ),

            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'claim_code',
                    true
                )
            ),

            $format_csv_date(
                get_post_meta(
                    $claim_id,
                    'date_in',
                    true
                )
            ),

            $format_csv_date(
                get_post_meta(
                    $claim_id,
                    'date_out',
                    true
                )
            ),

            $format_csv_date(
                get_post_meta(
                    $claim_id,
                    'purchase_date',
                    true
                )
            ),

            $safe_csv_text(
                get_post_meta(
                    $claim_id,
                    'store_name',
                    true
                )
            ),

            number_format(
                (float) get_post_meta(
                    $claim_id,
                    'parts_total',
                    true
                ),
                2,
                '.',
                ''
            ),

            number_format(
                (float) get_post_meta(
                    $claim_id,
                    'stocking_fee',
                    true
                ),
                2,
                '.',
                ''
            ),

            number_format(
                (float) get_post_meta(
                    $claim_id,
                    'labor',
                    true
                ),
                2,
                '.',
                ''
            ),

            number_format(
                (float) get_post_meta(
                    $claim_id,
                    'shipping_fee_in',
                    true
                ),
                2,
                '.',
                ''
            ),

            number_format(
                (float) get_post_meta(
                    $claim_id,
                    'shipping_fee_out',
                    true
                ),
                2,
                '.',
                ''
            ),

            number_format(
                (float) get_post_meta(
                    $claim_id,
                    'total_payment',
                    true
                ),
                2,
                '.',
                ''
            ),
        ]);
    }

    fclose($output);
    wp_reset_postdata();
    exit;
}

/* =========================
   Warranty Attachment ZIP Download
========================= */

add_action(
    'template_redirect',
    'zati_handle_warranty_attachment_download',
    20
);

function zati_handle_warranty_attachment_download() {

    $download_type = isset($_GET['zati_warranty_download'])
        ? sanitize_key(
            wp_unslash($_GET['zati_warranty_download'])
        )
        : '';

    if ($download_type !== 'attachments') {
        return;
    }

    if (
        !is_user_logged_in() ||
        !zati_current_user_can_access_warranty_claim()
    ) {
        wp_safe_redirect(home_url('/login/'));
        exit;
    }

    $nonce = isset($_GET['_wpnonce'])
        ? sanitize_text_field(
            wp_unslash($_GET['_wpnonce'])
        )
        : '';

    if (
        !wp_verify_nonce(
            $nonce,
            'zati_download_warranty_attachments'
        )
    ) {
        wp_die(
            esc_html__(
                'The download link is invalid or has expired.',
                'zati'
            ),
            esc_html__('Download Error', 'zati'),
            ['response' => 403]
        );
    }

    $year = isset($_GET['claim_year'])
        ? absint($_GET['claim_year'])
        : (int) wp_date('Y');

    $month = isset($_GET['claim_month'])
        ? absint($_GET['claim_month'])
        : 0;

    $selected_account_no = isset($_GET['service_center'])
        ? sanitize_text_field(
            wp_unslash($_GET['service_center'])
        )
        : '';

    /*
     * ZIPは特定月のみ
     */
    if ($month < 1 || $month > 12) {
        zati_redirect_warranty_download_notice(
            'select_month',
            $year,
            0,
            $selected_account_no
        );
    }

    if (!class_exists('ZipArchive')) {
        zati_redirect_warranty_download_notice(
            'zip_unavailable',
            $year,
            $month,
            $selected_account_no
        );
    }

    $query_args = zati_get_warranty_archive_query_args([
        'year'       => $year,
        'month'      => $month,
        'account_no' => $selected_account_no,
    ]);

    $claims_query = new WP_Query($query_args);

    if (!$claims_query->have_posts()) {
        zati_redirect_warranty_download_notice(
            'no_claims',
            $year,
            $month,
            $selected_account_no
        );
    }

    $temporary_file = wp_tempnam(
        'warranty-attachments.zip'
    );

    if (!$temporary_file) {
        zati_redirect_warranty_download_notice(
            'zip_failed',
            $year,
            $month,
            $selected_account_no
        );
    }

    $zip = new ZipArchive();

    $zip_opened = $zip->open(
        $temporary_file,
        ZipArchive::CREATE | ZipArchive::OVERWRITE
    );

    if ($zip_opened !== true) {
        @unlink($temporary_file);

        zati_redirect_warranty_download_notice(
            'zip_failed',
            $year,
            $month,
            $selected_account_no
        );
    }

    $attachment_fields = [
        'purchase_receipt_attachment' =>
            'purchase-receipt',

        'service_invoice_attachment' =>
            'service-invoice',

        'shipping_in_receipt_attachment' =>
            'shipping-in-receipt',

        'shipping_out_receipt_attachment' =>
            'shipping-out-receipt',
    ];

    $file_count = 0;

    foreach ($claims_query->posts as $claim_post) {

        $claim_id = $claim_post->ID;

        $reference_no = sanitize_file_name(
            get_post_meta(
                $claim_id,
                'reference_no',
                true
            )
        );

        if ($reference_no === '') {
            $reference_no = 'claim-' . $claim_id;
        }

        foreach (
            $attachment_fields as $field_name => $file_slug
        ) {

            $attachment_id = absint(
                get_post_meta(
                    $claim_id,
                    $field_name,
                    true
                )
            );

            if (!$attachment_id) {
                continue;
            }

            $file_path = get_attached_file(
                $attachment_id
            );

            if (
                !$file_path ||
                !is_file($file_path) ||
                !is_readable($file_path)
            ) {
                continue;
            }

            $extension = strtolower(
                pathinfo(
                    $file_path,
                    PATHINFO_EXTENSION
                )
            );

            $zip_file_name =
                $reference_no
                . '/'
                . $reference_no
                . '_'
                . $file_slug;

            if ($extension !== '') {
                $zip_file_name .= '.' . $extension;
            }

            if (
                $zip->addFile(
                    $file_path,
                    $zip_file_name
                )
            ) {
                $file_count++;
            }
        }
    }

    $zip->close();
    wp_reset_postdata();

    if ($file_count === 0) {
        @unlink($temporary_file);

        zati_redirect_warranty_download_notice(
            'no_attachments',
            $year,
            $month,
            $selected_account_no
        );
    }

    if (zati_is_warranty_claim_svc_user()) {
        $filename_account =
            zati_get_current_service_center_account_no();
    } else {
        $filename_account = $selected_account_no;
    }

    if ($filename_account === '') {
        $filename_account = 'all-service-centers';
    }

    $filename = sanitize_file_name(
        'warranty-attachments_'
        . $filename_account
        . '_'
        . $year
        . '-'
        . str_pad(
            (string) $month,
            2,
            '0',
            STR_PAD_LEFT
        )
        . '.zip'
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    nocache_headers();

    header('Content-Type: application/zip');
    header(
        'Content-Disposition: attachment; filename="'
        . $filename
        . '"'
    );
    header(
        'Content-Length: '
        . filesize($temporary_file)
    );

    readfile($temporary_file);
    @unlink($temporary_file);
    exit;
}


/* =========================
   Warranty Monthly Summary Download
========================= */

add_action(
    'template_redirect',
    'zati_handle_warranty_monthly_summary_download',
    20
);

function zati_handle_warranty_monthly_summary_download() {

    $download_type = isset($_GET['zati_warranty_download'])
        ? sanitize_key(
            wp_unslash($_GET['zati_warranty_download'])
        )
        : '';

    if ($download_type !== 'monthly_summary') {
        return;
    }

    /*
     * Monthly Summary is internal only.
     * Admin / ZAC TS Staff only.
     */
    if (
        !is_user_logged_in() ||
        !zati_is_warranty_claim_ts_user()
    ) {
        wp_die(
            esc_html__(
                'You do not have permission to generate the Monthly Summary.',
                'zati'
            ),
            esc_html__('Access Denied', 'zati'),
            ['response' => 403]
        );
    }

    /*
     * Nonce check
     */
    $nonce = isset($_GET['_wpnonce'])
        ? sanitize_text_field(
            wp_unslash($_GET['_wpnonce'])
        )
        : '';

    if (
        !wp_verify_nonce(
            $nonce,
            'zati_download_warranty_monthly_summary'
        )
    ) {
        wp_die(
            esc_html__(
                'The Monthly Summary link is invalid or has expired.',
                'zati'
            ),
            esc_html__('Download Error', 'zati'),
            ['response' => 403]
        );
    }

    /*
     * Filter values
     */
    $year = isset($_GET['claim_year'])
        ? absint($_GET['claim_year'])
        : 0;

    $month = isset($_GET['claim_month'])
        ? absint($_GET['claim_month'])
        : 0;

    $selected_account_no = isset($_GET['service_center'])
        ? sanitize_text_field(
            wp_unslash($_GET['service_center'])
        )
        : '';

    /*
     * Service Center is required
     */
    if ($selected_account_no === '') {
        wp_die(
            esc_html__(
                'Please select a Service Center before generating the Monthly Summary.',
                'zati'
            ),
            esc_html__('Monthly Summary Error', 'zati'),
            ['response' => 400]
        );
    }

    /*
     * Year is required
     */
    if ($year <= 0) {
        wp_die(
            esc_html__(
                'Please select a valid year.',
                'zati'
            ),
            esc_html__('Monthly Summary Error', 'zati'),
            ['response' => 400]
        );
    }

    /*
     * Month is required
     */
    if ($month < 1 || $month > 12) {
        wp_die(
            esc_html__(
                'Please select a month before generating the Monthly Summary.',
                'zati'
            ),
            esc_html__('Monthly Summary Error', 'zati'),
            ['response' => 400]
        );
    }

    /*
     * Get claims for selected
     * Service Center + Year + Month
     */
    $query_args = zati_get_warranty_archive_query_args([
        'year'       => $year,
        'month'      => $month,
        'account_no' => $selected_account_no,
    ]);

    /*
     * Oldest claim first for Monthly Summary
     */
    $query_args['orderby'] = 'date';
    $query_args['order']   = 'ASC';

    $claims_query = new WP_Query($query_args);

    if (!$claims_query->have_posts()) {
        wp_die(
            esc_html__(
                'No warranty claims were found for the selected Service Center and period.',
                'zati'
            ),
            esc_html__('Monthly Summary Error', 'zati'),
            ['response' => 404]
        );
    }

    /*
     * Service Center information
     */
    $first_claim_id = $claims_query->posts[0]->ID;

    $service_center_name = sanitize_text_field(
        get_post_meta(
            $first_claim_id,
            'service_center',
            true
        )
    );

    $account_no = sanitize_text_field(
        get_post_meta(
            $first_claim_id,
            'account_no',
            true
        )
    );

    /*
     * File name
     */
    $filename = sanitize_file_name(
        'Warranty_Claim_'
        . $account_no
        . '_'
        . $year
        . '-'
        . str_pad(
            (string) $month,
            2,
            '0',
            STR_PAD_LEFT
        )
        . '.xlsx'
    );

    /*
 * Build Claim ID list
 */
$claim_ids = wp_list_pluck(
    $claims_query->posts,
    'ID'
);

/*
 * Make sure the Excel generator exists.
 */
if (
    !function_exists(
        'zati_create_warranty_monthly_summary_xlsx'
    )
) {
    wp_reset_postdata();

    wp_die(
        esc_html__(
            'The Monthly Summary Excel generator is not available.',
            'zati'
        ),
        esc_html__('Monthly Summary Error', 'zati'),
        ['response' => 500]
    );
}

/*
 * Generate XLSX using zati-tools plugin.
 */
$xlsx_path =
    zati_create_warranty_monthly_summary_xlsx(
        $claim_ids,
        $service_center_name,
        $account_no,
        $year,
        $month
    );

/*
 * Confirm that the file was created.
 */
if (
    $xlsx_path === '' ||
    !file_exists($xlsx_path) ||
    !is_readable($xlsx_path)
) {
    wp_reset_postdata();

    wp_die(
        esc_html__(
            'The Monthly Summary Excel file could not be created.',
            'zati'
        ),
        esc_html__('Monthly Summary Error', 'zati'),
        ['response' => 500]
    );
}

/*
 * Clear any existing output before sending XLSX.
 */
while (ob_get_level() > 0) {
    ob_end_clean();
}

nocache_headers();

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="'
    . $filename
    . '"'
);

header('Cache-Control: max-age=0');

header(
    'Content-Length: '
    . filesize($xlsx_path)
);

/*
 * Send XLSX to browser.
 */
readfile($xlsx_path);

/*
 * Remove temporary XLSX after download.
 */
@unlink($xlsx_path);

wp_reset_postdata();

exit;

}

/* =========================
   Warranty Download Notice Redirect
========================= */

function zati_redirect_warranty_download_notice(
    $notice,
    $year,
    $month,
    $account_no = ''
) {
    $url = add_query_arg(
        [
            'claim_year'     => absint($year),
            'claim_month'    => absint($month),
            'service_center' => sanitize_text_field(
                $account_no
            ),
            'download_notice' => sanitize_key($notice),
        ],
        home_url('/warranty-claim-archive/')
    );

    wp_safe_redirect($url);
    exit;
}

/* =========================
   Warranty Claim Claim code
========================= */

function zati_claim_codes() {
    return [
        '1110 No Good Taste',
  '1111 Un-Even Cook',
  '1112 Hard Rice',
  '1113 Soft (wet) Rice',
  '1114 Un-Cooked',
  '1115 Burning',
  '1116 Overflow',
  '1210 Low Keep Warm',
  '1211 Smell Bad (KW)',
  '1212 Rice get Wet (KW)',
  '1213 Rice get Dry (KW)',
  '1214 Rice Change Color',
  '1215 Not Change to KW',
  '1216 Too much Dew inside',
  '1217 Bad Retention',
  '1310 Don’t Brew',
  '1311 Keep Boiling',
  '1312 Not Dispense',
  '1313 Dispensing Little',
  '1314 Dripping after disp.',
  '1410 Not Bake/Boil',
  '1411 Burning / Over Cook',
  '1412 Not Mix',
  '1413 Not Rising',
  '1414 Can\'t Steaming',
  '1415 Can\'t Polished Rice',
  '1710 Stop at Middle',
  '1711 Can\'t set Timer',
  '1810 No Beep/Melody',
  '1811 Hard to Hear Beep',
  '1812 Too Loud Beep',
  '1998 Not Cooking *1',
  '1999 Not Keeping Warm *2',
  '2110 Display E:0-',
  '2111 Incomplete LCD',
  '2112 Fog on LCD',
  '2113 Not Tun on LED',
  '2114 Different Display',
  '2999 Other Def. Display *3',
  '3110 Hard to Open/Close',
  '3111 Can\'t Open Lid',
  '3112 Lid open Itself',
  '3210 SW Knob/Lever is Hard',
  '3211 Hard to Start',
  '3212 Can\'t Reset',
  '3213 Loud operation Noise',
  '3214 Defective Cord',
  '3215 Don\'t Retract Cord',
  '3217 Vibrate',
  '3310 No Power',
  '3999 Other Def. Movement *4',
  '4110 Gap / Un-even',
  '4111 SW Seal OFF',
  '4112 Part coming out',
  '4113 Body, Color Change',
  '4114 Dirty Pan/Liner',
  '4115 Broken Parts',
  '4116 Dirt / Scratch',
  '4117 Rust / Corrosion',
  '4118 Worn Out Rubber',
  '4119 Loose Parts',
  '4120 Coating OFF',
  '4121 Un-Cleanable Dirt',
  '4888 Shipping Damage',
  '4999 Other Def. App. *5',
  '5110 Missing Parts',
  '5111 Bad Smell',
  '5112 Getting too Hot',
  '5113 Loose parts Inside',
  '5114 Steam Leak',
  '5115 Water Leak',
  '8110 Hard to Maintenance',
  '8111 Heavy to Carrying',
  '8112 Incovenient to Storing',
  '8999 Other *6',
  '9999 Un-Known *7',
];
}

add_action('wp_ajax_zati_lookup_part', 'zati_lookup_part');
add_action('wp_ajax_nopriv_zati_lookup_part', 'zati_lookup_part');

function zati_lookup_part() {

    check_ajax_referer('zati_lookup_part_nonce', 'nonce');

    $partnumber = isset($_POST['partnumber'])
        ? sanitize_text_field(wp_unslash($_POST['partnumber']))
        : '';

    if ($partnumber === '') {
        wp_send_json_error([
            'message' => 'Please enter a part number.'
        ]);
    }

    $query = new WP_Query([
        'post_type'      => 'part',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => 'partnumber',
                'value'   => $partnumber,
                'compare' => '='
            ]
        ]
    ]);

    if (!$query->have_posts()) {
        wp_send_json_error([
            'message' => 'Part was not found.'
        ]);
    }

    $part_id = $query->posts[0]->ID;

    $description = get_field('description', $part_id);

    $distributor_price = (float) get_field(
    'distributor_price',
    $part_id
     );

    $price_context = isset($_POST['price_context'])
    ? sanitize_key(wp_unslash($_POST['price_context']))
    : 'warranty_claim';

   if ($price_context === 'parts_order') {

    $unit_price = zati_get_part_price_by_role(
        $distributor_price
    );

    } else {

    // Warranty Claim is always 82% of DIST.
    $unit_price = zati_get_svc_price(
        $distributor_price
    );
    }

    wp_send_json_success([
    'partnumber'  => $partnumber,
    'description' => $description,
    'unit_price'  => '$' . number_format(
        (float) $unit_price,
        2
    ),
]);
}



/* =========================
   ZATI Warranty Claim Helpers
========================= */

function zati_post_text($key, $default = '') {
    return isset($_POST[$key])
        ? sanitize_text_field(wp_unslash($_POST[$key]))
        : $default;
}

function zati_money_to_float($value) {
    $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
    return $value === '' ? 0 : (float) $value;
}

function zati_validate_warranty_claim($post, $files = []) {
    $errors = [];

    if (
        !isset($post['zati_warranty_claim_nonce']) ||
        !wp_verify_nonce($post['zati_warranty_claim_nonce'], 'zati_warranty_claim')
    ) {
        $errors['form'] = 'Security check failed. Please try again.';
        return $errors;
    }

    $required_fields = [
        'invoice_no'    => 'Invoice No. is required.',
        'date_in'       => 'Date In is required.',
        'date_out'      => 'Date Out is required.',
        'claim_code'    => 'Please select a claim code.',
        'model_no'      => 'Model No. is required.',
        'purchase_date' => 'Purchase Date is required.',
    ];

    foreach ($required_fields as $field => $message) {
        if (empty(trim($post[$field] ?? ''))) {
            $errors[$field] = $message;
        }
    }

   if (empty($files['purchase_receipt_attachment']['name'])) {
    $errors['purchase_receipt_attachment'] =
        'Purchase Receipt is required.';
}

$shipping_fee_in = zati_money_to_float(
    $post['shipping_fee_in'] ?? ''
);

$shipping_fee_out = zati_money_to_float(
    $post['shipping_fee_out'] ?? ''
);

$has_shipping_in_receipt = !empty(
    $files['shipping_in_receipt_attachment']['name']
);

$has_shipping_out_receipt = !empty(
    $files['shipping_out_receipt_attachment']['name']
);

/*
 * Service Invoice is optional.
 * Required validation is not added.
 */
$has_service_invoice = !empty(
    $files['service_invoice_attachment']['name']
);

if ($shipping_fee_in > 0 && !$has_shipping_in_receipt) {
    $errors['shipping_in_receipt_attachment'] =
        'Shipping In Receipt is required when Shipping Fee In is entered.';
}

if ($has_shipping_in_receipt && $shipping_fee_in <= 0) {
    $errors['shipping_fee_in'] =
        'Shipping Fee In is required when Shipping In Receipt is attached.';
}

if ($shipping_fee_out > 0 && !$has_shipping_out_receipt) {
    $errors['shipping_out_receipt_attachment'] =
        'Shipping Out Receipt is required when Shipping Fee Out is entered.';
}

if ($has_shipping_out_receipt && $shipping_fee_out <= 0) {
    $errors['shipping_fee_out'] =
        'Shipping Fee Out is required when Shipping Out Receipt is attached.';
}

if ($shipping_fee_in > 0 && $shipping_fee_out <= 0) {
    $errors['shipping_fee_out'] =
        'Shipping Fee Out is required when Shipping Fee In is entered.';
}


    return $errors;
}

function zati_render_hidden_fields($data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $i => $row) {
                if (is_array($row)) {
                    foreach ($row as $sub_key => $sub_value) {
                        printf(
                            '<input type="hidden" name="%s" value="%s">' . "\n",
                            esc_attr($key . '[' . $i . '][' . $sub_key . ']'),
                            esc_attr(sanitize_text_field(wp_unslash($sub_value)))
                        );
                    }
                }
            }
        } else {
            printf(
                '<input type="hidden" name="%s" value="%s">' . "\n",
                esc_attr($key),
                esc_attr(sanitize_text_field(wp_unslash($value)))
            );
        }
    }
}

/* =========================
   Warranty Claim Save
========================= */

function zati_clean_money($value) {
    $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
    return $value === '' ? 0 : (float) $value;
}

function zati_generate_warranty_claim_ref() {
    $year = date('Y');

    $query = new WP_Query([
        'post_type'      => 'warranty_claim',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => 'reference_no',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $next_number = 1;

    if ($query->have_posts()) {
        $query->the_post();
        $last_ref = get_field('reference_no', get_the_ID());

        if (preg_match('/WC-' . $year . '-(\d+)/', $last_ref, $matches)) {
            $next_number = ((int) $matches[1]) + 1;
        }

        wp_reset_postdata();
    }

    return 'WC-' . $year . '-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
}

function zati_build_parts_json($post) {
    $parts = [];

    if (empty($post['parts']) || !is_array($post['parts'])) {
        return wp_json_encode([]);
    }

    foreach ($post['parts'] as $part) {
        $partnumber = sanitize_text_field($part['partnumber'] ?? '');

        if ($partnumber === '') {
            continue;
        }

        $parts[] = [
            'partnumber'      => $partnumber,
            'description'     => sanitize_text_field($part['description'] ?? ''),
            'quantity'        => (int) ($part['quantity'] ?? 1),
            'unit_price'      => zati_clean_money($part['unit_price'] ?? 0),
            'extended_price'  => zati_clean_money($part['extended_price'] ?? 0),
        ];
    }

    return wp_json_encode($parts);
}

function zati_calculate_parts_total($post) {

    $total = 0;

    if (empty($post['parts']) || !is_array($post['parts'])) {
        return 0;
    }

    foreach ($post['parts'] as $part) {

        $partnumber = sanitize_text_field(
            $part['partnumber'] ?? ''
        );

        if ($partnumber === '') {
            continue;
        }

        $quantity = max(
            1,
            (int) ($part['quantity'] ?? 1)
        );

        $unit_price = zati_clean_money(
            $part['unit_price'] ?? 0
        );

        $extended_price = zati_clean_money(
            $part['extended_price'] ?? 0
        );

        if ($extended_price <= 0 && $unit_price > 0) {
            $extended_price = $quantity * $unit_price;
        }

        $total += $extended_price;
    }

    return round($total, 2);
}

function zati_save_warranty_claim($post) {
    $post = wp_unslash($post);

    $current_user = wp_get_current_user();

    /*
     * SVCの場合は、フォームから送信された値を使用せず、
     * ログインユーザーに登録された情報を使用する。
     */
    if (zati_is_warranty_claim_svc_user($current_user)) {

        $service_center_name =
            zati_get_current_service_center_name();

        $account_no =
            zati_get_current_service_center_account_no();

        /*
         * ユーザー設定が不完全な場合は保存しない
         */
        if (
            $service_center_name === '' ||
            $account_no === ''
        ) {
            return false;
        }

    } else {

        /*
         * TS／Administratorが将来代理入力する場合に備える
         */
        $service_center_name = sanitize_text_field(
            $post['service_center_name'] ?? ''
        );

        $account_no = sanitize_text_field(
            $post['account_no'] ?? ''
        );
    }

    $reference_no = zati_generate_warranty_claim_ref();
    $invoice_no = sanitize_text_field($post['invoice_no'] ?? '');
    $model_no   = sanitize_text_field($post['model_no'] ?? '');

    $title = $reference_no;

    if ($invoice_no || $model_no) {
        $title .= ' - ' . trim($invoice_no . ' ' . $model_no);
    }

    $claim_id = wp_insert_post([
    'post_type'   => 'warranty_claim',
    'post_status' => 'publish',
    'post_title'  => $title,
    'post_author' => get_current_user_id(),
    ]);
    if (is_wp_error($claim_id) || !$claim_id) {
        return false;
    }

    update_field('reference_no', $reference_no, $claim_id);
    update_field('status', 'Submitted', $claim_id);

    update_field(
    'service_center',
    $service_center_name,
    $claim_id
	);

	update_field(
    'account_no',
    $account_no,
    $claim_id
	);
    update_field('invoice_no', $invoice_no, $claim_id);
    update_field('model_no', $model_no, $claim_id);
    update_field(
    'lot_no',
    sanitize_text_field($post['lot_no'] ?? ''),
    $claim_id
     );
    update_field('claim_code', sanitize_text_field($post['claim_code'] ?? ''), $claim_id);

    update_field('date_in', sanitize_text_field($post['date_in'] ?? ''), $claim_id);
    update_field('date_out', sanitize_text_field($post['date_out'] ?? ''), $claim_id);
    update_field('purchase_date', sanitize_text_field($post['purchase_date'] ?? ''), $claim_id);
    update_field('store_name', sanitize_text_field($post['store_name'] ?? ''), $claim_id);

$labor = zati_clean_money(
    $post['labor_total']
        ?? $post['labor']
        ?? 0
);

$parts_total = zati_calculate_parts_total($post);

$shipping_fee_in = zati_clean_money(
    $post['shipping_fee_in_total']
        ?? $post['shipping_fee_in']
        ?? 0
);

$shipping_fee_out = zati_clean_money(
    $post['shipping_fee_out_total']
        ?? $post['shipping_fee_out']
        ?? 0
);

$stocking_fee = round($parts_total * 0.10, 2);

$total_payment = round(
    $parts_total
    + $stocking_fee
    + $labor
    + $shipping_fee_in
    + $shipping_fee_out,
    2
);

update_field('labor', $labor, $claim_id);
update_field('parts_total', $parts_total, $claim_id);
update_field('shipping_fee_in', $shipping_fee_in, $claim_id);
update_field('shipping_fee_out', $shipping_fee_out, $claim_id);
update_field('stocking_fee', $stocking_fee, $claim_id);
update_field('total_payment', $total_payment, $claim_id);
    update_field('parts_json', zati_build_parts_json($post), $claim_id);

    return [
        'claim_id'     => $claim_id,
        'reference_no' => $reference_no,
    ];
}

/* =========================
   Warranty Claim Body Classes
========================= */

add_filter('body_class', function ($classes) {

    if (is_page('warranty-claim-review')) {
        $classes[] = 'zati-warranty-review-body';
    }

    if (is_page('warranty-claim-complete')) {
        $classes[] = 'zati-warranty-complete-body';
    }

    if (is_page('warranty-claim-archive')) {
        $classes[] = 'zati-warranty-archive-body';
    }

    if (is_page('warranty-claim-detail')) {
        $classes[] = 'zati-warranty-detail-body';
    }
    if (is_page('svc-form')) {
    $classes[] = 'zati-forms-hub-body';
    }

    return $classes;
});

/* =========================
   Warranty Claim file archive
========================= */
function zati_handle_temp_upload($file_key) {

    if (
        empty($_FILES[$file_key]) ||
        empty($_FILES[$file_key]['name']) ||
        !empty($_FILES[$file_key]['error'])
    ) {
        return null;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $uploaded = wp_handle_upload(
        $_FILES[$file_key],
        [
            'test_form' => false,
        ]
    );

    if (
        isset($uploaded['error']) ||
        empty($uploaded['file']) ||
        empty($uploaded['url'])
    ) {
        return null;
    }

    return [
        'file' => $uploaded['file'],
        'url'  => $uploaded['url'],
        'type' => $uploaded['type'] ?? '',
        'name' => sanitize_file_name($_FILES[$file_key]['name']),
    ];
}

/* =========================
   Warranty Attachment Labels
========================= */

function zati_get_warranty_attachment_label($field_name) {

    $labels = [
        'purchase_receipt_attachment'     => 'Purchase Receipt',
        'service_invoice_attachment'      => 'Service Invoice',
        'shipping_in_receipt_attachment'  => 'Shipping In Receipt',
        'shipping_out_receipt_attachment' => 'Shipping Out Receipt',
    ];

    return $labels[$field_name] ?? 'Warranty Attachment';
}

function zati_get_warranty_attachment_slug($field_name) {

    $slugs = [
        'purchase_receipt_attachment'     => 'purchase-receipt',
        'service_invoice_attachment'      => 'service-invoice',
        'shipping_in_receipt_attachment'  => 'shipping-in-receipt',
        'shipping_out_receipt_attachment' => 'shipping-out-receipt',
    ];

    return $slugs[$field_name] ?? 'attachment';
}


/* =========================
   Register Warranty Attachment
========================= */

function zati_register_warranty_attachment(
    $upload,
    $claim_id,
    $reference_no,
    $field_name
) {
    if (
        empty($upload) ||
        !is_array($upload) ||
        empty($upload['file']) ||
        !$claim_id
    ) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $uploads = wp_upload_dir();

    if (!empty($uploads['error'])) {
        return new WP_Error(
            'zati_upload_directory_error',
            $uploads['error']
        );
    }

    $original_path = wp_normalize_path($upload['file']);
    $uploads_base  = wp_normalize_path(realpath($uploads['basedir']));
    $real_path     = realpath($original_path);

    if (!$real_path || !$uploads_base) {
        return new WP_Error(
            'zati_attachment_path_error',
            'The uploaded attachment path could not be resolved.'
        );
    }

    $real_path = wp_normalize_path($real_path);

    /*
     * uploadsディレクトリ外のファイルを登録させない
     */
    if (
        strpos(
            $real_path,
            trailingslashit($uploads_base)
        ) !== 0
    ) {
        return new WP_Error(
            'zati_invalid_attachment_path',
            'The attachment path is outside the uploads directory.'
        );
    }

    $file_type = wp_check_filetype(
        basename($real_path),
        null
    );

    $allowed_mime_types = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    if (
        empty($file_type['ext']) ||
        empty($file_type['type']) ||
        !in_array($file_type['type'], $allowed_mime_types, true)
    ) {
        return new WP_Error(
            'zati_disallowed_attachment_type',
            'Only PDF, JPG, JPEG, and PNG files are allowed.'
        );
    }

    $attachment_label = zati_get_warranty_attachment_label(
        $field_name
    );

    $attachment_slug = zati_get_warranty_attachment_slug(
        $field_name
    );

    /*
     * 例:
     * WC-2026-000015_purchase-receipt.pdf
     */
    $desired_filename =
        sanitize_file_name($reference_no) .
        '_' .
        sanitize_file_name($attachment_slug) .
        '.' .
        strtolower($file_type['ext']);

    $directory = dirname($real_path);

    /*
     * 同名ファイルがある場合は -1, -2 を自動付与
     */
    $unique_filename = wp_unique_filename(
        $directory,
        $desired_filename
    );

    $new_path = wp_normalize_path(
        trailingslashit($directory) . $unique_filename
    );

    /*
     * 一時アップロード時の名前から正式名へ変更
     */
    if ($real_path !== $new_path) {
        if (!rename($real_path, $new_path)) {
            return new WP_Error(
                'zati_attachment_rename_failed',
                'The uploaded attachment could not be renamed.'
            );
        }
    }

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

    $service_center = get_post_meta(
        $claim_id,
        'service_center',
        true
    );

    $account_no = get_post_meta(
        $claim_id,
        'account_no',
        true
    );

    $description_lines = [
        'Warranty Claim: ' . $reference_no,
        'Attachment Type: ' . $attachment_label,
    ];

    if ($service_center !== '') {
        $description_lines[] =
            'Service Center: ' . $service_center;
    }

    if ($account_no !== '') {
        $description_lines[] =
            'Account No.: ' . $account_no;
    }

    if ($invoice_no !== '') {
        $description_lines[] =
            'Invoice No.: ' . $invoice_no;
    }

    if ($model_no !== '') {
        $description_lines[] =
            'Model: ' . $model_no;
    }

    $attachment_post = [
        'post_mime_type' => $file_type['type'],
        'post_title'     =>
            $reference_no . ' - ' . $attachment_label,
        'post_content'   => implode(
            "\n",
            $description_lines
        ),
        'post_excerpt'   =>
            $reference_no . ' - ' . $attachment_label,
        'post_status'    => 'inherit',
        'post_parent'    => (int) $claim_id,
        'post_author'    => get_current_user_id(),
    ];

    $attachment_id = wp_insert_attachment(
        $attachment_post,
        $new_path,
        $claim_id,
        true
    );

    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }

    $metadata = wp_generate_attachment_metadata(
        $attachment_id,
        $new_path
    );

    if (!is_wp_error($metadata) && !empty($metadata)) {
        wp_update_attachment_metadata(
            $attachment_id,
            $metadata
        );
    }

    /*
     * Media Libraryで識別するための独自メタ
     */
    update_post_meta(
        $attachment_id,
        '_zati_warranty_claim_id',
        (int) $claim_id
    );

    update_post_meta(
        $attachment_id,
        '_zati_warranty_reference',
        sanitize_text_field($reference_no)
    );

    update_post_meta(
        $attachment_id,
        '_zati_warranty_attachment_type',
        sanitize_key($field_name)
    );

    return (int) $attachment_id;
}


/* =========================
   Save Warranty Attachments
========================= */

function zati_save_warranty_attachments(
    $claim_id,
    $reference_no,
    $temp_uploads
) {
    if (
        !$claim_id ||
        $reference_no === '' ||
        empty($temp_uploads) ||
        !is_array($temp_uploads)
    ) {
        return [
            'saved'  => [],
            'errors' => [],
        ];
    }

    $attachment_fields = [
        'purchase_receipt_attachment',
        'service_invoice_attachment',
        'shipping_in_receipt_attachment',
        'shipping_out_receipt_attachment',
    ];

    $saved_attachments = [];
    $errors = [];

    foreach ($attachment_fields as $field_name) {

        if (empty($temp_uploads[$field_name])) {
            continue;
        }

        $attachment_id = zati_register_warranty_attachment(
            $temp_uploads[$field_name],
            $claim_id,
            $reference_no,
            $field_name
        );

        if (is_wp_error($attachment_id)) {
            $errors[$field_name] = $attachment_id;
            continue;
        }

        if (!$attachment_id) {
            continue;
        }

        update_field(
            $field_name,
            $attachment_id,
            $claim_id
        );

        $saved_attachments[$field_name] = $attachment_id;
    }

    return [
        'saved'  => $saved_attachments,
        'errors' => $errors,
    ];
}

/* =========================
   Get Attachment URL
========================= */

function zati_get_attachment_url_from_field(
    $field_name,
    $post_id
) {
    $value = get_field($field_name, $post_id);

    if (empty($value)) {
        return '';
    }

    /*
     * ACF Return Format: File ID
     */
    if (is_numeric($value)) {
        $url = wp_get_attachment_url((int) $value);
        return $url ? $url : '';
    }

    /*
     * ACF Return Format: File Array
     */
    if (is_array($value)) {

        if (!empty($value['url'])) {
            return esc_url_raw($value['url']);
        }

        if (!empty($value['ID'])) {
            $url = wp_get_attachment_url((int) $value['ID']);
            return $url ? $url : '';
        }

        if (!empty($value['id'])) {
            $url = wp_get_attachment_url((int) $value['id']);
            return $url ? $url : '';
        }
    }

    /*
     * ACF Return Format: File URL
     */
    if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
        return esc_url_raw($value);
    }

    return '';
}

/* =========================
   Media Library Columns
========================= */

add_filter(
    'manage_upload_columns',
    function ($columns) {

        $columns['zati_warranty_reference'] =
            'Warranty Claim';

        $columns['zati_attachment_type'] =
            'Attachment Type';

        return $columns;
    }
);

add_action(
    'manage_media_custom_column',
    function ($column_name, $attachment_id) {

        if ($column_name === 'zati_warranty_reference') {

            $reference_no = get_post_meta(
                $attachment_id,
                '_zati_warranty_reference',
                true
            );

            $claim_id = (int) get_post_meta(
                $attachment_id,
                '_zati_warranty_claim_id',
                true
            );

            if ($reference_no === '') {
                echo '—';
                return;
            }

            if (
                $claim_id &&
                get_post_type($claim_id) === 'warranty_claim'
            ) {
                $edit_url = get_edit_post_link($claim_id);

                if ($edit_url) {
                    printf(
                        '<a href="%s">%s</a>',
                        esc_url($edit_url),
                        esc_html($reference_no)
                    );
                    return;
                }
            }

            echo esc_html($reference_no);
        }

        if ($column_name === 'zati_attachment_type') {

            $field_name = get_post_meta(
                $attachment_id,
                '_zati_warranty_attachment_type',
                true
            );

            if ($field_name === '') {
                echo '—';
                return;
            }

            echo esc_html(
                zati_get_warranty_attachment_label(
                    $field_name
                )
            );
        }
    },
    10,
    2
);

/* =========================================
   ZATI Notification Settings
========================================= */

/**
 * Return the notification settings with defaults.
 */
function zati_get_notification_settings() {

   $defaults = [
    'ts_enabled'               => 1,
    'ts_to'                    => '',
    'ts_cc'                    => '',

    'parts_us_canada_to'       => '',
    'parts_us_canada_cc'       => '',

    'parts_mexico_to'          => '',
    'parts_mexico_cc'          => '',

    'attachment_limit_mb'      => 15,
    'svc_confirmation_enabled' => 1,
         ];

    $settings = get_option(
        'zati_notification_settings',
        []
    );

    if (!is_array($settings)) {
        $settings = [];
    }

    return wp_parse_args($settings, $defaults);
}


/**
 * Convert an email list into validated email addresses.
 *
 * The administrator may enter:
 * - One address per line
 * - Comma-separated addresses
 * - Semicolon-separated addresses
 */
function zati_parse_notification_email_list($value) {

    if (is_array($value)) {
        $value = implode("\n", $value);
    }

    $addresses = preg_split(
        '/[\r\n,;]+/',
        (string) $value
    );

    $valid_addresses = [];

    foreach ($addresses as $address) {

        $address = sanitize_email(trim($address));

        if ($address !== '' && is_email($address)) {
            $valid_addresses[] = $address;
        }
    }

    return array_values(array_unique($valid_addresses));
}


/**
 * Sanitize notification settings before saving.
 */
function zati_sanitize_notification_settings($input) {

    if (!is_array($input)) {
        $input = [];
    }

    $ts_to = isset($input['ts_to'])
        ? zati_parse_notification_email_list(
            wp_unslash($input['ts_to'])
        )
        : [];

    $ts_cc = isset($input['ts_cc'])
        ? zati_parse_notification_email_list(
            wp_unslash($input['ts_cc'])
        )
        : [];
       $parts_us_canada_to =
        isset($input['parts_us_canada_to'])
        ? zati_parse_notification_email_list(
            wp_unslash($input['parts_us_canada_to'])
        )
        : [];

       $parts_us_canada_cc =
       isset($input['parts_us_canada_cc'])
        ? zati_parse_notification_email_list(
            wp_unslash($input['parts_us_canada_cc'])
        )
        : [];

       $parts_mexico_to =
         isset($input['parts_mexico_to'])
        ? zati_parse_notification_email_list(
            wp_unslash($input['parts_mexico_to'])
        )
        : [];

       $parts_mexico_cc =
        isset($input['parts_mexico_cc'])
        ? zati_parse_notification_email_list(
            wp_unslash($input['parts_mexico_cc'])
        )
        : [];

    $attachment_limit = isset($input['attachment_limit_mb'])
        ? absint($input['attachment_limit_mb'])
        : 15;

    /*
     * Keep the setting within a reasonable range.
     */
    $attachment_limit = max(
        1,
        min(50, $attachment_limit)
    );

    return [
        'ts_enabled' => !empty($input['ts_enabled']) ? 1 : 0,

        /*
         * Store one address per line.
         */
        'ts_to' => implode("\n", $ts_to),
        'ts_cc' => implode("\n", $ts_cc),

                'parts_us_canada_to' =>
               implode("\n", $parts_us_canada_to),

                'parts_us_canada_cc' =>
                 implode("\n", $parts_us_canada_cc),

                 'parts_mexico_to' =>
                 implode("\n", $parts_mexico_to),

                  'parts_mexico_cc' =>
                  implode("\n", $parts_mexico_cc),

        'attachment_limit_mb' => $attachment_limit,

        'svc_confirmation_enabled' =>
            !empty($input['svc_confirmation_enabled']) ? 1 : 0,
    ];
}


/**
 * Register the WordPress option.
 */
function zati_register_notification_settings() {

    register_setting(
        'zati_notification_settings_group',
        'zati_notification_settings',
        [
            'type'              => 'array',
            'sanitize_callback' => 'zati_sanitize_notification_settings',
            'default'           => [],
        ]
    );
}
add_action(
    'admin_init',
    'zati_register_notification_settings'
);


/**
 * Add the settings page under Settings.
 */
function zati_add_notification_settings_page() {

    add_options_page(
        'ZATI Notifications',
        'ZATI Notifications',
        'manage_options',
        'zati-notifications',
        'zati_render_notification_settings_page'
    );
}
add_action(
    'admin_menu',
    'zati_add_notification_settings_page'
);


/**
 * Render notification settings page.
 */
function zati_render_notification_settings_page() {

    if (!current_user_can('manage_options')) {
        wp_die(
            esc_html__(
                'You do not have permission to access this page.',
                'zati'
            )
        );
    }

    $settings = zati_get_notification_settings();
    ?>

    <div class="wrap">

        <h1>ZATI Notifications</h1>

        <p>
            Configure email notifications for Warranty Claims.
            These settings can also be reused for future ZATI modules.
        </p>

        <form method="post" action="options.php">

            <?php
            settings_fields(
                'zati_notification_settings_group'
            );
            ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        TS Notification
                    </th>

                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="zati_notification_settings[ts_enabled]"
                                value="1"
                                <?php checked(
                                    !empty($settings['ts_enabled'])
                                ); ?>
                            >
                            Send new Warranty Claim notifications
                            to ZAC TS
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="zati-ts-to">
                            TS To
                        </label>
                    </th>

                    <td>
                        <textarea
                            id="zati-ts-to"
                            name="zati_notification_settings[ts_to]"
                            rows="5"
                            class="large-text code"
                            placeholder="ts@example.com"
                        ><?php
                            echo esc_textarea($settings['ts_to']);
                        ?></textarea>

                        <p class="description">
                            Enter one email address per line.
                            Multiple addresses are supported.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="zati-ts-cc">
                            TS CC
                        </label>
                    </th>

                    <td>
                        <textarea
                            id="zati-ts-cc"
                            name="zati_notification_settings[ts_cc]"
                            rows="5"
                            class="large-text code"
                            placeholder="manager@example.com"
                        ><?php
                            echo esc_textarea($settings['ts_cc']);
                        ?></textarea>

                        <p class="description">
                            Optional. Enter one email address per line.
                        </p>
                    </td>
                </tr>

<tr>
    <th scope="row" colspan="2">
        <h2>Parts Order - US & Canada</h2>
    </th>
</tr>

<tr>
    <th scope="row">
        <label for="zati-parts-us-canada-to">
            To
        </label>
    </th>

    <td>
        <textarea
            id="zati-parts-us-canada-to"
            name="zati_notification_settings[parts_us_canada_to]"
            rows="4"
            class="large-text code"
            placeholder="parts@example.com"
        ><?php
            echo esc_textarea(
                $settings['parts_us_canada_to']
            );
        ?></textarea>

        <p class="description">
            Parts Order recipients for US & Canada SVC.
            Enter one email address per line.
        </p>
    </td>
</tr>

<tr>
    <th scope="row">
        <label for="zati-parts-us-canada-cc">
            CC
        </label>
    </th>

    <td>
        <textarea
            id="zati-parts-us-canada-cc"
            name="zati_notification_settings[parts_us_canada_cc]"
            rows="3"
            class="large-text code"
        ><?php
            echo esc_textarea(
                $settings['parts_us_canada_cc']
            );
        ?></textarea>
    </td>
</tr>

<tr>
    <th scope="row" colspan="2">
        <h2>Parts Order - Mexico</h2>
    </th>
</tr>

<tr>
    <th scope="row">
        <label for="zati-parts-mexico-to">
            To
        </label>
    </th>

    <td>
        <textarea
            id="zati-parts-mexico-to"
            name="zati_notification_settings[parts_mexico_to]"
            rows="4"
            class="large-text code"
            placeholder="mexico-parts@example.com"
        ><?php
            echo esc_textarea(
                $settings['parts_mexico_to']
            );
        ?></textarea>

        <p class="description">
            Parts Order recipients for Mexico SVC.
            Enter one email address per line.
        </p>
    </td>
</tr>

<tr>
    <th scope="row">
        <label for="zati-parts-mexico-cc">
            CC
        </label>
    </th>

    <td>
        <textarea
            id="zati-parts-mexico-cc"
            name="zati_notification_settings[parts_mexico_cc]"
            rows="3"
            class="large-text code"
        ><?php
            echo esc_textarea(
                $settings['parts_mexico_cc']
            );
        ?></textarea>
    </td>
</tr>

                <tr>
                    <th scope="row">
                        <label for="zati-attachment-limit">
                            Attachment Limit
                        </label>
                    </th>

                    <td>
                        <input
                            id="zati-attachment-limit"
                            type="number"
                            name="zati_notification_settings[attachment_limit_mb]"
                            value="<?php
                                echo esc_attr(
                                    $settings['attachment_limit_mb']
                                );
                            ?>"
                            min="1"
                            max="50"
                            step="1"
                            class="small-text"
                        >
                        MB

                        <p class="description">
                            Maximum combined attachment size for one
                            TS notification. Recommended: 15 MB.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        SVC Confirmation
                    </th>

                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="zati_notification_settings[svc_confirmation_enabled]"
                                value="1"
                                <?php checked(
                                    !empty(
                                        $settings[
                                            'svc_confirmation_enabled'
                                        ]
                                    )
                                ); ?>
                            >
                            Send a confirmation email to the
                            submitting SVC user
                        </label>

                        <p class="description">
                            The confirmation will be sent to the email
                            address registered in the WordPress user
                            account.
                        </p>
                    </td>
                </tr>

            </table>

            <?php submit_button('Save Notification Settings'); ?>

        </form>

    </div>

    <?php
}


/**
 * Get validated TS "To" recipients.
 */
function zati_get_ts_notification_recipients() {

    $settings = zati_get_notification_settings();

    return zati_parse_notification_email_list(
        $settings['ts_to']
    );
}


/**
 * Get validated TS CC recipients.
 */
function zati_get_ts_notification_cc_recipients() {

    $settings = zati_get_notification_settings();

    return zati_parse_notification_email_list(
        $settings['ts_cc']
    );
}


/* =========================================
   Parts Order Notification Recipients
   ========================================= */

function zati_get_parts_order_notification_recipients($user = null) {

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    $roles = (array) $user->roles;
    $settings = zati_get_notification_settings();

    if (in_array('mexico_svc', $roles, true)) {

        return zati_parse_notification_email_list(
            $settings['parts_mexico_to']
        );
    }

    if (in_array('us_canada_svc', $roles, true)) {

        return zati_parse_notification_email_list(
            $settings['parts_us_canada_to']
        );
    }

    return [];
}


function zati_get_parts_order_notification_cc_recipients($user = null) {

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    $roles = (array) $user->roles;
    $settings = zati_get_notification_settings();

    if (in_array('mexico_svc', $roles, true)) {

        return zati_parse_notification_email_list(
            $settings['parts_mexico_cc']
        );
    }

    if (in_array('us_canada_svc', $roles, true)) {

        return zati_parse_notification_email_list(
            $settings['parts_us_canada_cc']
        );
    }

    return [];
}

/* =========================================
   Warranty Claim Worksheet CSV
========================================= */

/**
 * Format a stored date for the existing worksheet.
 */
function zati_format_warranty_csv_date($value) {

    $value = sanitize_text_field((string) $value);

    if ($value === '') {
        return '';
    }

    $date = DateTime::createFromFormat(
        'Y-m-d',
        $value
    );

    if ($date instanceof DateTime) {
        return $date->format('m/d/Y');
    }

    return $value;
}


/**
 * Prevent spreadsheet formula execution.
 */
function zati_safe_warranty_csv_value($value) {

    $value = (string) $value;

    if (
        $value !== '' &&
        preg_match('/^[=\-+@]/', $value)
    ) {
        return "'" . $value;
    }

    return $value;
}


/**
 * Build one Warranty Claim worksheet row.
 *
 * Used by:
 * - TS notification CSV
 * - Monthly Summary Excel
 */
function zati_get_warranty_claim_worksheet_row($claim_id) {

    $claim_id = absint($claim_id);

    if (
        !$claim_id ||
        get_post_type($claim_id) !== 'warranty_claim'
    ) {
        return [];
    }

    $date_in = zati_format_warranty_csv_date(
        get_post_meta($claim_id, 'date_in', true)
    );

    $date_out = zati_format_warranty_csv_date(
        get_post_meta($claim_id, 'date_out', true)
    );

    $invoice_no = sanitize_text_field(
        get_post_meta($claim_id, 'invoice_no', true)
    );

    $model_no = sanitize_text_field(
        get_post_meta($claim_id, 'model_no', true)
    );

    $lot_no = sanitize_text_field(
        get_post_meta($claim_id, 'lot_no', true)
    );

    $purchase_date = zati_format_warranty_csv_date(
        get_post_meta($claim_id, 'purchase_date', true)
    );

    /*
     * Claim Code / Problem
     */
    $claim_code_value = sanitize_text_field(
        get_post_meta($claim_id, 'claim_code', true)
    );

    $claim_number  = '';
    $claim_problem = $claim_code_value;

    if (
        preg_match(
            '/^\s*(\d{4})\s*(.*)$/u',
            $claim_code_value,
            $claim_matches
        )
    ) {
        $claim_number  = $claim_matches[1];
        $claim_problem = trim($claim_matches[2]);
    }

    /*
     * Amounts
     */
    $parts_total = (float) get_post_meta(
        $claim_id,
        'parts_total',
        true
    );

    $stocking_fee = (float) get_post_meta(
        $claim_id,
        'stocking_fee',
        true
    );

    $labor = (float) get_post_meta(
        $claim_id,
        'labor',
        true
    );

    $shipping_in = (float) get_post_meta(
        $claim_id,
        'shipping_fee_in',
        true
    );

    $shipping_out = (float) get_post_meta(
        $claim_id,
        'shipping_fee_out',
        true
    );

    $shipping_total = round(
        $shipping_in + $shipping_out,
        2
    );

    $claim_subtotal = round(
        $parts_total + $stocking_fee + $labor,
        2
    );

    $total_payment = (float) get_post_meta(
        $claim_id,
        'total_payment',
        true
    );

    /*
     * Delivery Type
     */
    $delivery_type = (
        $shipping_in > 0 ||
        $shipping_out > 0
    )
        ? 'UP'
        : 'WC';

    /*
     * Warranty
     */
    $purchase_receipt_id = (int) get_field(
        'purchase_receipt_attachment',
        $claim_id
    );

    $warranty_value = $purchase_receipt_id > 0
        ? 'Y'
        : 'N';

    /*
     * Parts Used
     */
    $parts_json = get_post_meta(
        $claim_id,
        'parts_json',
        true
    );

    $parts = json_decode($parts_json, true);

    $part_numbers = [];
    $unit_prices  = [];

    if (is_array($parts)) {

        foreach ($parts as $part) {

            $part_number = sanitize_text_field(
                $part['partnumber'] ?? ''
            );

            if ($part_number === '') {
                continue;
            }

            $quantity = max(
                1,
                absint($part['quantity'] ?? 1)
            );

            $part_numbers[] = $quantity > 1
                ? $part_number . ' x' . $quantity
                : $part_number;

            $unit_prices[] = number_format(
                (float) ($part['unit_price'] ?? 0),
                2,
                '.',
                ''
            );
        }
    }

    $parts_used_value = implode(
        '; ',
        $part_numbers
    );

    $parts_price_value = implode(
        '; ',
        $unit_prices
    );

    return [
        $date_in,
        $date_out,
        $invoice_no,
        $model_no,
        $lot_no,
        $parts_used_value,
        $parts_price_value,
        $parts_total,
        $stocking_fee,
        $labor,
        $claim_subtotal,
        $shipping_in,
        $shipping_out,
        $shipping_total,
        $total_payment,
        $delivery_type,
        $warranty_value,
        $claim_number,
        $claim_problem,
        $purchase_date,
    ];
}


/**
 * Create a one-row CSV matching the existing TS worksheet.
 *
 * No header row is included.
 */
function zati_create_warranty_claim_email_csv($claim_id) {

    $claim_id = absint($claim_id);

    if (
        !$claim_id ||
        get_post_type($claim_id) !== 'warranty_claim'
    ) {
        return '';
    }

    $reference_no = sanitize_text_field(
        get_post_meta($claim_id, 'reference_no', true)
    );

    $date_in = zati_format_warranty_csv_date(
        get_post_meta($claim_id, 'date_in', true)
    );

    $date_out = zati_format_warranty_csv_date(
        get_post_meta($claim_id, 'date_out', true)
    );

    $invoice_no = sanitize_text_field(
        get_post_meta($claim_id, 'invoice_no', true)
    );

    $model_no = sanitize_text_field(
        get_post_meta($claim_id, 'model_no', true)
    );

    $lot_no = sanitize_text_field(
        get_post_meta($claim_id, 'lot_no', true)
    );

    $purchase_date = zati_format_warranty_csv_date(
        get_post_meta($claim_id, 'purchase_date', true)
    );

    $claim_code_value = sanitize_text_field(
        get_post_meta($claim_id, 'claim_code', true)
    );

    /*
     * Separate "1110 No Good Taste" into:
     * Claim Code = 1110
     * Problem    = No Good Taste
     */
    $claim_number = '';
    $claim_problem = $claim_code_value;

    if (
        preg_match(
            '/^\s*(\d{4})\s*(.*)$/u',
            $claim_code_value,
            $claim_matches
        )
    ) {
        $claim_number = $claim_matches[1];
        $claim_problem = trim($claim_matches[2]);
    }

    $parts_total = (float) get_post_meta(
        $claim_id,
        'parts_total',
        true
    );

    $stocking_fee = (float) get_post_meta(
        $claim_id,
        'stocking_fee',
        true
    );

    $labor = (float) get_post_meta(
        $claim_id,
        'labor',
        true
    );

    $shipping_in = (float) get_post_meta(
        $claim_id,
        'shipping_fee_in',
        true
    );

    $shipping_out = (float) get_post_meta(
        $claim_id,
        'shipping_fee_out',
        true
    );

    $shipping_total = round(
        $shipping_in + $shipping_out,
        2
    );
	/*
 	* Delivery Type:
 	* UP = Shipping was used
 	* WC = Will Call
 	*/
	$delivery_type = (
   	 $shipping_in > 0 ||
    	$shipping_out > 0
	)
    	? 'UP'
    	: 'WC';


	/*
 	* Warranty:
 	* Y = Purchase Receipt is registered
 	* N = No Purchase Receipt
 	*/
	$purchase_receipt_id = (int) get_field(
   	 'purchase_receipt_attachment',
    	$claim_id
	);

	$warranty_value = $purchase_receipt_id > 0
    	? 'Y'
    	: 'N';

    	/*
    	 * Worksheet "Total" before shipping.
     	*/
   	 $claim_subtotal = round(
        $parts_total + $stocking_fee + $labor,
        2
    	);

   	 $total_payment = (float) get_post_meta(
        $claim_id,
        'total_payment',
        true
   	 );

    /* =====================================
       Parts Used
    ===================================== */

    $parts_json = get_post_meta(
        $claim_id,
        'parts_json',
        true
    );

    $parts = json_decode($parts_json, true);

    $part_numbers = [];
    $unit_prices = [];

    if (is_array($parts)) {

        foreach ($parts as $part) {

            $part_number = sanitize_text_field(
                $part['partnumber'] ?? ''
            );

            if ($part_number === '') {
                continue;
            }

            $quantity = max(
                1,
                absint($part['quantity'] ?? 1)
            );

            if ($quantity > 1) {
                $part_numbers[] =
                    $part_number . ' x' . $quantity;
            } else {
                $part_numbers[] = $part_number;
            }

            $unit_prices[] = number_format(
                (float) ($part['unit_price'] ?? 0),
                2,
                '.',
                ''
            );
        }
    }

    $parts_used_value = implode(
        '; ',
        $part_numbers
    );

    $parts_price_value = implode(
        '; ',
        $unit_prices
    );

    /* =====================================
       Create Temporary CSV
    ===================================== */

    $csv_filename = sanitize_file_name(
        ($reference_no ?: 'warranty-claim') .
        '_worksheet.csv'
    );

    $csv_path = trailingslashit(
        get_temp_dir()
    ) . $csv_filename;

    $csv_handle = fopen($csv_path, 'w');

    if ($csv_handle === false) {
        return '';
    }

    /*
     * Same order as the existing TS worksheet.
     * There is intentionally no header row.
     */
    $csv_row = [
        $date_in,                                       // Date IN
        $date_out,                                      // Date OUT
        $invoice_no,                                    // Invoice #
        $model_no,                                      // Model #
        $lot_no,                                        // Lot #
        $parts_used_value,                              // Parts Used
        $parts_price_value,                             // Parts Price
        number_format($parts_total, 2, '.', ''),        // Parts Total
        number_format($stocking_fee, 2, '.', ''),       // Stocking Fee
        number_format($labor, 2, '.', ''),              // Labor
        number_format($claim_subtotal, 2, '.', ''),     // Total
        number_format($shipping_in, 2, '.', ''),        // Shipping IN
        number_format($shipping_out, 2, '.', ''),       // Shipping OUT
        number_format($shipping_total, 2, '.', ''),     // Shipping Total
        number_format($total_payment, 2, '.', ''),      // Total Payment
        $delivery_type,                                 // Type: UP or WC
                $warranty_value,                                // Warranty: Y or N       
               $claim_number,                                  // Claim Code
        $claim_problem,                                 // Problem
        $purchase_date,                                 // Date Purchased
    ];

    $csv_row = array_map(
        'zati_safe_warranty_csv_value',
        $csv_row
    );

    fputcsv($csv_handle, $csv_row);

    fclose($csv_handle);

    if (
        !file_exists($csv_path) ||
        !is_readable($csv_path)
    ) {
        return '';
    }

    return $csv_path;
}


/* =========================================
   Warranty Claim Email Notifications
========================================= */

/**
 * Get local attachment paths registered to a Warranty Claim.
 */
function zati_get_warranty_claim_email_attachments($claim_id) {

    $claim_id = absint($claim_id);

    if (!$claim_id) {
        return [];
    }

    $attachment_fields = [
        'purchase_receipt_attachment',
        'service_invoice_attachment',
        'shipping_in_receipt_attachment',
        'shipping_out_receipt_attachment',
    ];

    $attachments = [];

    foreach ($attachment_fields as $field_name) {

        /*
         * Warranty Claim ACF fields store Media Library IDs.
         */
        $attachment_id = (int) get_field(
            $field_name,
            $claim_id
        );

        if (!$attachment_id) {
            continue;
        }

        $file_path = get_attached_file($attachment_id);

        if (
            !$file_path ||
            !is_string($file_path) ||
            !file_exists($file_path) ||
            !is_readable($file_path)
        ) {
            continue;
        }

        $attachments[] = $file_path;
    }

    return array_values(array_unique($attachments));
}


/**
 * Calculate the combined size of email attachments.
 */
function zati_get_attachment_paths_total_size($attachments) {

    $total_size = 0;

    foreach ((array) $attachments as $file_path) {

        if (
            is_string($file_path) &&
            file_exists($file_path)
        ) {
            $file_size = filesize($file_path);

            if ($file_size !== false) {
                $total_size += (int) $file_size;
            }
        }
    }

    return $total_size;
}


/**
 * Build the Warranty Claim Detail URL.
 */
function zati_get_warranty_claim_detail_url($claim_id) {

    return add_query_arg(
        'claim_id',
        absint($claim_id),
        home_url('/warranty-claim-detail/')
    );
}


/**
 * Send notifications after a Warranty Claim has been saved.
 *
 * Email failures do not cancel or delete the Warranty Claim.
 */
function zati_send_warranty_claim_notifications($claim_id) {

    $claim_id = absint($claim_id);

    if (
        !$claim_id ||
        get_post_type($claim_id) !== 'warranty_claim'
    ) {
        return [
            'ts_sent'  => false,
            'svc_sent' => false,
        ];
    }

    /*
     * Prevent duplicate notification attempts.
     */
    if (
        get_post_meta(
            $claim_id,
            '_zati_notification_processed',
            true
        )
    ) {
        return [
            'ts_sent'  => (
                get_post_meta(
                    $claim_id,
                    '_zati_ts_email_sent',
                    true
                ) === '1'
            ),
            'svc_sent' => (
                get_post_meta(
                    $claim_id,
                    '_zati_svc_email_sent',
                    true
                ) === '1'
            ),
        ];
    }

    /*
     * Lock this claim before wp_mail() is called.
     */
    add_post_meta(
        $claim_id,
        '_zati_notification_processed',
        current_time('mysql'),
        true
    );

    $settings = zati_get_notification_settings();

    $reference_no = sanitize_text_field(
        get_post_meta($claim_id, 'reference_no', true)
    );

    $service_center = sanitize_text_field(
        get_post_meta($claim_id, 'service_center', true)
    );

    $account_no = sanitize_text_field(
        get_post_meta($claim_id, 'account_no', true)
    );

    $invoice_no = sanitize_text_field(
        get_post_meta($claim_id, 'invoice_no', true)
    );

    $model_no = sanitize_text_field(
        get_post_meta($claim_id, 'model_no', true)
    );

    $claim_code = sanitize_text_field(
        get_post_meta($claim_id, 'claim_code', true)
    );

    $date_out = sanitize_text_field(
        get_post_meta($claim_id, 'date_out', true)
    );

    $total_payment = (float) get_post_meta(
        $claim_id,
        'total_payment',
        true
    );

    $submitted_on = wp_date(
        'm/d/Y',
        get_post_timestamp($claim_id)
    );

    $detail_url = zati_get_warranty_claim_detail_url(
        $claim_id
    );

    $result = [
        'ts_sent'                => false,
        'svc_sent'               => false,
        'attachments_included'   => false,
        'attachments_over_limit' => false,
    ];

    /* =====================================
       ZAC TS Notification
    ===================================== */

    if (!empty($settings['ts_enabled'])) {

        $ts_recipients =
            zati_get_ts_notification_recipients();

        $ts_cc_recipients =
            zati_get_ts_notification_cc_recipients();

        if (!empty($ts_recipients)) {

            $attachments =
                zati_get_warranty_claim_email_attachments(
                    $claim_id
                );
	      $worksheet_csv_path =
                        zati_create_warranty_claim_email_csv(
                              $claim_id
                           );

            if ($worksheet_csv_path !== '') {
                   $attachments[] = $worksheet_csv_path;
                             }

            $attachment_total_size =
                zati_get_attachment_paths_total_size(
                    $attachments
                );

            $attachment_limit_mb = max(
                1,
                absint($settings['attachment_limit_mb'])
            );

            $attachment_limit_bytes =
                $attachment_limit_mb * MB_IN_BYTES;

            $attachment_note = '';

            if (
                $attachment_total_size >
                $attachment_limit_bytes
            ) {
                /*
                 * Send the notification without attachments.
                 */
                $attachments = [];

                $result['attachments_over_limit'] = true;

                $attachment_note =
                    "\nAttachments were not included because their " .
                    "combined size exceeded the configured " .
                    $attachment_limit_mb .
                    " MB limit.\n" .
                    "Open the Warranty Claim Detail page to view them.\n";

            } elseif (!empty($attachments)) {

                $result['attachments_included'] = true;

                $attachment_note =
                    "\nAvailable claim documents are attached to " .
                    "this email.\n";

            } else {

                $attachment_note =
                    "\nNo attachment files were available for " .
                    "this notification.\n";
            }

            $ts_subject = sprintf(
                'New Warranty Claim - %s - %s',
                $reference_no ?: 'No Reference',
                $account_no ?: 'No Account'
            );

            $ts_message =
                "A new Warranty Claim has been submitted.\n\n" .
                "Reference No.: " .
                ($reference_no ?: '—') . "\n" .

                "Submitted On: " .
                ($submitted_on ?: '—') . "\n" .

                "Service Center: " .
                ($service_center ?: '—') . "\n" .

                "Account No.: " .
                ($account_no ?: '—') . "\n" .

                "Invoice No.: " .
                ($invoice_no ?: '—') . "\n" .

                "Model #: " .
                ($model_no ?: '—') . "\n" .

                "Claim: " .
                ($claim_code ?: '—') . "\n" .

                "Date Out: " .
                ($date_out ?: '—') . "\n" .

                "Total Payment: $" .
                number_format($total_payment, 2) . "\n" .

                $attachment_note . "\n" .

                "View Warranty Claim:\n" .
                $detail_url . "\n";

            $ts_headers = [
                'Content-Type: text/plain; charset=UTF-8',
            ];

            foreach ($ts_cc_recipients as $cc_address) {
                $ts_headers[] = 'Cc: ' . $cc_address;
            }

            $result['ts_sent'] = wp_mail(
                $ts_recipients,
                $ts_subject,
                $ts_message,
                $ts_headers,
                $attachments
            );
                     /*
                       * The CSV was needed only while wp_mail() was sending it.
                    */
                   if (
                     !empty($worksheet_csv_path) &&
                        file_exists($worksheet_csv_path)
                      ) {
                      unlink($worksheet_csv_path);
                        }

            update_post_meta(
                $claim_id,
                '_zati_ts_email_sent',
                $result['ts_sent'] ? '1' : '0'
            );

            update_post_meta(
                $claim_id,
                '_zati_ts_email_sent_at',
                current_time('mysql')
            );

            update_post_meta(
                $claim_id,
                '_zati_ts_email_attachments_included',
                $result['attachments_included'] ? '1' : '0'
            );

            update_post_meta(
                $claim_id,
                '_zati_ts_email_attachments_over_limit',
                $result['attachments_over_limit'] ? '1' : '0'
            );
        }
    }

    /* =====================================
       SVC Confirmation
    ===================================== */

    if (!empty($settings['svc_confirmation_enabled'])) {

        $author_id = (int) get_post_field(
            'post_author',
            $claim_id
        );

        $svc_user = $author_id
            ? get_userdata($author_id)
            : false;

        $svc_email = (
            $svc_user &&
            !empty($svc_user->user_email) &&
            is_email($svc_user->user_email)
        )
            ? sanitize_email($svc_user->user_email)
            : '';

        if ($svc_email !== '') {

            $svc_subject = sprintf(
                'Warranty Claim Submitted - %s',
                $reference_no ?: 'No Reference'
            );

            $svc_message =
                "Your Warranty Claim has been successfully submitted.\n\n" .

                "Reference No.: " .
                ($reference_no ?: '—') . "\n" .

                "Submitted On: " .
                ($submitted_on ?: '—') . "\n" .

                "Account No.: " .
                ($account_no ?: '—') . "\n" .

                "Invoice No.: " .
                ($invoice_no ?: '—') . "\n" .

                "Model #: " .
                ($model_no ?: '—') . "\n" .

                "Total Payment: $" .
                number_format($total_payment, 2) . "\n\n" .

                "View Warranty Claim:\n" .
                $detail_url . "\n\n" .

                "This is an automated confirmation from " .
                "Zojirushi America Tech Information.\n";

            $result['svc_sent'] = wp_mail(
                $svc_email,
                $svc_subject,
                $svc_message,
                [
                    'Content-Type: text/plain; charset=UTF-8',
                ]
            );

            update_post_meta(
                $claim_id,
                '_zati_svc_email_sent',
                $result['svc_sent'] ? '1' : '0'
            );

            update_post_meta(
                $claim_id,
                '_zati_svc_email_sent_at',
                current_time('mysql')
            );
        }
    }

    return $result;
}
/* =========================================
   ZATI Email Sender Name
========================================= */

function zati_mail_from_name($name) {
    return 'Zojirushi America Technical Support';
}

add_filter(
    'wp_mail_from_name',
    'zati_mail_from_name'
);

/* =========================================================
   Parts Order
========================================================= */


/* =========================================================
   Parts Order Statuses
========================================================= */

function zati_get_parts_order_statuses() {

    return [
        'Submitted'             => 'Submitted',
        'Processing'            => 'Processing',
        'Partially Backordered' => 'Partially Backordered',
        'Backordered'           => 'Backordered',
        'Completed'             => 'Completed',
        'Cancelled'             => 'Cancelled',
    ];
}



/**
 * Generate Parts Order reference number.
 *
 * Format:
 * PO-2026-000001
 */
function zati_generate_parts_order_reference() {

    global $wpdb;

    $year = wp_date('Y');
    $prefix = 'PO-' . $year . '-';

    $latest_reference = $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = %s
              AND meta_value LIKE %s
            ORDER BY meta_value DESC
            LIMIT 1
            ",
            'reference_no',
            $wpdb->esc_like($prefix) . '%'
        )
    );

    $next_number = 1;

    if ($latest_reference) {

        $last_number = (int) substr(
            $latest_reference,
            strlen($prefix)
        );

        $next_number = $last_number + 1;
    }

    return sprintf(
        '%s%06d',
        $prefix,
        $next_number
    );
}


/**
 * Sanitize one address array.
 */
function zati_sanitize_parts_order_address($address) {

    if (!is_array($address)) {
        $address = [];
    }

    $fields = [
        'name',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
    ];

    $clean_address = [];

    foreach ($fields as $field) {

        $clean_address[$field] =
            isset($address[$field])
                ? sanitize_text_field(
                    wp_unslash($address[$field])
                )
                : '';
    }

    return $clean_address;
}


/**
 * Convert currency-formatted value to float.
 */
function zati_parts_order_clean_money($value) {

    $cleaned = preg_replace(
        '/[^0-9.\-]/',
        '',
        (string) $value
    );

    return $cleaned === ''
        ? 0
        : (float) $cleaned;
}


/**
 * Save Parts Order.
 */
function zati_save_parts_order($posted_data) {

    if (!is_array($posted_data)) {
        return new WP_Error(
            'invalid_parts_order_data',
            'Invalid Parts Order data.'
        );
    }

    $reference_no =
        zati_generate_parts_order_reference();

    $service_center_name = sanitize_text_field(
        wp_unslash(
            $posted_data['service_center_name'] ?? ''
        )
    );

    $account_no = sanitize_text_field(
        wp_unslash(
            $posted_data['account_no'] ?? ''
        )
    );

    $phone = sanitize_text_field(
        wp_unslash(
            $posted_data['phone'] ?? ''
        )
    );

    $customer_po_no = sanitize_text_field(
        wp_unslash(
            $posted_data['customer_po_no'] ?? ''
        )
    );

    $order_date = sanitize_text_field(
        wp_unslash(
            $posted_data['order_date'] ?? ''
        )
    );

    $requested_ship_via = sanitize_text_field(
        wp_unslash(
            $posted_data['requested_ship_via'] ?? ''
        )
    );

    $requested_ship_date = sanitize_text_field(
        wp_unslash(
            $posted_data['requested_ship_date'] ?? 'ASAP'
        )
    );

    $terms = sanitize_text_field(
        wp_unslash(
            $posted_data['terms'] ?? ''
        )
    );

    $remarks = sanitize_text_field(
        wp_unslash(
            $posted_data['remarks'] ?? ''
        )
    );

    $sold_to =
        zati_sanitize_parts_order_address(
            $posted_data['sold_to'] ?? []
        );

    $ship_to =
        zati_sanitize_parts_order_address(
            $posted_data['ship_to'] ?? []
        );

    $parts = [];
    $calculated_total = 0;

    $posted_parts =
        isset($posted_data['parts'])
        && is_array($posted_data['parts'])
            ? $posted_data['parts']
            : [];

    foreach ($posted_parts as $posted_part) {

        if (!is_array($posted_part)) {
            continue;
        }

        $partnumber = sanitize_text_field(
            wp_unslash(
                $posted_part['partnumber'] ?? ''
            )
        );

        if ($partnumber === '') {
            continue;
        }

        $description = sanitize_text_field(
            wp_unslash(
                $posted_part['description'] ?? ''
            )
        );

        $quantity = max(
            1,
            absint(
                $posted_part['quantity'] ?? 1
            )
        );

        /*
 * Get the current part directly from ZATI.
 * Do not trust the Unit Price sent from the browser.
 */
      $part_query = new WP_Query([
    'post_type'      => 'part',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_query'     => [
        [
            'key'     => 'partnumber',
            'value'   => $partnumber,
            'compare' => '=',
        ],
    ],
      ]);

      if (empty($part_query->posts)) {
    return new WP_Error(
        'invalid_part_number',
        sprintf(
            'Part %s could not be found.',
            $partnumber
        )
    );
       }

      $part_id = (int) $part_query->posts[0];

      $distributor_price = get_field(
    'distributor_price',
    $part_id
      );

    $unit_price = zati_get_part_price_by_role(
    $distributor_price
     );

    /*
    * Recalculate Extended Price on the server.
    */
    $extended_price =
    $quantity * (float) $unit_price;

        $calculated_total +=
            $extended_price;

        $parts[] = [
            'partnumber'      => $partnumber,
            'description'     => $description,
            'quantity'        => $quantity,
            'unit_price'      => $unit_price,
            'extended_price'  => $extended_price,
        ];
    }

    if ($customer_po_no === '') {
        return new WP_Error(
            'missing_customer_po',
            'Customer PO No. is required.'
        );
    }

    if (empty($parts)) {
        return new WP_Error(
            'missing_parts',
            'At least one valid part is required.'
        );
    }

    $post_title = sprintf(
        '%s - %s - %s',
        $reference_no,
        $account_no,
        $customer_po_no
    );

    $order_id = wp_insert_post(
        [
            'post_type'   => 'parts_order',
            'post_status' => 'publish',
            'post_title'  => $post_title,
            'post_author' => get_current_user_id(),
        ],
        true
    );

       if (is_wp_error($order_id)) {
        return $order_id;
    }

/*
 * Build initial TS processing data.
 *
 * Keep the original "parts" data unchanged.
 */
$ts_parts = [];

if (is_array($parts)) {

    foreach ($parts as $part) {

        $ordered_qty = isset($part['quantity'])
            ? max(1, absint($part['quantity']))
            : 1;

        $ts_parts[] = [
            'partnumber'      => sanitize_text_field(
                $part['partnumber'] ?? ''
            ),
            'ordered_qty'     => $ordered_qty,

            /*
             * Initial TS processing values.
             */
            'confirmed_qty'   => 0,
            'backorder_qty'   => 0,
            'item_status'     => 'Pending',
            'ts_note'         => '',
        ];
    }
}


   $meta_values = [
    'reference_no'          => $reference_no,
    'service_center_name'   => $service_center_name,
    'account_no'            => $account_no,
    'phone'                 => $phone,
    'customer_po_no'        => $customer_po_no,
    'order_date'            => $order_date,
    'requested_ship_via'    => $requested_ship_via,
    'requested_ship_date'   => $requested_ship_date,
    'terms'                 => $terms,
    'remarks'               => $remarks,

    /*
     * Initial Parts Order status.
     */
    'order_status'          => 'Submitted',

    /*
     * Original addresses submitted by the SVC.
     */
    'sold_to'               => $sold_to,
    'ship_to'               => $ship_to,

    /*
     * Original order lines submitted by the SVC.
     * Do not overwrite these during TS processing.
     */
    'parts'                 => $parts,

    /*
     * TS processing data.
     */
    'ts_parts'              => $ts_parts,

    /*
     * Order total.
     */
    'parts_total'           => round(
        $calculated_total,
        2
    ),

    /*
     * Future TS processing fields.
     */
    'ts_internal_note'      => '',
    'processed_by'          => 0,
    'processed_at'          => '',

    /*
     * Submission record.
     */
    'submitted_user_id'     => get_current_user_id(),
    'submitted_at'          => current_time('mysql'),
];
    foreach ($meta_values as $meta_key => $meta_value) {

    update_post_meta(
        $order_id,
        $meta_key,
        $meta_value
    );
}


/*
 * Send notification to Technical Support.
 */
zati_send_parts_order_ts_notification(
    $order_id
);


return [
    'order_id'      => $order_id,
    'reference_no'  => $reference_no,
    'parts_total'   => round(
        $calculated_total,
        2
    ),
];

}

/*=========================================================
 * Initialize TS processing data from original order parts.
 *
 * Original "parts" data is never modified.
 =========================================================
*/
function zati_initialize_ts_parts( $order_id ) {

    $existing_ts_parts = get_post_meta(
        $order_id,
        'ts_parts',
        true
    );

    // Already initialized.
    if (
        is_array( $existing_ts_parts ) &&
        ! empty( $existing_ts_parts )
    ) {
        return $existing_ts_parts;
    }

    $parts = get_post_meta(
        $order_id,
        'parts',
        true
    );

    if (
        ! is_array( $parts ) ||
        empty( $parts )
    ) {
        return [];
    }

    $ts_parts = [];

    foreach ( $parts as $part ) {

        $part_no = isset( $part['partnumber'] )
            ? sanitize_text_field( $part['partnumber'] )
            : '';

        $ordered_qty = isset( $part['quantity'] )
            ? absint( $part['quantity'] )
            : 0;

        $ts_parts[] = [
            'part_no'       => $part_no,
            'ordered_qty'   => $ordered_qty,
            'confirmed_qty' => 0,
            'backorder_qty' => 0,
            'item_status'   => 'Submitted',
            'ts_note'       => '',
        ];
    }

    update_post_meta(
        $order_id,
        'ts_parts',
        $ts_parts
    );

    return $ts_parts;
}

/* =========================================================
   Parts Order Email CSV
========================================================= */

function zati_create_parts_order_email_csv($order_id) {

    $order_id = absint($order_id);

    if (
        !$order_id ||
        get_post_type($order_id) !== 'parts_order'
    ) {
        return '';
    }

    $reference_no = sanitize_text_field(
        get_post_meta($order_id, 'reference_no', true)
    );

    $service_center_name = sanitize_text_field(
        get_post_meta($order_id, 'service_center_name', true)
    );

    $account_no = sanitize_text_field(
        get_post_meta($order_id, 'account_no', true)
    );

    $customer_po_no = sanitize_text_field(
        get_post_meta($order_id, 'customer_po_no', true)
    );

    $order_date = sanitize_text_field(
        get_post_meta($order_id, 'order_date', true)
    );

    $ship_via = sanitize_text_field(
        get_post_meta($order_id, 'requested_ship_via', true)
    );

    $ship_date = sanitize_text_field(
        get_post_meta($order_id, 'requested_ship_date', true)
    );

    $terms = sanitize_text_field(
        get_post_meta($order_id, 'terms', true)
    );

    $remarks = sanitize_text_field(
        get_post_meta($order_id, 'remarks', true)
    );

    $sold_to = get_post_meta(
        $order_id,
        'sold_to',
        true
    );

    $ship_to = get_post_meta(
        $order_id,
        'ship_to',
        true
    );

    $parts = get_post_meta(
        $order_id,
        'parts',
        true
    );

    $parts_total = (float) get_post_meta(
        $order_id,
        'parts_total',
        true
    );

    if (!is_array($sold_to)) {
        $sold_to = [];
    }

    if (!is_array($ship_to)) {
        $ship_to = [];
    }

    if (!is_array($parts)) {
        $parts = [];
    }


    $uploads = wp_upload_dir();

    if (!empty($uploads['error'])) {
        return '';
    }

    $temp_dir = trailingslashit(
        $uploads['basedir']
    ) . 'zati-temp';

    if (
        !is_dir($temp_dir) &&
        !wp_mkdir_p($temp_dir)
    ) {
        return '';
    }

    $filename =
        'Parts_Order_' .
        sanitize_file_name(
            $reference_no ?: $order_id
        ) .
        '.csv';

    $file_path =
        trailingslashit($temp_dir) .
        $filename;

    $output = fopen($file_path, 'w');

    if (!$output) {
        return '';
    }

    /*
     * UTF-8 BOM for Excel.
     */
    fwrite(
        $output,
        "\xEF\xBB\xBF"
    );


    /*
     * Order information.
     */
    fputcsv($output, [
        'Zojirushi America Corporation'
    ]);

    fputcsv($output, []);

    fputcsv($output, [
        'Reference No.',
        $reference_no
    ]);

    fputcsv($output, [
        'Order Date',
        $order_date
    ]);

    fputcsv($output, [
        'Service Center',
        $service_center_name
    ]);

    fputcsv($output, [
        'Account No.',
        $account_no
    ]);

    fputcsv($output, [
        'Customer PO No.',
        $customer_po_no
    ]);

    fputcsv($output, [
        'Ship Via',
        $ship_via
    ]);

    fputcsv($output, [
        'Ship Date',
        $ship_date
    ]);

    fputcsv($output, [
        'Terms',
        $terms
    ]);

    fputcsv($output, [
        'Remarks',
        $remarks
    ]);


    /*
     * Sold To.
     */
    fputcsv($output, []);
    fputcsv($output, ['SOLD TO']);

    foreach (
        [
            'name',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
        ] as $field
    ) {

        if (!empty($sold_to[$field])) {
            fputcsv($output, [
                ucfirst($field),
                $sold_to[$field],
            ]);
        }
    }


    /*
     * Ship To.
     */
    fputcsv($output, []);
    fputcsv($output, ['SHIP TO']);

    foreach (
        [
            'name',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
        ] as $field
    ) {

        if (!empty($ship_to[$field])) {
            fputcsv($output, [
                ucfirst($field),
                $ship_to[$field],
            ]);
        }
    }


    /*
     * Parts table.
     */
    fputcsv($output, []);

    fputcsv($output, [
        'Part No.',
        'Qty',
        'Description',
        'Unit Price',
        'Extended Price',
    ]);

    foreach ($parts as $part) {

        fputcsv($output, [
            $part['partnumber'] ?? '',
            $part['quantity'] ?? '',
            $part['description'] ?? '',
            number_format(
                (float) (
                    $part['unit_price'] ?? 0
                ),
                2,
                '.',
                ''
            ),
            number_format(
                (float) (
                    $part['extended_price'] ?? 0
                ),
                2,
                '.',
                ''
            ),
        ]);
    }

    fputcsv($output, []);

    fputcsv($output, [
        '',
        '',
        '',
        'Parts Total',
        number_format(
            $parts_total,
            2,
            '.',
            ''
        ),
    ]);

    fclose($output);

    return $file_path;
}
/* =========================================================
   Parts Order - TS Email Notification
========================================================= */

function zati_send_parts_order_ts_notification($order_id) {

    $order_id = absint($order_id);

    if (!$order_id) {
        return false;
    }

    $reference_no = get_post_meta(
        $order_id,
        'reference_no',
        true
    );

    $service_center_name = get_post_meta(
        $order_id,
        'service_center_name',
        true
    );

    $account_no = get_post_meta(
        $order_id,
        'account_no',
        true
    );

    $customer_po_no = get_post_meta(
        $order_id,
        'customer_po_no',
        true
    );

    $order_date = get_post_meta(
        $order_id,
        'order_date',
        true
    );

    $ship_via = get_post_meta(
        $order_id,
        'requested_ship_via',
        true
    );

    $ship_date = get_post_meta(
        $order_id,
        'requested_ship_date',
        true
    );

    $terms = get_post_meta(
        $order_id,
        'terms',
        true
    );

    $remarks = get_post_meta(
        $order_id,
        'remarks',
        true
    );

    $parts = get_post_meta(
        $order_id,
        'parts',
        true
    );

    $parts_total = (float) get_post_meta(
        $order_id,
        'parts_total',
        true
    );


  /*
 * ==========================================
 * Parts Order Email Recipients
 * ==========================================
 */

$submitted_user_id = absint(
    get_post_meta(
        $order_id,
        'submitted_user_id',
        true
    )
);

$submitted_user = $submitted_user_id
    ? get_userdata($submitted_user_id)
    : null;

$to = zati_get_parts_order_notification_recipients(
    $submitted_user
);

$cc = zati_get_parts_order_notification_cc_recipients(
    $submitted_user
);

/*
 * No valid recipient configured.
 */
if (empty($to)) {
    return false;
}

    /*
     * ==========================================
     * Subject
     * ==========================================
     */

    $subject = sprintf(
        '[ZATI Parts Order] %s - %s',
        $reference_no,
        $service_center_name
    );


    /*
     * ==========================================
     * Message
     * ==========================================
     */

    $message = '';

    $message .= "A new Parts Order has been submitted.\n\n";

    $message .= "Reference No.: "
        . $reference_no . "\n";

    $message .= "Service Center: "
        . $service_center_name . "\n";

    $message .= "Account No.: "
        . $account_no . "\n";

    $message .= "Customer PO No.: "
        . $customer_po_no . "\n";

    $message .= "Order Date: "
        . $order_date . "\n\n";


    $message .= "Shipping Request\n";
    $message .= "------------------------------\n";

    $message .= "Ship Via: "
        . $ship_via . "\n";

    $message .= "Ship Date: "
        . $ship_date . "\n";

    $message .= "Terms: "
        . $terms . "\n";

    if ($remarks !== '') {
        $message .= "Remarks: "
            . $remarks . "\n";
    }


    $message .= "\nParts Ordered\n";
    $message .= "------------------------------\n";


    if (is_array($parts)) {

        foreach ($parts as $part) {

            $partnumber = isset(
                $part['partnumber']
            )
                ? $part['partnumber']
                : '';

            $description = isset(
                $part['description']
            )
                ? $part['description']
                : '';

            $quantity = isset(
                $part['quantity']
            )
                ? (int) $part['quantity']
                : 0;

            $unit_price = isset(
                $part['unit_price']
            )
                ? $part['unit_price']
                : '';

            $extended_price = isset(
                $part['extended_price']
            )
                ? $part['extended_price']
                : '';

            $message .= "\n";

            $message .= "Part No.: "
                . $partnumber . "\n";

            $message .= "Description: "
                . $description . "\n";

            $message .= "Qty: "
                . $quantity . "\n";

            $message .= "Unit Price: "
                . $unit_price . "\n";

            $message .= "Extended Price: "
                . $extended_price . "\n";
        }
    }


    $message .= "\n------------------------------\n";

    $message .= "Parts Total: $"
        . number_format(
            $parts_total,
            2
        )
        . "\n";


    /*
     * Temporary admin link.
     *
     * Later we will replace this with
     * Parts Order Detail front-end page.
     */

    $admin_url = admin_url(
        'post.php?post='
        . $order_id
        . '&action=edit'
    );

    $message .= "\nView Order:\n";
    $message .= $admin_url . "\n";


    $message .= "\nZATI\n";
    $message .= "Zojirushi America Technical Information\n";


    /*
     * ==========================================
     * Headers
     * ==========================================
     */

   $headers = [
    'Content-Type: text/plain; charset=UTF-8',
      ];

        foreach ($cc as $cc_address) {

    $headers[] =
        'Cc: ' . $cc_address;
            }

    $csv_path =
    zati_create_parts_order_email_csv(
        $order_id
    );

     $xlsx_path =
    zati_create_parts_order_email_xlsx(
        $order_id
    );

     $attachments = [];

    if (
    $csv_path !== '' &&
    file_exists($csv_path)
     ) {
    $attachments[] = $csv_path;
    }

    if (
    $xlsx_path !== '' &&
    file_exists($xlsx_path)
    ) {
    $attachments[] = $xlsx_path;
   }

   $mail_sent = wp_mail(
    $to,
    $subject,
    $message,
    $headers,
    $attachments
   );

       /*
      * The CSV is needed only while
      * wp_mail() is sending the email.
      */
      if (
    $csv_path !== '' &&
    file_exists($csv_path)
       ) {
    unlink($csv_path);
      }

     if (
    $xlsx_path !== '' &&
    file_exists($xlsx_path)
      ) {
    unlink($xlsx_path);
      }

      return $mail_sent;
          }

/* =========================================================
   Parts Order Admin Meta Box
========================================================= */

add_action(
    'add_meta_boxes_parts_order',
    function () {

        add_meta_box(
            'zati_parts_order_details',
            'Parts Order Details',
            'zati_render_parts_order_admin_meta_box',
            'parts_order',
            'normal',
            'high'
        );

    }
);


function zati_render_parts_order_admin_meta_box($post) {

    $reference_no = get_post_meta(
        $post->ID,
        'reference_no',
        true
          );
	$order_status = sanitize_text_field(
    get_post_meta(
        $post->ID,
        'order_status',
        true
      )
	);

	if ($order_status === '') {
    $order_status = 'Submitted';
	}

    $service_center_name = get_post_meta(
        $post->ID,
        'service_center_name',
        true
    );

    $account_no = get_post_meta(
        $post->ID,
        'account_no',
        true
    );

    $phone = get_post_meta(
        $post->ID,
        'phone',
        true
    );

    $customer_po_no = get_post_meta(
        $post->ID,
        'customer_po_no',
        true
    );

    $order_date = get_post_meta(
        $post->ID,
        'order_date',
        true
    );

    $requested_ship_via = get_post_meta(
        $post->ID,
        'requested_ship_via',
        true
    );

    $requested_ship_date = get_post_meta(
        $post->ID,
        'requested_ship_date',
        true
    );

    $terms = get_post_meta(
        $post->ID,
        'terms',
        true
    );

    $remarks = get_post_meta(
        $post->ID,
        'remarks',
        true
    );

    $sold_to = get_post_meta(
        $post->ID,
        'sold_to',
        true
    );

    $ship_to = get_post_meta(
        $post->ID,
        'ship_to',
        true
    );

    $parts = get_post_meta(
        $post->ID,
        'parts',
        true
    );

    $parts_total = (float) get_post_meta(
        $post->ID,
        'parts_total',
        true
    );

    $submitted_at = get_post_meta(
        $post->ID,
        'submitted_at',
        true
    );

    if (!is_array($sold_to)) {
        $sold_to = [];
    }

    if (!is_array($ship_to)) {
        $ship_to = [];
    }

    if (!is_array($parts)) {
        $parts = [];
    }

    ?>
    <style>
        .zati-admin-order {
            max-width: 1100px;
        }

        .zati-admin-order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .zati-admin-order-section {
            border: 1px solid #dcdcde;
            background: #fff;
            padding: 18px;
        }

        .zati-admin-order-section h3 {
            margin-top: 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #dcdcde;
        }

        .zati-admin-order dl {
            display: grid;
            grid-template-columns: 170px 1fr;
            gap: 8px 16px;
            margin: 0;
        }

        .zati-admin-order dt {
            font-weight: 600;
        }

        .zati-admin-order dd {
            margin: 0;
        }

        .zati-admin-address {
            line-height: 1.6;
        }

        .zati-admin-parts-table {
            width: 100%;
            border-collapse: collapse;
        }

        .zati-admin-parts-table th,
        .zati-admin-parts-table td {
            border: 1px solid #dcdcde;
            padding: 8px 10px;
            text-align: left;
        }

        .zati-admin-parts-table th {
            background: #f6f7f7;
        }

        .zati-admin-money {
            text-align: right !important;
        }

        @media (max-width: 900px) {
            .zati-admin-order-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="zati-admin-order">

        <div class="zati-admin-order-grid">

            <section class="zati-admin-order-section">
                <h3>Order Information</h3>

                <dl>
                    <dt>Reference No.</dt>
                    <dd><?php echo esc_html($reference_no); ?></dd>
                                       <dt>Status</dt>
                                       <dd>
                                     <strong>
                               <?php echo esc_html($order_status); ?>
                                     </strong>
                                          </dd>

                    <dt>Service Center</dt>
                    <dd><?php echo esc_html($service_center_name); ?></dd>

                    <dt>Account No.</dt>
                    <dd><?php echo esc_html($account_no); ?></dd>

                    <dt>Phone No.</dt>
                    <dd><?php echo esc_html($phone); ?></dd>

                    <dt>Customer PO No.</dt>
                    <dd><?php echo esc_html($customer_po_no); ?></dd>

                    <dt>Order Date</dt>
                    <dd><?php echo esc_html($order_date); ?></dd>

                    <dt>Submitted At</dt>
                    <dd><?php echo esc_html($submitted_at); ?></dd>
                </dl>
            </section>

            <section class="zati-admin-order-section">
                <h3>Shipping Request</h3>

                <dl>
                    <dt>Ship Via</dt>
                    <dd><?php echo esc_html($requested_ship_via); ?></dd>

                    <dt>Ship Date</dt>
                    <dd><?php echo esc_html($requested_ship_date); ?></dd>

                    <dt>Terms</dt>
                    <dd><?php echo esc_html($terms); ?></dd>

                    <dt>Remarks</dt>
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

        <div class="zati-admin-order-grid">

            <section class="zati-admin-order-section">
                <h3>Sold To</h3>

                <?php
                zati_render_parts_order_admin_address(
                    $sold_to
                );
                ?>
            </section>

            <section class="zati-admin-order-section">
                <h3>Ship To</h3>

                <?php
                zati_render_parts_order_admin_address(
                    $ship_to
                );
                ?>
            </section>

        </div>

        <section class="zati-admin-order-section">
            <h3>Parts Ordered</h3>

            <table class="zati-admin-parts-table">
                <thead>
                    <tr>
                        <th>Part No.</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Extended Price</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($parts)) : ?>

                        <?php foreach ($parts as $part) : ?>
                            <tr>
                                <td>
                                    <?php
                                    echo esc_html(
                                        $part['partnumber'] ?? ''
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $part['description'] ?? ''
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $part['quantity'] ?? ''
                                    );
                                    ?>
                                </td>

                                <td class="zati-admin-money">
                                    $<?php
                                    echo esc_html(
                                        number_format(
                                            (float) (
                                                $part['unit_price']
                                                ?? 0
                                            ),
                                            2
                                        )
                                    );
                                    ?>
                                </td>

                                <td class="zati-admin-money">
                                    $<?php
                                    echo esc_html(
                                        number_format(
                                            (float) (
                                                $part['extended_price']
                                                ?? 0
                                            ),
                                            2
                                        )
                                    );
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else : ?>

                        <tr>
                            <td colspan="5">
                                No parts data found.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="4">
                            Parts Total
                        </th>

                        <th class="zati-admin-money">
                            $<?php
                            echo esc_html(
                                number_format(
                                    $parts_total,
                                    2
                                )
                            );
                            ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </section>

    </div>
    <?php
}


function zati_render_parts_order_admin_address(
    $address
) {

    if (!is_array($address)) {
        $address = [];
    }

    $name = $address['name'] ?? '';
    $address1 = $address['address1'] ?? '';
    $address2 = $address['address2'] ?? '';
    $city = $address['city'] ?? '';
    $state = $address['state'] ?? '';
    $zip = $address['zip'] ?? '';
    $country = $address['country'] ?? '';

    $countries = [
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
    ];

    $country_display =
        $countries[$country] ?? $country;

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

    <div class="zati-admin-address">

        <?php if ($name !== '') : ?>
            <strong>
                <?php echo esc_html($name); ?>
            </strong><br>
        <?php endif; ?>

        <?php if ($address1 !== '') : ?>
            <?php echo esc_html($address1); ?><br>
        <?php endif; ?>

        <?php if ($address2 !== '') : ?>
            <?php echo esc_html($address2); ?><br>
        <?php endif; ?>

        <?php if ($city_line !== '') : ?>
            <?php echo esc_html($city_line); ?><br>
        <?php endif; ?>

        <?php if ($country_display !== '') : ?>
            <?php echo esc_html($country_display); ?>
        <?php endif; ?>

    </div>

    <?php
}
/* =========================================================
   Parts Order User Roles
========================================================= */

function zati_is_parts_order_ts_user($user = null) {

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    return in_array(
        'administrator',
        (array) $user->roles,
        true
    ) || in_array(
        'zac_ts',
        (array) $user->roles,
        true
    );
}


function zati_is_parts_order_svc_user($user = null) {

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    $svc_roles = [
        'us_canada_svc',
        'mexico_svc',
        'canada_parts_sales',
    ];

    return (bool) array_intersect(
        $svc_roles,
        (array) $user->roles
    );
}


/* =========================================================
   Parts Order View Permission
========================================================= */

function zati_current_user_can_view_parts_order($order_id) {

    $order_id = absint($order_id);

    if (
        !$order_id ||
        get_post_type($order_id) !== 'parts_order' ||
        get_post_status($order_id) !== 'publish'
    ) {
        return false;
    }

    $current_user = wp_get_current_user();

    /*
     * Administrator and ZAC TS can view all Parts Orders.
     */
    if (zati_is_parts_order_ts_user($current_user)) {
        return true;
    }

    /*
     * Only Parts Order SVC roles can continue.
     */
    if (!zati_is_parts_order_svc_user($current_user)) {
        return false;
    }

    $user_account_no =
        zati_get_current_service_center_account_no();

    $order_account_no = sanitize_text_field(
        get_post_meta(
            $order_id,
            'account_no',
            true
        )
    );

    if (
        $user_account_no === '' ||
        $order_account_no === ''
    ) {
        return false;
    }

    return strcasecmp(
        trim($user_account_no),
        trim($order_account_no)
    ) === 0;
}


/* =========================================================
   Parts Order Archive Query
========================================================= */

function zati_get_parts_order_archive_query_args(
    $filters = []
) {

    $year = isset($filters['year'])
        ? absint($filters['year'])
        : 0;

    $month = isset($filters['month'])
        ? absint($filters['month'])
        : 0;

    $account_no = isset($filters['account_no'])
        ? sanitize_text_field($filters['account_no'])
        : '';

    $status = isset($filters['status'])
        ? sanitize_text_field($filters['status'])
        : '';

    $query_args = [
        'post_type'      => 'parts_order',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    /*
     * Year and month filter.
     */
    if ($year > 0) {

        $date_filter = [
            'year' => $year,
        ];

        if ($month >= 1 && $month <= 12) {
            $date_filter['monthnum'] = $month;
        }

        $query_args['date_query'] = [
            $date_filter,
        ];
    }

    $meta_query = [
        'relation' => 'AND',
    ];

    $current_user = wp_get_current_user();

    /*
     * SVC users are always restricted to their own
     * registered Account No.
     */
    if (zati_is_parts_order_svc_user($current_user)) {

        $current_account_no =
            zati_get_current_service_center_account_no();

        if ($current_account_no === '') {

            /*
             * Display no orders when Account No. is missing.
             */
            $query_args['post__in'] = [0];

        } else {

            $meta_query[] = [
                'key'     => 'account_no',
                'value'   => $current_account_no,
                'compare' => '=',
            ];
        }

    } elseif (zati_is_parts_order_ts_user($current_user)) {

        /*
         * TS and Administrator may filter by Account No.
         */
        if ($account_no !== '') {
            $meta_query[] = [
                'key'     => 'account_no',
                'value'   => $account_no,
                'compare' => '=',
            ];
        }

    } else {

        /*
         * Other roles cannot view Parts Order Archive.
         */
        $query_args['post__in'] = [0];
    }

    /*
     * Order Status filter.
     */
    if ($status !== '') {

        $valid_statuses =
            zati_get_parts_order_statuses();

        if (isset($valid_statuses[$status])) {

            $meta_query[] = [
                'key'     => 'order_status',
                'value'   => $status,
                'compare' => '=',
            ];
        }
    }

    if (count($meta_query) > 1) {
        $query_args['meta_query'] = $meta_query;
    }

    return $query_args;
}

/* ========================================
   News Topics Categories
   ======================================== */

function zati_register_news_category_taxonomy() {

    $labels = [
        'name'              => 'News Categories',
        'singular_name'     => 'News Category',
        'search_items'      => 'Search News Categories',
        'all_items'         => 'All News Categories',
        'parent_item'       => 'Parent News Category',
        'parent_item_colon' => 'Parent News Category:',
        'edit_item'         => 'Edit News Category',
        'update_item'       => 'Update News Category',
        'add_new_item'      => 'Add New News Category',
        'new_item_name'     => 'New News Category Name',
        'menu_name'         => 'News Categories',
    ];

    register_taxonomy(
        'news_category',
        ['zac_news'],
        [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => [
                'slug' => 'news-category',
            ],
        ]
    );
}

add_action(
    'init',
    'zati_register_news_category_taxonomy'
);

/* =========================================================
   News Topics CSS
========================================================= */

function zati_enqueue_news_css() {

    if (!is_singular('zac_news')) {
        return;
    }

    $css_file =
        get_stylesheet_directory()
        . '/assets/css/zati-news.css';

    if (!file_exists($css_file)) {

        /*
         * CSSファイルが存在しない場合、
         * 管理者にはHTMLコメントで確認できるようにする
         */
        if (current_user_can('manage_options')) {
            add_action(
                'wp_footer',
                static function () use ($css_file) {
                    echo "\n<!-- ZATI News CSS not found: "
                        . esc_html($css_file)
                        . " -->\n";
                }
            );
        }

        return;
    }

    wp_enqueue_style(
        'zati-news',
        get_stylesheet_directory_uri()
            . '/assets/css/zati-news.css',
        [],
        filemtime($css_file)
    );
}

add_action(
    'wp_enqueue_scripts',
    'zati_enqueue_news_css',
    30
);