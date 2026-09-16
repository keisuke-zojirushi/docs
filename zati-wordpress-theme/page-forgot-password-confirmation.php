<?php
/*
Template Name: ZAC Forgot Password Confirmation
*/
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
         src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>"
         alt="Zojirushi">

    <h1>Zojirushi America Tech Information</h1>

    <div class="zac-forgot-info">
        A password reset email has been sent to the address you entered.

        <br><br>

        Please check your inbox and follow the instructions in the email to reset your password.

        <br><br>

        If you do not receive the email within a few minutes, please check your spam folder or contact Zojirushi Technical Support.
    </div>

    <a class="zac-back-login-link" href="<?php echo esc_url(home_url('/login/')); ?>">
        Back to Login
    </a>

</div>

<footer class="zac-login-footer">
    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/zojirushi_logo.svg'); ?>" alt="Zojirushi">

    <span>
        Copyright © ZOJIRUSHI AMERICA CORPORATION. All Rights Reserved.
    </span>
</footer>

<?php wp_footer(); ?>
</body>
</html>