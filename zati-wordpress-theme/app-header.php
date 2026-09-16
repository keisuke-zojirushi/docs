<?php
// app-header.php
?>
<header class="app-header">
    <div class="app-header-left">
        <a class="app-brand" href="<?php echo esc_url(home_url('/')); ?>">
    <img
        class="app-logo-img"
        src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_white_logo.png'); ?>"
        alt="Zojirushi America Tech Information"
    >
    <span class="app-brand-text">Zojirushi America Tech Information</span>
</a>    </div>

    <nav class="app-nav">
        <div class="app-nav-item has-dropdown">
            <a href="#" class="app-nav-parent" onclick="return false;">
                 Models & Parts <span class="nav-arrow">▼</span>                 </a>  
             <div class="app-dropdown">
                  <a href="<?php echo esc_url( home_url('/search-models/') ); ?>">
                  Search Models
                   </a>

                 <a href="<?php echo esc_url( home_url('/parts-search/') ); ?>">
                  Parts Search
                 </a>           
             </div>
        </div>

        <?php
$can_access_warranty =
    zati_current_user_can_access_warranty_claim();

$can_access_parts_order =
    zati_current_user_can_access_parts_order();
?>

<?php if (
    $can_access_warranty ||
    $can_access_parts_order
) : ?>

    <div class="app-nav-item has-dropdown">

        <a href="<?php echo esc_url(home_url('/svc-form/')); ?>">
            Form <span class="nav-arrow">▼</span>
        </a>

        <div class="app-dropdown">

            <?php if ($can_access_warranty) : ?>

                <a href="<?php echo esc_url(
                    home_url('/warranty-claim/')
                ); ?>">
                    Warranty Claim Form
                </a>

                <a href="<?php echo esc_url(
                    home_url('/warranty-claim-archive/')
                ); ?>">
                    Warranty Claim Archive
                </a>

            <?php endif; ?>

            <?php if ($can_access_parts_order) : ?>

                <a href="<?php echo esc_url(
                    home_url('/parts-order/')
                ); ?>">
                    Parts Order Form
                </a>

              <a href="<?php echo esc_url(
                   home_url('/parts-order-archive/')
                ); ?>">
                  Parts Order Archive
              </a>

            <?php endif; ?>

        </div>

    </div>

<?php endif; ?>    </nav>

<div class="app-user">

   <?php if (is_user_logged_in()) : ?>

    <?php
    $current_user = wp_get_current_user();
    $roles = (array) $current_user->roles;

    $icon_class = 'role-default';

    if (in_array('administrator', $roles, true)) {
        $icon_class = 'role-admin';

    } elseif (in_array('zac_ts', $roles, true)) {
        $icon_class = 'role-ts';

    } elseif (in_array('us_canada_svc', $roles, true)) {
        $icon_class = 'role-us';

    } elseif (in_array('canada_parts_sales', $roles, true)) {
        $icon_class = 'role-canada';

    } elseif (in_array('mexico_svc', $roles, true)) {
        $icon_class = 'role-mexico';
    } elseif (in_array('subscriber', $roles, true)) {
    $icon_class = 'role-zac-staff';
    }
    ?>
         <div class="app-user-menu">
            <button class="app-user-button" type="button">
               <span class="app-user-icon <?php echo esc_attr($icon_class); ?>">
    <svg class="app-user-svg" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.314 0-10 1.657-10 5v3h20v-3c0-3.343-6.686-5-10-5z"/>
    </svg>
</span>

                <span class="app-user-id">
                    <?php echo esc_html( $current_user->display_name ); ?>
                </span>
                <span class="app-user-arrow">▼</span>
            </button>

            <div class="app-user-dropdown">
                <a href="<?php echo esc_url( wp_logout_url( home_url('/login/') ) ); ?>">
                    Logout
                </a>
            </div>
        </div>

    <?php else : ?>

        <div class="app-login-menu">
            <a class="app-login-link" href="<?php echo esc_url( home_url('/login/') ); ?>">
                Login
            </a>
            <a class="app-reset-link" href="<?php echo esc_url( home_url('/forgot-password/') ); ?>">
                Forgot Password
            </a>
        </div>

    <?php endif; ?>
</div>

</header>