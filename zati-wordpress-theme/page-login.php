<?php
/*
Template Name: ZAC Login
*/

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/') );
    exit;
}

$login_error = '';

if ( isset($_POST['zac_login_submit']) ) {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['log']),
        'user_password' => $_POST['pwd'],
        'remember'      => ! empty($_POST['rememberme']),
    );

    $user = wp_signon($creds, false);

    if ( is_wp_error($user) ) {
        $login_error = 'Invalid User ID or Password.';
    } else {
        wp_redirect(home_url('/'));
        exit;
    }
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body class="zac-login-page">

<div class="zac-login-wrapper">

    <section class="zac-login-left">
        <div class="zac-login-box">

            <img class="zac-login-logo"
                 src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>"
                 alt="Zojirushi">

            <h1>Zojirushi America </h1>
                    <h3>Tech Information </h3>

            <form method="post" class="zac-login-form">
                <h2>Login</h2>

                <?php if ( $login_error ) : ?>
                    <div class="zac-login-error">
                        <?php echo esc_html($login_error); ?>
                    </div>
                <?php endif; ?>

                <label>User ID</label>
                <input type="text" name="log" required>

                <label>Password</label>
                <input type="password" name="pwd" required>

                <label class="zac-remember">
                    <input type="checkbox" name="rememberme" value="1">
                    Remember me
                </label>

                <button type="submit" name="zac_login_submit">
                    Login
                </button>

                <a class="zac-forgot-link" href="<?php echo esc_url(home_url('/forgot-password/')); ?>">
                    Forgot password?
                </a>
            </form>

        </div>
    </section>

    <section class="zac-login-right">
        <div class="zac-login-info">
            <h2>About this system</h2>
            <p>Please follow the instructions below if you would like to use this service.</p>

            <h3>Recommended Use Environment</h3>
            <p>This system is recommended to be used with the following browser environments:</p>
	<p class="zac-terms-link">
    	<a href="<?php echo esc_url(home_url('/terms-of-use/')); ?>">Terms of Use</a>
	</p>           
 	<p>[Recommended Browsers]</p>
            <ul>
                <li>Google Chrome</li>
                <li>Microsoft Edge</li>
                <li>Mozilla Firefox</li>
                <li>Safari</li>
            </ul>

            <p>Please use the latest version of the browsers listed above.</p>
            <p>The following environments are not guaranteed to work:</p>

            <ul>
                <li>Smartphones</li>
                <li>Tablets</li>
            </ul>
        </div>
    </section>

</div>

<footer class="zac-login-footer">
    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>" alt="Zojirushi">
    <span>
  Copyright © ZOJIRUSHI AMERICA CORPORATION. All Rights Reserved.
  <a href="<?php echo esc_url(home_url('/terms-of-use/')); ?>">Terms of Use</a>
</span></footer>

<?php wp_footer(); ?>
</body>
</html>