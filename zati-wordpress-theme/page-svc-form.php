<?php
/*
Template Name: ZATI Forms
Template Post Type: page
*/

if (!is_user_logged_in()) {
    auth_redirect();
}

$can_access_warranty =
    zati_current_user_can_access_warranty_claim();

$can_access_parts_order =
    zati_current_user_can_access_parts_order();

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


</div>

<main class="zati-forms-hub-page">

    <section class="zati-forms-hub-wrap">

        <div class="zati-forms-hub-header">
            <h1>Forms</h1>

            <p>
                Submit new requests and view previously submitted records.
            </p>
        </div>

<?php
$module_notice = isset($_GET['module_notice'])
    ? sanitize_key(wp_unslash($_GET['module_notice']))
    : '';
?>

<?php if ($module_notice === 'warranty_unavailable') : ?>

    <div class="zati-forms-hub-notice" role="alert">
        Warranty Claim is not available for your account.
    </div>

<?php endif; ?>


        <div class="zati-forms-hub-grid">

            <?php if ($can_access_warranty) : ?>

                <article class="zati-form-module-card">

                    <div class="zati-form-module-content">

                        <span class="zati-form-module-label">
                            WARRANTY
                        </span>

                        <h2>Warranty Claim</h2>

                        <p>
                            Submit a new warranty claim or review previously
                            submitted warranty claims.
                        </p>

                    </div>

                    <div class="zati-form-module-actions ">

                        <a
                            href="<?php echo esc_url(
                                home_url('/warranty-claim/')
                            ); ?>"
                            class="zati-primary-btn"
                        >
                            SUBMIT NEW CLAIM
                        </a>

                        <a
                            href="<?php echo esc_url(
                                home_url('/warranty-claim-archive/')
                            ); ?>"
                            class="zati-secondary-btn"
                        >
                            VIEW ARCHIVE
                        </a>

                    </div>

                </article>

            <?php endif; ?>

            <?php if ($can_access_parts_order) : ?>

                <article class="zati-form-module-card">

                    <div class="zati-form-module-content">

                        <span class="zati-form-module-label">
                            PARTS
                        </span>

                        <h2>Parts Order</h2>

                        <p>
                            Submit a parts order request and review order
                            information.
                        </p>

                    </div>

                    <div class="zati-form-module-actions">

                        <a
                            href="<?php echo esc_url(
                                add_query_arg(
                                    'new',
                                    '1',
                                    home_url('/parts-order/')
                                )
                            ); ?>"
                            class="zati-primary-btn"
                        >
                            SUBMIT NEW ORDER
                        </a>

                        <a
                                                  href="<?php echo esc_url(
                                                     home_url('/parts-order-archive/')
                                                  ); ?>"
                                                    class="zati-secondary-btn"
                                                        >
                                                      VIEW ARCHIVE
                                                  </a>
                    </div>

                </article>

            <?php endif; ?>

        </div>

        <?php if (
            !$can_access_warranty &&
            !$can_access_parts_order
        ) : ?>

            <div class="zati-forms-hub-empty">
                No forms are available for your account.
            </div>

        <?php endif; ?>

    </section>

</main>

<?php
get_template_part('app-footer');
get_footer('blank');