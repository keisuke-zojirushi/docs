<?php
/*
Template Name: ZAC Forgot Password
*/

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/') );
    exit;
}

$message = '';

if ( isset($_POST['zac_reset_submit']) ) {

    $email = sanitize_email($_POST['user_email']);

    $user = get_user_by('email', $email);

    if ( $user ) {

        retrieve_password($user->user_login);

        wp_redirect(home_url('/forgot-password-confirmation/'));
        exit;

    } else {

        $message = 'Email address not found.';
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

<body class="zac-forgot-page">

<div class="zac-forgot-wrapper">

    <img class="zac-login-logo"
         src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/zojirushi_logo.svg"
         alt="Zojirushi">

    <h1>Zojirushi America Tech Information</h1>

    <div class="zac-forgot-info">

        Enter your email address below and we will send a password reset link.

        <br><br>

        If you do not receive the email, please check your spam folder or contact Zojirushi Technical Support.

    </div>

    <form method="post" class="zac-forgot-form">

        <?php if($message): ?>
            <div class="zac-login-error">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <label>Email Address</label>

        <input type="email"
               name="user_email"
               required>

        <button type="submit"
                name="zac_reset_submit">

            Send Reset Link

        </button>

    </form>

</div>

<footer class="zac-login-footer">

    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/zojirushi_logo.svg">

    <span>
        Copyright © ZOJIRUSHI AMERICA CORPORATION. All Rights Reserved.
    </span>

</footer>

<?php wp_footer(); ?>
</body>
</html>
